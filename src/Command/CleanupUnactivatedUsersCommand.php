<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\FrozenTime;

/**
 * Removes unactivated user accounts whose activation token has expired
 * beyond a configurable grace period.
 *
 * Usage:
 *   bin/cake cleanup_unactivated_users
 *   bin/cake cleanup_unactivated_users --grace-hours 48
 *
 * Cron example (daily at 03:00):
 *   0 3 * * * /opt/lampp/bin/php /opt/lampp/htdocs/MindForge/bin/cake cleanup_unactivated_users
 */
class CleanupUnactivatedUsersCommand extends Command
{
    /**
     * @param \Cake\Console\Arguments $args
     * @param \Cake\Console\ConsoleIo $io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $graceHours = max(1, (int)$args->getOption('grace-hours'));

        $tableLocator = FactoryLocator::get('Table');

        /** @var \App\Model\Table\UserTokensTable $userTokensTable */
        $userTokensTable = $tableLocator->get('UserTokens');

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $tableLocator->get('Users');

        $cutoff = FrozenTime::now()->subHours($graceHours);

        // Find user IDs whose most recent activation token expired before the cutoff
        // and who are still not activated.
        $expiredUserIds = $userTokensTable->find()
            ->select(['user_id'])
            ->where([
                'type' => 'activate',
                'expires_at <' => $cutoff,
            ])
            ->groupBy('user_id')
            ->all()
            ->extract('user_id')
            ->toArray();

        if (empty($expiredUserIds)) {
            $io->out('No unactivated users to clean up.');

            return static::CODE_SUCCESS;
        }

        // Filter to only those that are still unactivated.
        $zombieUserIds = $usersTable->find()
            ->select(['id'])
            ->where([
                'id IN' => $expiredUserIds,
                'is_active' => false,
            ])
            ->all()
            ->extract('id')
            ->toArray();

        if (empty($zombieUserIds)) {
            $io->out('No unactivated users to clean up.');

            return static::CODE_SUCCESS;
        }

        // Delete related user_tokens rows first (in case no CASCADE is defined)
        $deletedTokens = $userTokensTable->deleteAll(['user_id IN' => $zombieUserIds]);

        // Delete the zombie user accounts
        $deletedUsers = $usersTable->deleteAll([
            'id IN' => $zombieUserIds,
            'is_active' => false,
        ]);

        $io->out(sprintf(
            'Cleaned up %d unactivated user(s) and %d token row(s).',
            $deletedUsers,
            $deletedTokens,
        ));

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Delete unactivated user accounts whose activation token expired more than --grace-hours hours ago.',
        );

        $parser->addOption('grace-hours', [
            'short' => 'g',
            'help' => 'Hours after token expiry before the account is deleted (default: 24).',
            'default' => 24,
        ]);

        return $parser;
    }
}
