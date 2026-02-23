<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\AiQuestionGenerationPipelineService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Throwable;

class AiRequestsProcessCommand extends Command
{
    /**
     * Process pending AI generation requests.
     *
     * @param \Cake\Console\Arguments $args Command arguments.
     * @param \Cake\Console\ConsoleIo $io Console output.
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $limit = (int)($args->getOption('limit') ?? 3);
        $limit = max(1, min(25, $limit));

        $aiRequests = $this->fetchTable('AiRequests');

        // Process pending requests and also apply ready drafts that have no test_id yet.
        $rows = $aiRequests->find()
            ->where([
                'type' => 'test_generation_async',
                'OR' => [
                    ['status' => 'pending'],
                    ['status' => 'success', 'test_id IS' => null],
                ],
            ])
            ->orderByAsc('AiRequests.created_at')
            ->limit($limit)
            ->all()
            ->toList();

        if (!$rows) {
            $io->out('No pending requests.');

            return static::CODE_SUCCESS;
        }

        $pipeline = new AiQuestionGenerationPipelineService();

        foreach ($rows as $req) {
            $io->out('Processing request #' . (int)$req->id);
            try {
                $result = $pipeline->run((int)$req->id);
                $io->out(
                    sprintf(
                        'Request #%d -> test #%d (%s)',
                        (int)$result['ai_request_id'],
                        (int)$result['test_id'],
                        (bool)$result['apply_only'] ? 'apply-only' : 'generated',
                    ),
                );
            } catch (Throwable $e) {
                $io->err('Failed request #' . (int)$req->id . ': ' . $e->getMessage());
                if ($this->shouldRetryRateLimit($e->getMessage())) {
                    $this->requeueWithBackoff((int)$req->id, $io);
                }
            }
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Build CLI options.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser Parser instance.
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->addOption('limit', [
            'short' => 'l',
            'help' => 'Max number of pending requests to process',
            'default' => 3,
        ]);

        return $parser;
    }

    /**
     * Determines if a failed request should be retried based on rate limit error message.
     *
     * @param string $message The error message to check.
     * @return bool True if the request should be retried.
     */
    private function shouldRetryRateLimit(string $message): bool
    {
        return str_contains($message, '429')
            || str_contains(strtolower($message), 'rate limit');
    }

    /**
     * Re-queues a rate-limited request with exponential backoff.
     *
     * @param int $requestId The ID of the AI request to requeue.
     * @param \Cake\Console\ConsoleIo $io The console IO instance.
     * @return void
     */
    private function requeueWithBackoff(int $requestId, ConsoleIo $io): void
    {
        $aiRequests = $this->fetchTable('AiRequests');
        $request = $aiRequests->find()->where(['id' => $requestId])->first();
        if ($request === null) {
            return;
        }

        $meta = [];
        if (is_string($request->meta) && $request->meta !== '') {
            $decoded = json_decode($request->meta, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $retryCount = (int)($meta['retry_count'] ?? 0) + 1;
        if ($retryCount > 3) {
            $io->warning('Request #' . $requestId . ' reached retry limit after rate limiting.');

            return;
        }

        $meta['retry_count'] = $retryCount;
        $meta['last_retry_reason'] = 'rate_limited';
        $meta['last_retry_at'] = DateTime::now()->format('c');

        $request->status = 'pending';
        $request->started_at = null;
        $request->finished_at = null;
        $request->error_code = null;
        $request->error_message = null;
        $request->meta = json_encode($meta, JSON_UNESCAPED_SLASHES);
        $request->updated_at = DateTime::now();
        $aiRequests->save($request, ['validate' => false]);

        $sleepSeconds = min(20, 3 * $retryCount);
        $msg = sprintf(
            'Re-queued request #%d after rate limit (retry %d/3). Sleeping %ds.',
            $requestId,
            $retryCount,
            $sleepSeconds,
        );
        $io->warning($msg);
        sleep($sleepSeconds);
    }
}
