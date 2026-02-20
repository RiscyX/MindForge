<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Role;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use RuntimeException;

// phpcs:disable Generic.Files.LineLength.TooLong,Generic.PHP.NoSilencedErrors.Discouraged

class SeedAiQuizzesCommand extends Command
{
    private const SOURCE_PREFIX = 'ai_seed:';

    /**
     * @var array<int, string>
     */
    private const PROMPT_STYLES = [
        'Use practical real-world scenarios and avoid trivia-only questions.',
        'Focus on applied understanding, not memorization.',
        'Include a balanced progression from basic to intermediate challenge.',
        'Prefer clear, concise stems and plausible distractors.',
        'Emphasize cause-effect reasoning and decision-making cases.',
    ];

    /**
     * Seeds and optionally processes AI quiz generation requests.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $cleanup = (bool)$args->getOption('cleanup');
        $cleanupOnly = (bool)$args->getOption('cleanup_only');
        $cleanupRun = trim((string)$args->getOption('cleanup_run'));

        $connection = ConnectionManager::get('default');

        if ($cleanup) {
            $cleanupToken = $cleanupRun !== '' ? $cleanupRun : null;
            $result = $this->cleanupRun($connection, $cleanupToken);
            $io->success(sprintf(
                'Cleanup finished. Requests: %d, Tests: %d, Attempts: %d, AttemptAnswers: %d, Favorites: %d, Assets: %d, Files: %d',
                $result['requests_deleted'],
                $result['tests_deleted'],
                $result['attempts_deleted'],
                $result['attempt_answers_deleted'],
                $result['favorites_deleted'],
                $result['assets_deleted'],
                $result['files_deleted'],
            ));

            if ($cleanupOnly) {
                return static::CODE_SUCCESS;
            }
        }

        $count = max(1, min(50, (int)$args->getOption('count')));
        $questionCount = max(4, min(30, (int)$args->getOption('questions')));
        $processLimit = max(1, min(50, (int)$args->getOption('process_limit')));
        $maxCycles = max(1, min(300, (int)$args->getOption('max_cycles')));
        $enqueueOnly = (bool)$args->getOption('enqueue_only');

        $runToken = trim((string)$args->getOption('run_token'));
        if ($runToken === '') {
            $runToken = FrozenTime::now()->format('YmdHis') . '-r' . random_int(1000, 9999);
        }

        $creatorId = $this->resolveCreatorId($connection, $args->getOption('creator_id'));
        $languageId = $this->resolveLanguageId($connection, (string)$args->getOption('language_code'));
        $themes = $this->loadThemes($connection);
        if (!$themes) {
            throw new RuntimeException('No active categories available for AI seed generation.');
        }
        $difficulties = $this->loadDifficulties($connection);
        if (!$difficulties) {
            throw new RuntimeException('No difficulties available for AI seed generation.');
        }

        $sourceReference = self::SOURCE_PREFIX . $runToken;
        $io->out('AI Seed Quiz Generation');
        $io->out('Run token: ' . $runToken);
        $io->out('Source reference: ' . $sourceReference);
        $io->out('Creator id: ' . $creatorId);
        $io->out('Language id: ' . ($languageId ?? 'null'));

        $requestIds = $this->enqueueRequests(
            $connection,
            $sourceReference,
            $creatorId,
            $languageId,
            $count,
            $questionCount,
            $themes,
            $difficulties,
        );

        $io->success('Queued AI requests: ' . count($requestIds));

        if ($enqueueOnly) {
            $io->out('Enqueue-only mode enabled. Process with: /opt/lampp/bin/php bin/cake.php ai_requests_process --limit=' . $processLimit);
            $io->out('Cleanup with: /opt/lampp/bin/php bin/cake.php seed_ai_quizzes --cleanup --cleanup_run=' . $runToken . ' --cleanup_only');

            return static::CODE_SUCCESS;
        }

        $phpBin = trim((string)$args->getOption('php_bin'));
        if ($phpBin === '') {
            $phpBin = PHP_BINARY;
        }

        $this->processQueuedRequests($connection, $io, $sourceReference, $phpBin, $processLimit, $maxCycles);

        $summary = $this->runSummary($connection, $sourceReference);
        $io->success(sprintf(
            'Done. success=%d failed=%d pending=%d processing=%d',
            $summary['success'],
            $summary['failed'],
            $summary['pending'],
            $summary['processing'],
        ));

        $testIds = $this->fetchRunTestIds($connection, $sourceReference);
        if ($testIds) {
            $io->out('Generated test IDs: ' . implode(', ', array_slice($testIds, 0, 20)) . (count($testIds) > 20 ? ' ...' : ''));
        }
        $io->out('Rollback command: /opt/lampp/bin/php bin/cake.php seed_ai_quizzes --cleanup --cleanup_run=' . $runToken . ' --cleanup_only');

        return static::CODE_SUCCESS;
    }

    /**
     * Builds CLI options for seeding and cleanup workflows.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('count', [
            'short' => 'n',
            'help' => 'How many AI quizzes to generate (1-50)',
            'default' => '10',
        ]);
        $parser->addOption('questions', [
            'help' => 'Question count per quiz (4-30)',
            'default' => '10',
        ]);
        $parser->addOption('run_token', [
            'help' => 'Custom run token for tracking/rollback',
            'default' => null,
        ]);
        $parser->addOption('creator_id', [
            'help' => 'Creator user id to own generated quizzes',
            'default' => null,
        ]);
        $parser->addOption('language_code', [
            'help' => 'Preferred language code for request context (e.g. en_US)',
            'default' => 'en_US',
        ]);
        $parser->addOption('enqueue_only', [
            'help' => 'Only enqueue AI requests, do not process',
            'boolean' => true,
            'default' => false,
        ]);
        $parser->addOption('process_limit', [
            'help' => 'Per-cycle limit for ai_requests_process',
            'default' => '10',
        ]);
        $parser->addOption('max_cycles', [
            'help' => 'Max processor cycles before stopping',
            'default' => '30',
        ]);
        $parser->addOption('php_bin', [
            'help' => 'PHP binary used to invoke ai_requests_process',
            'default' => null,
        ]);
        $parser->addOption('cleanup', [
            'short' => 'c',
            'help' => 'Cleanup generated AI-seed data first',
            'boolean' => true,
            'default' => false,
        ]);
        $parser->addOption('cleanup_run', [
            'help' => 'Cleanup one run token only',
            'default' => null,
        ]);
        $parser->addOption('cleanup_only', [
            'help' => 'Cleanup and exit (no new generation)',
            'boolean' => true,
            'default' => false,
        ]);

        return $parser;
    }

    /**
     * @param mixed $creatorIdOption
     */
    private function resolveCreatorId(Connection $connection, mixed $creatorIdOption): int
    {
        if (is_numeric($creatorIdOption) && (int)$creatorIdOption > 0) {
            $id = (int)$creatorIdOption;
            $row = $connection->execute(
                'SELECT id FROM users WHERE id = :id AND role_id IN (:creator, :admin) AND is_active = 1',
                ['id' => $id, 'creator' => Role::CREATOR, 'admin' => Role::ADMIN],
            )->fetch('assoc');
            if ($row) {
                return $id;
            }
            throw new RuntimeException('Provided --creator_id is invalid or not active creator/admin.');
        }

        $row = $connection->execute(
            'SELECT id FROM users WHERE role_id IN (:creator, :admin) AND is_active = 1 ORDER BY role_id ASC, id ASC LIMIT 1',
            ['creator' => Role::CREATOR, 'admin' => Role::ADMIN],
        )->fetch('assoc');

        if (!$row) {
            throw new RuntimeException('No active creator/admin user found. Create one or pass --creator_id.');
        }

        return (int)$row['id'];
    }

