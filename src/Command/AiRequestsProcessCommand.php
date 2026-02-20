<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\AiQuizDraftService;
use App\Service\AiQuizPromptService;
use App\Service\AiService;
use App\Service\AiServiceException;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\FrozenTime;
use RuntimeException;
use Throwable;
use ZipArchive;

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
        $langs = $languagesTable->find('list', keyField: 'id', valueField: 'name')
            ->orderByAsc('Languages.id')
            ->toArray();
        $promptService = new AiQuizPromptService();
        $systemMessage = $promptService->getGenerationSystemPrompt($langs);

        $aiService = new AiService();
        $draftService = new AiQuizDraftService();
        $testsTable = $this->fetchTable('Tests');

        foreach ($rows as $req) {
            $io->out('Processing request #' . (int)$req->id);

            $now = FrozenTime::now();

            // Apply-only mode for already generated drafts.
            $applyOnly =
                (string)$req->status === 'success' &&
                $req->test_id === null &&
                is_string($req->output_payload) &&
                $req->output_payload !== '';
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
            $questionCount = isset($opts['question_count']) && is_numeric($opts['question_count'])
                ? (int)$opts['question_count']
                : null;
            $prompt = $promptService->buildGenerationUserPrompt($prompt, $questionCount);

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
                $documentBlocks = [];
                $maxExtractChars = max(2000, (int)env('AI_MAX_DOCUMENT_EXTRACT_CHARS', '20000'));
                $remainingChars = $maxExtractChars;
                foreach ($assets as $asset) {
                    $rel = (string)$asset->storage_path;
                    $abs = ROOT . DS . str_replace('/', DS, $rel);
                    if (!is_file($abs)) {
                        continue;
                    }
                    $mime = (string)$asset->mime_type;

                    if (str_starts_with($mime, 'image/')) {
                        $bytes = file_get_contents($abs);
                        if ($bytes === false) {
                            continue;
                        }
                        $b64 = base64_encode($bytes);
                        $dataUrls[] = 'data:' . $mime . ';base64,' . $b64;

                        continue;
                    }

                    if ($remainingChars <= 0) {
                        continue;
                    }

                    $extracted = $this->extractDocumentText($abs, $mime);
                    if ($extracted === '') {
                        continue;
                    }

                    if (mb_strlen($extracted) > $remainingChars) {
                        $extracted = mb_substr($extracted, 0, $remainingChars);
                    }
                    $remainingChars -= mb_strlen($extracted);

                    $documentBlocks[] = "Source: {$rel} ({$mime})\n" . $extracted;
                }

                $augmentedPrompt = $prompt;
                if ($documentBlocks) {
                    $augmentedPrompt .= "\n\nUse these uploaded documents as additional context. "
                        . "Prioritize factual consistency with this material:\n\n"
                        . implode("\n\n---\n\n", $documentBlocks);
                }

                if (!$applyOnly) {
                    $content = $aiService->generateVisionContent(
                        $augmentedPrompt,
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
                $testData['is_public'] = $this->boolOrDefault($opts['is_public'] ?? null, true);
                $testData['category_id'] = isset($opts['category_id']) && is_numeric($opts['category_id'])
                    ? (int)$opts['category_id']
                    : null;
                $testData['difficulty_id'] = isset($opts['difficulty_id']) && is_numeric($opts['difficulty_id'])
                    ? (int)$opts['difficulty_id']
                    : null;

                if ($testData['category_id'] === null) {
                    throw new RuntimeException('CATEGORY_REQUIRED: Category is required.');
                }
                if (!$this->isCategoryValidAndActive((int)$testData['category_id'])) {
                    throw new RuntimeException('CATEGORY_INVALID: Category is invalid or inactive.');
                }

                if ($testData['difficulty_id'] === null) {
                    throw new RuntimeException('DIFFICULTY_REQUIRED: Difficulty is required.');
                }
                if (!$this->isDifficultyValidAndActive((int)$testData['difficulty_id'])) {
                    throw new RuntimeException('DIFFICULTY_INVALID: Difficulty is invalid or inactive.');
                }

                if (!empty($testData['questions'])) {
                    foreach ($testData['questions'] as &$q) {
                        $q['category_id'] = $testData['category_id'];
                        $q['created_by'] = (int)$req->user_id;

                        if (!empty($q['answers']) && is_array($q['answers'])) {
                            $position = 1;
                            foreach ($q['answers'] as &$a) {
                                if (!is_array($a)) {
                                    continue;
                                }

                                $a['position'] = $position;
                                $position += 1;
                                $a['source_type'] = (string)($a['source_type'] ?? 'ai');

                                $sourceText = '';
                                if (!empty($a['answer_translations']) && is_array($a['answer_translations'])) {
                                    foreach ($a['answer_translations'] as &$at) {
                                        if (!is_array($at)) {
                                            continue;
                                        }
                                        $at['source_type'] = (string)($at['source_type'] ?? $a['source_type']);
                                        $at['created_by'] = (int)$req->user_id;

                                        if ($sourceText === '') {
                                            $candidate = trim((string)($at['content'] ?? ''));
                                            if ($candidate !== '') {
                                                $sourceText = $candidate;
                                            }
                                        }
                                    }
                                    unset($at);
                                }

                                if ($sourceText !== '') {
                                    $a['source_text'] = $sourceText;
                                }
                            }
                            unset($a);
                        }
                    }
                    unset($q);
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
                if (str_starts_with($msg, 'CATEGORY_REQUIRED:')) {
                    $req->error_code = 'CATEGORY_REQUIRED';
                    $msg = trim(substr($msg, strlen('CATEGORY_REQUIRED:')));
                } elseif (str_starts_with($msg, 'CATEGORY_INVALID:')) {
                    $req->error_code = 'CATEGORY_INVALID';
                    $msg = trim(substr($msg, strlen('CATEGORY_INVALID:')));
                } elseif (str_starts_with($msg, 'DIFFICULTY_REQUIRED:')) {
                    $req->error_code = 'DIFFICULTY_REQUIRED';
                    $msg = trim(substr($msg, strlen('DIFFICULTY_REQUIRED:')));
                } elseif (str_starts_with($msg, 'DIFFICULTY_INVALID:')) {
                    $req->error_code = 'DIFFICULTY_INVALID';
                    $msg = trim(substr($msg, strlen('DIFFICULTY_INVALID:')));
                } elseif (str_contains($msg, 'save generated quiz')) {
                    $req->error_code = 'TEST_SAVE_FAILED';
                } elseif ($e instanceof AiServiceException) {
                    $req->error_code = $e->getErrorCode();
                } else {
                    $req->error_code = 'AI_FAILED';
                }
                $req->error_message = $msg;
                $aiRequests->save($req);

                $io->err('Failed request #' . (int)$req->id . ': ' . $e->getMessage());
            }
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Extract normalized text from a supported document type.
     *
     * @param string $absolutePath Absolute file path.
     * @param string $mime Detected MIME type.
     * @return string
     */
    private function extractDocumentText(string $absolutePath, string $mime): string
    {
        $mime = strtolower(trim($mime));

        $text = match ($mime) {
            'text/plain', 'text/markdown', 'text/csv', 'application/json', 'application/xml', 'text/xml'
                => $this->readPlainTextFile($absolutePath),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                => $this->extractDocxText($absolutePath),
            'application/vnd.oasis.opendocument.text'
                => $this->extractOdtText($absolutePath),
            'application/pdf'
                => $this->extractPdfText($absolutePath),
            default => '',
        };

        return $this->normalizeExtractedText($text);
    }

    /**
     * Read plain text-like documents.
     *
     * @param string $absolutePath Absolute file path.
     * @return string
     */
    private function readPlainTextFile(string $absolutePath): string
    {
        $content = file_get_contents($absolutePath);
        if (!is_string($content)) {
            return '';
        }

        return $content;
    }

    /**
     * Extract text from a DOCX file.
     *
     * @param string $absolutePath Absolute file path.
     * @return string
     */
    private function extractDocxText(string $absolutePath): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $chunks = [];
        foreach (
            [
                'word/document.xml',
                'word/header1.xml',
                'word/header2.xml',
                'word/footer1.xml',
                'word/footer2.xml',
            ] as $entry
        ) {
            $xml = $zip->getFromName($entry);
            if (!is_string($xml) || $xml === '') {
                continue;
            }
            $chunks[] = trim(html_entity_decode(strip_tags($xml)));
        }
        $zip->close();

        return implode("\n", array_filter($chunks, static fn($v) => $v !== ''));
    }

    /**
     * Extract text from an ODT file.
     *
     * @param string $absolutePath Absolute file path.
     * @return string
     */
    private function extractOdtText(string $absolutePath): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('content.xml');
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            return '';
        }

        return trim(html_entity_decode(strip_tags($xml)));
    }

    /**
     * Extract text from a PDF file.
     *
     * @param string $absolutePath Absolute file path.
     * @return string
     */
    private function extractPdfText(string $absolutePath): string
    {
        if (!function_exists('shell_exec')) {
            return '';
        }

        $escaped = escapeshellarg($absolutePath);
        $cmd = 'pdftotext -layout -nopgbrk ' . $escaped . ' - 2>/dev/null';
        $output = shell_exec($cmd);

        return is_string($output) ? $output : '';
    }

    /**
     * Normalize extracted text for prompt usage.
     *
     * @param string $text Raw extracted text.
     * @return string
     */
    private function normalizeExtractedText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\r\n?/', "\n", $text) ?? $text;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Convert mixed input to boolean with fallback default.
     *
     * @param mixed $value Raw input value.
     * @param bool $default Fallback value when input is not parseable.
     * @return bool
     */
    private function boolOrDefault(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int)$value !== 0;
        }
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return $default;
    }

    /**
     * Check whether the category exists and is active.
     *
     * @param int $categoryId Category identifier.
     * @return bool
     */
    private function isCategoryValidAndActive(int $categoryId): bool
    {
        $categories = $this->fetchTable('Categories');
        $query = $categories->find()->where(['Categories.id' => $categoryId]);
        if ($categories->getSchema()->hasColumn('is_active')) {
            $query->where(['Categories.is_active' => true]);
        }

        return $query->count() > 0;
    }

    /**
     * Check whether the difficulty exists and is active.
     *
     * @param int $difficultyId Difficulty identifier.
     * @return bool
     */
    private function isDifficultyValidAndActive(int $difficultyId): bool
    {
        $difficulties = $this->fetchTable('Difficulties');
        $query = $difficulties->find()->where(['Difficulties.id' => $difficultyId]);
        if ($difficulties->getSchema()->hasColumn('is_active')) {
            $query->where(['Difficulties.is_active' => true]);
        }

        return $query->count() > 0;
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
