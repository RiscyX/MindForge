<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;
use Throwable;
use ZipArchive;

class AiQuestionGenerationPipelineService
{
    use LocatorAwareTrait;

    /**
     * Run full generation pipeline for one upload/request id.
     *
     * @param int $uploadId Upload identifier (mapped to ai_requests.id).
     * @return array{ai_request_id:int,test_id:int,status:string,apply_only:bool}
     */
    public function run(int $uploadId): array
    {
        $aiRequests = $this->fetchTable('AiRequests');
        $req = $aiRequests->find()
            ->where([
                'AiRequests.id' => $uploadId,
                'AiRequests.type' => 'test_generation_async',
            ])
            ->first();

        if ($req === null) {
            throw new RuntimeException('AI request not found.');
        }

        if ($req->test_id !== null) {
            return [
                'ai_request_id' => (int)$req->id,
                'test_id' => (int)$req->test_id,
                'status' => (string)$req->status,
                'apply_only' => true,
            ];
        }

        $applyOnly =
            (string)$req->status === 'success' &&
            $req->test_id === null &&
            is_string($req->output_payload) &&
            $req->output_payload !== '';

        if (!$applyOnly) {
            $now = FrozenTime::now();
            $req->status = 'processing';
            $req->started_at = $now;
            $req->updated_at = $now;
            $aiRequests->save($req);
        }

        $opts = $this->decodeInputOptions($req->input_payload ?? null);
        $promptService = new AiQuizPromptService();
        $generationPromptVersion = $promptService->getGenerationPromptVersion();
        if ((string)($req->prompt_version ?? '') === '') {
            $req->prompt_version = $generationPromptVersion;
        }

        $providerResult = null;
        $providerDurationMs = null;
        $providerCallStartedIso = null;
        $rawProviderContent = null;

        try {
            $draft = null;
            if ($applyOnly) {
                $draft = json_decode((string)$req->output_payload, true);
                if (!is_array($draft) || json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('Stored AI draft is invalid JSON.');
                }
            } else {
                $languagesTable = $this->fetchTable('Languages');
                $langs = $languagesTable->find('list', keyField: 'id', valueField: 'name')
                    ->orderByAsc('Languages.id')
                    ->toArray();
                $systemMessage = $promptService->getGenerationSystemPrompt($langs);

                $prompt = trim((string)($opts['prompt'] ?? ''));
                $questionCount = isset($opts['question_count']) && is_numeric($opts['question_count'])
                    ? (int)$opts['question_count']
                    : null;
                $prompt = $promptService->buildGenerationUserPrompt($prompt, $questionCount);

                $visionInputs = $this->buildVisionInputs((int)$req->id);
                $augmentedPrompt = $prompt;
                if ($visionInputs['document_blocks']) {
                    $augmentedPrompt .= "\n\nUse these uploaded documents as additional context. "
                        . "Prioritize factual consistency with this material:\n\n"
                        . implode("\n\n---\n\n", $visionInputs['document_blocks']);
                }

                $aiService = new AiGatewayService();
                $providerCallStartedIso = FrozenTime::now()->format('c');
                $providerCallStartedAt = microtime(true);
                $providerResponse = $aiService->generateFromOCR(
                    $augmentedPrompt,
                    $visionInputs['data_urls'],
                    $systemMessage,
                    0.7,
                    ['response_format' => ['type' => 'json_object']],
                );
                if (!$providerResponse->success) {
                    throw new RuntimeException((string)($providerResponse->error ?? 'AI request failed.'));
                }
                $providerDurationMs = (int)max(0, round((microtime(true) - $providerCallStartedAt) * 1000));

                $providerResult = $providerResponse->data;
                $rawProviderContent = (string)($providerResult['content'] ?? '');
                $draft = json_decode($rawProviderContent, true);
                if (!is_array($draft) || json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('AI returned invalid JSON.');
                }
            }

            $testId = $this->applyDraftFromRequest($req, $draft, !$applyOnly);

            if (!$applyOnly) {
                $req->output_payload = json_encode($draft, JSON_UNESCAPED_SLASHES);
                $req->duration_ms = $providerDurationMs;
                $this->applyProviderTelemetry($req, $providerResult, $providerDurationMs, $providerCallStartedIso);
            }

            $req->status = 'success';
            $req->finished_at = FrozenTime::now();
            $req->updated_at = FrozenTime::now();
            $req->error_code = null;
            $req->error_message = null;
            $aiRequests->save($req);

            return [
                'ai_request_id' => (int)$req->id,
                'test_id' => $testId,
                'status' => 'success',
                'apply_only' => $applyOnly,
            ];
        } catch (Throwable $e) {
            $req->status = 'failed';
            $req->finished_at = FrozenTime::now();
            $req->updated_at = FrozenTime::now();
            $msg = $e->getMessage();

            if ($providerDurationMs !== null) {
                $req->duration_ms = (int)$providerDurationMs;
            }
            $this->applyProviderTelemetry($req, $providerResult, $providerDurationMs, $providerCallStartedIso);

            if (is_string($rawProviderContent) && $rawProviderContent !== '') {
                $decodedRaw = json_decode($rawProviderContent, true);
                if (is_array($decodedRaw) && json_last_error() === JSON_ERROR_NONE) {
                    $req->output_payload = $rawProviderContent;
                } else {
                    $failedPayload = json_encode(['raw' => $rawProviderContent], JSON_UNESCAPED_SLASHES);
                    $req->output_payload = is_string($failedPayload) ? $failedPayload : null;
                }
            }

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
            } else {
                $req->error_code = 'AI_FAILED';
            }
            $req->error_message = $msg;
            $aiRequests->save($req);

            throw new RuntimeException($msg, 0, $e);
        }
    }

    /**
     * Validate and persist a draft into tests/questions/answers/translations.
     *
     * @param object $req AI request entity.
     * @param array<string, mixed> $draft Draft payload.
     * @param bool $persistDraftPayload Persist current draft into output_payload.
     * @return int
     */
    public function applyDraftFromRequest(object $req, array $draft, bool $persistDraftPayload = false): int
    {
        $aiRequests = $this->fetchTable('AiRequests');
        $opts = $this->decodeInputOptions($req->input_payload ?? null);

        $draftService = new AiQuizDraftService();
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
                $q['ai_request_id'] = (int)$req->id;
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

        $testsTable = $this->fetchTable('Tests');
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

        if ($persistDraftPayload) {
            $req->output_payload = json_encode($draft, JSON_UNESCAPED_SLASHES);
        }
        $req->test_id = (int)$test->id;
        $req->updated_at = FrozenTime::now();
        $aiRequests->save($req);

        return (int)$test->id;
    }

    /**
     * @param mixed $inputPayload
     * @return array<string, mixed>
     */
    private function decodeInputOptions(mixed $inputPayload): array
    {
        if (!is_string($inputPayload) || $inputPayload === '') {
            return [];
        }

        $decoded = json_decode($inputPayload, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param int $aiRequestId
     * @return array{data_urls: list<string>, document_blocks: list<string>}
     */
    private function buildVisionInputs(int $aiRequestId): array
    {
        $assetsTable = $this->fetchTable('AiRequestAssets');
        $assets = $assetsTable->find()
            ->where(['ai_request_id' => $aiRequestId])
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

            $extracted = $this->chunkExtractedText($extracted, $remainingChars);
            $remainingChars -= mb_strlen($extracted);

            $documentBlocks[] = "Source: {$rel} ({$mime})\n" . $extracted;
        }

        return [
            'data_urls' => $dataUrls,
            'document_blocks' => $documentBlocks,
        ];
    }

    /**
     * @param object $req
     * @param array<string, mixed>|null $providerResult
     * @param int|null $providerDurationMs
     * @param string|null $providerCallStartedIso
     * @return void
     */
    private function applyProviderTelemetry(
        object $req,
        ?array $providerResult,
        ?int $providerDurationMs,
        ?string $providerCallStartedIso,
    ): void {
        if (!is_array($providerResult)) {
            return;
        }

        $req->provider = isset($providerResult['provider']) ? (string)$providerResult['provider'] : null;
        $req->model = isset($providerResult['model']) ? (string)$providerResult['model'] : null;
        $req->prompt_tokens = isset($providerResult['prompt_tokens']) && is_numeric($providerResult['prompt_tokens'])
            ? (int)$providerResult['prompt_tokens']
            : null;
        $req->completion_tokens = isset($providerResult['completion_tokens'])
            && is_numeric($providerResult['completion_tokens'])
            ? (int)$providerResult['completion_tokens']
            : null;
        $req->total_tokens = isset($providerResult['total_tokens']) && is_numeric($providerResult['total_tokens'])
            ? (int)$providerResult['total_tokens']
            : null;
        $req->cost_usd = isset($providerResult['cost_usd']) && is_numeric($providerResult['cost_usd'])
            ? (float)$providerResult['cost_usd']
            : null;

        $meta = [];
        if (is_string($req->meta) && $req->meta !== '') {
            $decodedMeta = json_decode($req->meta, true);
            if (is_array($decodedMeta)) {
                $meta = $decodedMeta;
            }
        }
        if ($providerCallStartedIso !== null) {
            $meta['timing']['provider_call_started_at'] = $providerCallStartedIso;
        }
        $meta['timing']['duration_ms'] = $providerDurationMs;
        $meta['usage'] = is_array($providerResult['usage'] ?? null)
            ? $providerResult['usage']
            : [];
        $meta['provider_response'] = is_array($providerResult['response'] ?? null)
            ? $providerResult['response']
            : [];

        $metaJson = json_encode($meta, JSON_UNESCAPED_SLASHES);
        $req->meta = is_string($metaJson) ? $metaJson : null;
    }

    /**
     * Extract normalized text from a supported document type.
     *
     * @param string $absolutePath
     * @param string $mime
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
     * @param string $absolutePath
     * @return string
     */
    private function readPlainTextFile(string $absolutePath): string
    {
        $content = file_get_contents($absolutePath);

        return is_string($content) ? $content : '';
    }

    /**
     * @param string $absolutePath
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

        // Only extract body content — skip headers/footers to avoid noise.
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!is_string($xml) || $xml === '') {
            return '';
        }

        return trim(html_entity_decode(strip_tags($xml)));
    }

    /**
     * @param string $absolutePath
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
     * @param string $absolutePath
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
     * Split extracted text into paragraph chunks and return as much as fits within $maxChars.
     * Uses paragraph boundaries to avoid cutting mid-sentence.
     *
     * @param string $text
     * @param int $maxChars
     * @return string
     */
    private function chunkExtractedText(string $text, int $maxChars): string
    {
        if ($maxChars <= 0) {
            return '';
        }
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        $paragraphs = preg_split('/\n{2,}/', $text);
        if (!is_array($paragraphs)) {
            return mb_substr($text, 0, $maxChars);
        }

        $result = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            $separator = $result !== '' ? "\n\n" : '';
            if (mb_strlen($result) + mb_strlen($separator) + mb_strlen($paragraph) > $maxChars) {
                break;
            }
            $result .= $separator . $paragraph;
        }

        // Fallback: if even the first paragraph exceeds limit, hard-cut it.
        if ($result === '') {
            $result = mb_substr($text, 0, $maxChars);
        }

        return $result;
    }

    /**
     * @param string $text
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
     * @param mixed $value
     * @param bool $default
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
     * @param int $categoryId
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
     * @param int $difficultyId
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
}