    /**
     * Resolves preferred language id with fallback to first available language.
     */
    private function resolveLanguageId(Connection $connection, string $preferredCode): ?int
    {
        $preferredCode = trim($preferredCode);
        if ($preferredCode !== '') {
            $row = $connection->execute(
                'SELECT id FROM languages WHERE code = :code LIMIT 1',
                ['code' => $preferredCode],
            )->fetch('assoc');
            if ($row) {
                return (int)$row['id'];
            }
        }

        $fallback = $connection->execute('SELECT id FROM languages ORDER BY id ASC LIMIT 1')->fetch('assoc');

        return $fallback ? (int)$fallback['id'] : null;
    }

    /**
     * @return array<int, array{id:int, name:string}>
     */
    private function loadThemes(Connection $connection): array
    {
        $rows = $connection->execute(
            'SELECT c.id, COALESCE(MIN(ct.name), CONCAT("Category ", c.id)) AS name '
            . 'FROM categories c '
            . 'LEFT JOIN category_translations ct ON ct.category_id = c.id '
            . 'WHERE c.is_active = 1 '
            . 'GROUP BY c.id '
            . 'ORDER BY c.id ASC',
        )->fetchAll('assoc');

        return array_values(array_map(static fn(array $r): array => [
            'id' => (int)$r['id'],
            'name' => trim((string)$r['name']) !== '' ? (string)$r['name'] : 'Category ' . (int)$r['id'],
        ], $rows));
    }

