<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\AiQuizDraftService;
use App\Service\AiService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\FrozenTime;
use RuntimeException;
use Throwable;

class AiRequestsProcessCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $limit = (int)($args->getOption('limit') ?? 3);
        $limit = max(1, min(25, $limit));

        $aiRequests = $this->fetchTable('AiRequests');
        $assetsTable = $this->fetchTable('AiRequestAssets');
        $languagesTable = $this->fetchTable('Languages');

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

        // Build system message language list.
        $langs = $languagesTable->find('list', keyField: 'id', valueField: 'name')->orderByAsc('Languages.id')->toArray();
        $langIds = array_keys($langs);
        $languagesList = implode(', ', array_map(static fn($id, $name) => $id . ':' . $name, $langIds, array_values($langs)));

        $systemMessage = "You are a professional test creator assistant.
The user will provide a description of a test and optional images.
You MUST return a valid JSON object representing the test questions, answers, and translations.

The available languages are: {$languagesList}.
You MUST provide translations for ALL these languages for every text field (title, description, question text, answer text).

Expected JSON format:
{
  \"translations\": {
    \"[language_id]\": { \"title\": \"...\", \"description\": \"...\" }
  },
  \"questions\": [
    {
      \"type\": \"multiple_choice\" | \"true_false\" | \"text\",
      \"translations\": { \"[language_id]\": \"Question\" },
      \"answers\": [
        {
          \"is_correct\": true|false,
          \"translations\": { \"[language_id]\": \"Answer\" }
        }
      ]
    }
  ]
}

Rules:
1) Use ONLY integer language IDs as keys: " . implode(', ', $langIds) . "
2) For true_false provide exactly 2 answers.
3) For multiple_choice provide 4 answers.
4) Ensure at least one correct answer for non-text questions.
5) Output ONLY the JSON object (no markdown).";

        $aiService = new AiService();
        $draftService = new AiQuizDraftService();
        $testsTable = $this->fetchTable('Tests');
        $categoriesTable = $this->fetchTable('Categories');

        foreach ($rows as $req) {
            $io->out('Processing request #' . (int)$req->id);

            $now = FrozenTime::now();

            // Apply-only mode for already generated drafts.
            $applyOnly = ((string)$req->status === 'success') && ($req->test_id === null) && is_string($req->output_payload) && $req->output_payload !== '';
            if (!$applyOnly) {
                $req->status = 'processing';
                $req->started_at = $now;
                $req->updated_at = $now;
                $aiRequests->save($req);
            }

            $opts = [];
            if (is_string($req->input_payload) && $req->input_payload !== '') {
                $tmp = json_decode($req->input_payload, true);
                if (is_array($tmp)) {
                    $opts = $tmp;
                }
            }

            $prompt = trim((string)($opts['prompt'] ?? ''));
            $questionCount = isset($opts['question_count']) && is_numeric($opts['question_count']) ? (int)$opts['question_count'] : null;
            if ($questionCount !== null && $questionCount > 0) {
                $prompt .= "\n\nGenerate exactly {$questionCount} questions.";
            }

            try {
                $draft = null;
                if ($applyOnly) {
                    $draft = json_decode((string)$req->output_payload, true);
                    if (!is_array($draft) || json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException('Stored AI draft is invalid JSON.');
                    }
                }

                $assets = $assetsTable->find()
                    ->where(['ai_request_id' => (int)$req->id])
                    ->orderByAsc('id')
                    ->all()
                    ->toList();

                $dataUrls = [];
                foreach ($assets as $asset) {
                    $rel = (string)$asset->storage_path;
                    $abs = ROOT . DS . str_replace('/', DS, $rel);
                    if (!is_file($abs)) {
                        continue;
                    }
                    $bytes = file_get_contents($abs);
                    if ($bytes === false) {
                        continue;
                    }
                    $b64 = base64_encode($bytes);
                    $mime = (string)$asset->mime_type;
                    $dataUrls[] = 'data:' . $mime . ';base64,' . $b64;
                }

                if (!$applyOnly) {
                    $content = $aiService->generateVisionContent(
                        $prompt,
                        $dataUrls,
                        $systemMessage,
                        0.7,
                        ['response_format' => ['type' => 'json_object']],
                    );

                    $draft = json_decode($content, true);
                    if (!is_array($draft) || json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException('AI returned invalid JSON.');
                    }
                }

                // Validate + build test data.
                $built = $draftService->validateAndBuildTestData($draft);
                $testData = $built['testData'];
                $testData['created_by'] = (int)$req->user_id;
                $testData['is_public'] = false;
                $testData['category_id'] = isset($opts['category_id']) && is_numeric($opts['category_id']) ? (int)$opts['category_id'] : null;
                $testData['difficulty_id'] = isset($opts['difficulty_id']) && is_numeric($opts['difficulty_id']) ? (int)$opts['difficulty_id'] : null;

                if (empty($testData['category_id'])) {
                    $defaultCategory = $categoriesTable->find()
                        ->select(['id'])
                        ->where(['Categories.is_active' => true])
                        ->orderByAsc('Categories.id')
                        ->first();
                    if ($defaultCategory) {
                        $testData['category_id'] = (int)$defaultCategory->id;
                    }
                }
                if (empty($testData['category_id'])) {
                    throw new RuntimeException('No active category exists for generated quiz.');
                }

                if (!empty($testData['questions'])) {
                    foreach ($testData['questions'] as &$q) {
                        $q['category_id'] = $testData['category_id'];
                        $q['created_by'] = (int)$req->user_id;
                    }
                }

                $test = $testsTable->newEmptyEntity();
                $test = $testsTable->patchEntity($test, $testData, [
                    'associated' => [
                        'Questions' => ['associated' => [
                            'Answers' => ['associated' => ['AnswerTranslations']],
                            'QuestionTranslations',
                        ]],
                        'TestTranslations',
                    ],
                ]);

                if (!$testsTable->save($test)) {
                    throw new RuntimeException('Could not save generated quiz.');
                }

                $req->test_id = (int)$test->id;
                if (!$applyOnly) {
                    $req->output_payload = json_encode($draft, JSON_UNESCAPED_SLASHES);
                }
                $req->status = 'success';
                $req->finished_at = FrozenTime::now();
                $req->updated_at = FrozenTime::now();
                $req->error_code = null;
                $req->error_message = null;

                $aiRequests->save($req);
            } catch (Throwable $e) {
                $req->status = 'failed';
                $req->finished_at = FrozenTime::now();
                $req->updated_at = FrozenTime::now();
                $msg = $e->getMessage();
                $req->error_code = str_contains($msg, 'save generated quiz') ? 'TEST_SAVE_FAILED' : 'AI_FAILED';
                $req->error_message = $e->getMessage();
                $aiRequests->save($req);

                $io->err('Failed request #' . (int)$req->id . ': ' . $e->getMessage());
            }
        }

        return static::CODE_SUCCESS;
    }

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
