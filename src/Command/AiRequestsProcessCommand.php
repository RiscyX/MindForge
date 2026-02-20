<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\AiQuestionGenerationPipelineService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
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
}