    /**
     * @return array<int, array{id:int, name:string}>
     */
    private function loadDifficulties(Connection $connection): array
    {
        $rows = $connection->execute(
            'SELECT d.id, COALESCE(MIN(dt.name), CONCAT("Difficulty ", d.id)) AS name '
            . 'FROM difficulties d '
            . 'LEFT JOIN difficulty_translations dt ON dt.difficulty_id = d.id '
            . 'GROUP BY d.id '
            . 'ORDER BY d.id ASC',
        )->fetchAll('assoc');

        return array_values(array_map(static fn(array $r): array => [
            'id' => (int)$r['id'],
            'name' => trim((string)$r['name']) !== '' ? (string)$r['name'] : 'Difficulty ' . (int)$r['id'],
        ], $rows));
    }

    /**
     * @param array<int, array{id:int, name:string}> $themes
     * @param array<int, array{id:int, name:string}> $difficulties
     * @return array<int>
     */
    private function enqueueRequests(
        Connection $connection,
        string $sourceReference,
        int $creatorId,
        ?int $languageId,
        int $count,
        int $questionCount,
        array $themes,
        array $difficulties,
    ): array {
        $aiRequests = $this->fetchTable('AiRequests');
        $now = FrozenTime::now();

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $theme = $themes[array_rand($themes)];
            $difficulty = $difficulties[array_rand($difficulties)];
            $style = self::PROMPT_STYLES[array_rand(self::PROMPT_STYLES)];

            $prompt = sprintf(
                '[AI-SEED] Generate a high-quality quiz about "%s" at "%s" difficulty. %s Include mixed question types and practical context.',
                $theme['name'],
                $difficulty['name'],
                $style,
            );

            $inputPayload = json_encode([
                'prompt' => $prompt,
                'category_id' => $theme['id'],
                'difficulty_id' => $difficulty['id'],
                'question_count' => $questionCount,
                'is_public' => true,
            ], JSON_UNESCAPED_SLASHES);

            if (!is_string($inputPayload)) {
                throw new RuntimeException('Failed to encode AI input payload.');
            }

            $rows[] = $aiRequests->newEntity([
                'user_id' => $creatorId,
                'language_id' => $languageId,
                'source_medium' => 'cli_seed_ai',
                'source_reference' => $sourceReference,
                'type' => 'test_generation_async',
                'input_payload' => $inputPayload,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!$aiRequests->saveMany($rows)) {
            throw new RuntimeException('Failed to enqueue AI requests.');
        }

        return array_values(array_map(static fn($e): int => (int)$e->id, $rows));
    }

    /**
     * Processes queued AI requests until completion or cycle limit.
     */
    private function processQueuedRequests(
        Connection $connection,
        ConsoleIo $io,
        string $sourceReference,
        string $phpBin,
        int $processLimit,
        int $maxCycles,
    ): void {
        $cakePath = ROOT . DS . 'bin' . DS . 'cake.php';
        $stableCyclesWithoutProgress = 0;
        $lastPending = null;

        for ($cycle = 1; $cycle <= $maxCycles; $cycle++) {
            $summary = $this->runSummary($connection, $sourceReference);
            $pendingWork = $summary['pending'] + $summary['processing'] + $summary['success_without_test'];
            $io->out(sprintf(
                'Cycle %d: pending=%d processing=%d success=%d failed=%d success_without_test=%d',
                $cycle,
                $summary['pending'],
                $summary['processing'],
                $summary['success'],
                $summary['failed'],
                $summary['success_without_test'],
            ));

            if ($pendingWork === 0) {
                return;
            }

            if ($lastPending !== null && $lastPending === $pendingWork) {
                $stableCyclesWithoutProgress++;
            } else {
                $stableCyclesWithoutProgress = 0;
            }
            $lastPending = $pendingWork;

            if ($stableCyclesWithoutProgress >= 5) {
                $io->warning('No progress in 5 cycles, stopping processor loop.');

                return;
            }

            $cmd = escapeshellarg($phpBin)
                . ' ' . escapeshellarg($cakePath)
                . ' ai_requests_process --limit=' . (int)$processLimit;

            $envPrefix = 'env -i PATH=' . escapeshellarg((string)getenv('PATH'))
                . ' HOME=' . escapeshellarg((string)getenv('HOME'));
            $cmd = $envPrefix . ' ' . $cmd;

            $output = [];
            $code = 0;
            exec($cmd, $output, $code);

            if ($code !== 0) {
                $io->err('ai_requests_process failed with exit code ' . $code);
                foreach (array_slice($output, -10) as $line) {
                    $io->err('  ' . $line);
                }

                return;
            }
        }

        $io->warning('Reached max cycles before full completion.');
    }

    /**
     * @return array{pending:int,processing:int,success:int,failed:int,success_without_test:int}
     */
    private function runSummary(Connection $connection, string $sourceReference): array
    {
        $rows = $connection->execute(
            'SELECT status, COUNT(*) AS cnt, SUM(CASE WHEN status = "success" AND test_id IS NULL THEN 1 ELSE 0 END) AS success_without_test '
            . 'FROM ai_requests WHERE source_reference = :source GROUP BY status',
            ['source' => $sourceReference],
        )->fetchAll('assoc');

        $summary = [
            'pending' => 0,
            'processing' => 0,
            'success' => 0,
            'failed' => 0,
            'success_without_test' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string)($row['status'] ?? '');
            $cnt = (int)($row['cnt'] ?? 0);
            if (isset($summary[$status])) {
                $summary[$status] = $cnt;
            }
            $summary['success_without_test'] += (int)($row['success_without_test'] ?? 0);
        }

        return $summary;
    }

