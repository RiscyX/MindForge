<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\ApiTokenService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\FactoryLocator;

class ApiTokensCleanupCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $retentionDays = max(1, (int)$args->getOption('retention-days'));

        $tableLocator = FactoryLocator::get('Table');
        /** @var \App\Model\Table\ApiTokensTable $apiTokens */
        $apiTokens = $tableLocator->get('ApiTokens');

        $service = new ApiTokenService($apiTokens);
        $deleted = $service->cleanup($retentionDays);

        $io->out(sprintf('Deleted %d API token rows.', $deleted));

        return static::CODE_SUCCESS;
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->addOption('retention-days', [
            'short' => 'd',
            'help' => 'Keep revoked tokens newer than this many days.',
            'default' => 30,
        ]);

        return $parser;
    }
}