    /**
     * @return array<int>
     */
    private function fetchRunTestIds(Connection $connection, string $sourceReference): array
    {
        $rows = $connection->execute(
            'SELECT DISTINCT test_id FROM ai_requests WHERE source_reference = :source AND test_id IS NOT NULL',
            ['source' => $sourceReference],
        )->fetchAll('assoc');

        return array_values(array_map(static fn(array $r): int => (int)$r['test_id'], $rows));
    }

    /**
     * @return array<string, int>
     */
    private function cleanupRun(Connection $connection, ?string $runToken = null): array
    {
        $sourceLike = self::SOURCE_PREFIX . '%';
        $params = ['source_like' => $sourceLike];
        $sql = 'SELECT id, test_id FROM ai_requests WHERE source_reference LIKE :source_like';
        if ($runToken !== null && $runToken !== '') {
            $params['source_exact'] = self::SOURCE_PREFIX . $runToken;
            $sql .= ' AND source_reference = :source_exact';
        }

        $requestRows = $connection->execute($sql, $params)->fetchAll('assoc');
        if (!$requestRows) {
            return [
                'requests_deleted' => 0,
                'tests_deleted' => 0,
                'attempts_deleted' => 0,
                'attempt_answers_deleted' => 0,
                'favorites_deleted' => 0,
                'assets_deleted' => 0,
                'files_deleted' => 0,
            ];
        }

        $requestIds = array_values(array_unique(array_map(static fn(array $r): int => (int)$r['id'], $requestRows)));
        $testIds = array_values(array_unique(array_filter(array_map(static fn(array $r): int => (int)($r['test_id'] ?? 0), $requestRows))));

        $filesDeleted = 0;
        if ($requestIds) {
            $assetRows = $connection->execute(
                'SELECT storage_path FROM ai_request_assets WHERE ai_request_id IN (' . implode(',', array_map('intval', $requestIds)) . ')',
            )->fetchAll('assoc');
            foreach ($assetRows as $asset) {
                $storagePath = trim((string)($asset['storage_path'] ?? ''));
                if ($storagePath === '') {
                    continue;
                }
                $absolute = ROOT . DS . str_replace('/', DS, ltrim($storagePath, '/'));
                if (is_file($absolute) && @unlink($absolute)) {
                    $filesDeleted++;
                }
            }
        }

        $attemptAnswersDeleted = 0;
        $attemptsDeleted = 0;
        $favoritesDeleted = 0;
        $testsDeleted = 0;

        if ($testIds) {
            $inTests = implode(',', array_map('intval', $testIds));
            $attemptRows = $connection->execute('SELECT id FROM test_attempts WHERE test_id IN (' . $inTests . ')')->fetchAll('assoc');
            $attemptIds = array_values(array_map(static fn(array $r): int => (int)$r['id'], $attemptRows));

            if ($attemptIds) {
                $inAttempts = implode(',', array_map('intval', $attemptIds));
                $attemptAnswersDeleted += $connection->execute('DELETE FROM test_attempt_answers WHERE test_attempt_id IN (' . $inAttempts . ')')->rowCount();
                $attemptsDeleted += $connection->execute('DELETE FROM test_attempts WHERE id IN (' . $inAttempts . ')')->rowCount();
            }

            $favoritesDeleted += $connection->execute('DELETE FROM user_favorite_tests WHERE test_id IN (' . $inTests . ')')->rowCount();
            $testsDeleted += $connection->execute('DELETE FROM tests WHERE id IN (' . $inTests . ')')->rowCount();
        }

        $assetsDeleted = 0;
        if ($requestIds) {
            $inRequests = implode(',', array_map('intval', $requestIds));
            $assetsDeleted += $connection->execute('DELETE FROM ai_request_assets WHERE ai_request_id IN (' . $inRequests . ')')->rowCount();
            $requestsDeleted = $connection->execute('DELETE FROM ai_requests WHERE id IN (' . $inRequests . ')')->rowCount();
        } else {
            $requestsDeleted = 0;
        }

        return [
            'requests_deleted' => $requestsDeleted,
            'tests_deleted' => $testsDeleted,
            'attempts_deleted' => $attemptsDeleted,
            'attempt_answers_deleted' => $attemptAnswersDeleted,
            'favorites_deleted' => $favoritesDeleted,
            'assets_deleted' => $assetsDeleted,
            'files_deleted' => $filesDeleted,
        ];
    }
}
