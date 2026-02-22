<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
use App\Service\AiQuestionGenerationPipelineService;
use App\Service\AiQuizPromptService;
use App\Service\ImageUploadGuard;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

#[OA\Tag(name: 'CreatorAi', description: 'Creator AI quiz generation')]
class CreatorAiController extends AppController
{
    /**
     * Create asynchronous AI generation request.
     *
     * @return void
     */
    #[OA\Post(
        path: '/api/v1/creator/ai/test-generation',
        summary: 'Create an async AI test generation request (prompt + optional assets)',
        tags: ['CreatorAi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['prompt', 'category_id', 'difficulty_id'],
                    properties: [
                        new OA\Property(
                            property: 'prompt',
                            type: 'string',
                            example: 'Generate a beginner JavaScript quiz focused on arrays.',
                        ),
                        new OA\Property(property: 'category_id', type: 'integer', example: 2),
                        new OA\Property(property: 'difficulty_id', type: 'integer', example: 1),
                        new OA\Property(property: 'question_count', type: 'integer', nullable: true, example: 10),
                        new OA\Property(property: 'is_public', type: 'boolean', nullable: true, example: true),
                        new OA\Property(property: 'language_id', type: 'integer', nullable: true, example: 2),
                        new OA\Property(property: 'lang', type: 'string', nullable: true, example: 'en'),
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            description: 'Optional images (default max 6 files, 6 MB each; jpeg/png/webp).',
                            items: new OA\Items(type: 'string', format: 'binary'),
                        ),
                        new OA\Property(
                            property: 'documents',
                            type: 'array',
                            description: 'Optional documents (pdf/docx/odt/txt/md/csv/json/xml).',
                            items: new OA\Items(type: 'string', format: 'binary'),
                        ),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 202, description: 'Request accepted'),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 413, description: 'Payload too large'),
            new OA\Response(response: 422, description: 'Invalid input'),
            new OA\Response(response: 429, description: 'Daily limit reached'),
        ],
    )]
    public function createTestGeneration(): void
    {
        $this->request->allowMethod(['post']);

        $apiUser = $this->request->getAttribute('apiUser');
        $userId = $apiUser ? (int)$apiUser->id : null;
        $roleId = $apiUser ? (int)($apiUser->role_id ?? 0) : 0;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }
        if (!in_array($roleId, [Role::ADMIN, Role::CREATOR], true)) {
            $this->jsonError(403, 'FORBIDDEN', 'Only creators can generate quizzes.');

            return;
        }

        $prompt = trim((string)$this->request->getData('prompt', ''));
        if ($prompt === '') {
            $this->jsonError(422, 'PROMPT_REQUIRED', 'Prompt is required.');

            return;
        }

        $dailyLimit = max(1, (int)env('AI_TEST_GENERATION_DAILY_LIMIT', '20'));
        $todayStart = FrozenTime::today();
        $tomorrowStart = FrozenTime::tomorrow();
        $aiRequests = $this->fetchTable('AiRequests');
        $used = (int)$aiRequests->find()
            ->where([
                'user_id' => $userId,
                'type IN' => ['test_generation_async', 'test_generation'],
                'created_at >=' => $todayStart,
                'created_at <' => $tomorrowStart,
            ])
            ->count();
        if ($used >= $dailyLimit) {
            $this->response = $this->response->withStatus(429);
            $this->jsonSuccess([
                'ok' => false,
                'error' => [
                    'code' => 'AI_LIMIT_REACHED',
                    'message' => 'AI generation limit reached. Limit resets tomorrow.',
                ],
                'resets_at' => $tomorrowStart->format('c'),
            ]);

            return;
        }

        $languageId = $this->resolveLanguageId();
        $categoryId = $this->intOrNull($this->request->getData('category_id'));
        $difficultyId = $this->intOrNull($this->request->getData('difficulty_id'));
        $questionCount = $this->intOrNull($this->request->getData('question_count'));
        $isPublic = $this->boolOrDefault($this->request->getData('is_public'), true);

        if ($categoryId === null) {
            $this->jsonError(422, 'CATEGORY_REQUIRED', 'Category is required.');

            return;
        }
        if (!$this->isCategoryValidAndActive($categoryId)) {
            $this->jsonError(422, 'CATEGORY_INVALID', 'Category is invalid or inactive.');

            return;
        }

        if ($difficultyId === null) {
            $this->jsonError(422, 'DIFFICULTY_REQUIRED', 'Difficulty is required.');

            return;
        }
        if (!$this->isDifficultyValidAndActive($difficultyId)) {
            $this->jsonError(422, 'DIFFICULTY_INVALID', 'Difficulty is invalid or inactive.');

            return;
        }

        $now = FrozenTime::now();
        $promptService = new AiQuizPromptService();
        $req = $aiRequests->newEntity([
            'user_id' => $userId,
            'language_id' => $languageId,
            'source_medium' => 'mobile_app',
            'source_reference' => 'creator_ai_test_generation',
            'type' => 'test_generation_async',
            'prompt_version' => $promptService->getGenerationPromptVersion(),
            'input_payload' => json_encode([
                'prompt' => $prompt,
                'category_id' => $categoryId,
                'difficulty_id' => $difficultyId,
                'question_count' => $questionCount,
                'is_public' => $isPublic,
            ], JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (!$aiRequests->save($req)) {
            $this->jsonError(500, 'REQUEST_CREATE_FAILED', 'Could not create AI request.');

            return;
        }

        try {
            $this->saveUploadedAssets((int)$req->id);
        } catch (RuntimeException $e) {
            $req->status = 'failed';
            $req->error_code = 'UPLOAD_FAILED';
            $req->error_message = $e->getMessage();
            $req->updated_at = FrozenTime::now();
            $aiRequests->save($req);

            $this->jsonError(422, 'UPLOAD_FAILED', $e->getMessage());

            return;
        } catch (Throwable) {
            $req->status = 'failed';
            $req->error_code = 'UPLOAD_FAILED';
            $req->error_message = 'Failed to store uploaded assets.';
            $req->updated_at = FrozenTime::now();
            $aiRequests->save($req);

            $this->jsonError(500, 'UPLOAD_FAILED', 'Failed to store uploaded assets.');

            return;
        }

        $this->response = $this->response->withStatus(202);
        $this->jsonSuccess([
            'ai_request' => [
                'id' => (int)$req->id,
                'status' => (string)$req->status,
                'created_at' => $req->created_at?->format('c'),
            ],
            'poll_url' => '/api/v1/creator/ai/requests/' . (int)$req->id,
            'poll_interval_ms' => 2500,
        ]);
    }

    /**
     * Return AI request status and optional generated draft.
     *
     * @param string|null $id AI request id.
     * @return void
     */
    #[OA\Get(
        path: '/api/v1/creator/ai/requests/{id}',
        summary: 'Get status/result of an AI generation request',
        tags: ['CreatorAi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status payload'),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function view(?string $id = null): void
    {
        $this->request->allowMethod(['get']);

        $apiUser = $this->request->getAttribute('apiUser');
        $userId = $apiUser ? (int)$apiUser->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $aiRequests = $this->fetchTable('AiRequests');
        $req = $aiRequests->find()
            ->where(['AiRequests.id' => (int)$id, 'AiRequests.user_id' => $userId])
            ->first();
        if (!$req) {
            $this->jsonError(404, 'NOT_FOUND', 'AI request not found.');

            return;
        }

        $payload = [
            'ai_request' => [
                'id' => (int)$req->id,
                'status' => (string)$req->status,
                'test_id' => $req->test_id !== null ? (int)$req->test_id : null,
                'created_at' => $req->created_at?->format('c'),
                'updated_at' => $req->updated_at?->format('c'),
                'started_at' => $req->started_at?->format('c'),
                'finished_at' => $req->finished_at?->format('c'),
                'duration_ms' => $req->duration_ms !== null ? (int)$req->duration_ms : null,
                'prompt_tokens' => $req->prompt_tokens !== null ? (int)$req->prompt_tokens : null,
                'completion_tokens' => $req->completion_tokens !== null ? (int)$req->completion_tokens : null,
                'total_tokens' => $req->total_tokens !== null ? (int)$req->total_tokens : null,
                'cost_usd' => $req->cost_usd !== null ? (float)$req->cost_usd : null,
                'prompt_version' => $req->prompt_version,
                'error_code' => $req->error_code,
                'error_message' => $req->error_message,
            ],
        ];

        // If the worker already applied the draft to a Test, include basic metadata
        // so the mobile app can immediately show category/difficulty/title.
        if ($req->test_id !== null) {
            $langId = $req->language_id !== null ? (int)$req->language_id : $this->resolveLanguageId();
            $tests = $this->fetchTable('Tests');
            $test = $tests->find()
                ->where([
                    'Tests.id' => (int)$req->test_id,
                    'Tests.created_by' => $userId,
                ])
                ->contain([
                    'Categories.CategoryTranslations' => function ($q) use ($langId) {
                        return $langId ? $q->where(['CategoryTranslations.language_id' => $langId]) : $q;
                    },
                    'Difficulties.DifficultyTranslations' => function ($q) use ($langId) {
                        return $langId ? $q->where(['DifficultyTranslations.language_id' => $langId]) : $q;
                    },
                    'TestTranslations' => function ($q) use ($langId) {
                        return $langId ? $q->where(['TestTranslations.language_id' => $langId]) : $q;
                    },
                ])
                ->first();

            if ($test) {
                $tTrans = $test->test_translations[0] ?? null;
                $catTrans = $test->category?->category_translations[0] ?? null;
                $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;

                $payload['test'] = [
                    'id' => (int)$test->id,
                    'title' => $tTrans?->title ?? 'Untitled Test',
                    'description' => $tTrans?->description ?? '',
                    'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
                    'category' => $catTrans?->name ?? null,
                    'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
                    'difficulty' => $diffTrans?->name ?? null,
                    'number_of_questions' => $test->number_of_questions !== null
                        ? (int)$test->number_of_questions
                        : null,
                    'is_public' => (bool)$test->is_public,
                ];
            }
        }

        if ((string)$req->status === 'success' && is_string($req->output_payload) && $req->output_payload !== '') {
            $draft = json_decode($req->output_payload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($draft)) {
                $payload['draft'] = $draft;
            }
        }

        $this->jsonSuccess($payload);
    }

    /**
     * Apply successful AI draft and persist it as a test.
     *
     * @param string|null $id AI request id.
     * @return void
     */
    #[OA\Post(
        path: '/api/v1/creator/ai/requests/{id}/apply',
        summary: 'Apply a successful AI draft into a real Test',
        tags: ['CreatorAi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Applied'),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 409, description: 'Not ready'),
            new OA\Response(response: 422, description: 'Draft invalid'),
        ],
    )]
    public function apply(?string $id = null): void
    {
        $this->request->allowMethod(['post']);

        $apiUser = $this->request->getAttribute('apiUser');
        $userId = $apiUser ? (int)$apiUser->id : null;
        $roleId = $apiUser ? (int)($apiUser->role_id ?? 0) : 0;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }
        if (!in_array($roleId, [Role::ADMIN, Role::CREATOR], true)) {
            $this->jsonError(403, 'FORBIDDEN', 'Only creators can create quizzes.');

            return;
        }

        $aiRequests = $this->fetchTable('AiRequests');
        $req = $aiRequests->find()
            ->where(['AiRequests.id' => (int)$id, 'AiRequests.user_id' => $userId])
            ->first();
        if (!$req) {
            $this->jsonError(404, 'NOT_FOUND', 'AI request not found.');

            return;
        }

        if ($req->test_id !== null) {
            $this->jsonSuccess(['test_id' => (int)$req->test_id, 'already_applied' => true]);

            return;
        }

        if ((string)$req->status !== 'success') {
            $this->jsonError(409, 'NOT_READY', 'AI request is not ready yet.');

            return;
        }

        $decodedDraft = null;
        if (is_string($req->output_payload) && $req->output_payload !== '') {
            $decodedDraft = json_decode($req->output_payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedDraft = null;
            }
        }

        $draft = is_array($decodedDraft) ? $decodedDraft : null;

        // Allow clients (mobile/web) to apply an edited draft.
        // If provided, it must match the same schema as the AI output payload.
        $incomingDraft = $this->request->getData('draft');
        if (is_array($incomingDraft) && $incomingDraft) {
            $draft = $incomingDraft;
        }

        if (!is_array($draft)) {
            $this->jsonError(422, 'DRAFT_INVALID', 'AI draft is not valid JSON.');

            return;
        }

        $pipeline = new AiQuestionGenerationPipelineService();
        try {
            $testId = $pipeline->applyDraftFromRequest(
                $req,
                $draft,
                is_array($incomingDraft) && $incomingDraft,
            );
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            if (str_starts_with($message, 'CATEGORY_REQUIRED:')) {
                $this->jsonError(422, 'CATEGORY_REQUIRED', trim(substr($message, strlen('CATEGORY_REQUIRED:'))));

                return;
            }
            if (str_starts_with($message, 'CATEGORY_INVALID:')) {
                $this->jsonError(422, 'CATEGORY_INVALID', trim(substr($message, strlen('CATEGORY_INVALID:'))));

                return;
            }
            if (str_starts_with($message, 'DIFFICULTY_REQUIRED:')) {
                $this->jsonError(422, 'DIFFICULTY_REQUIRED', trim(substr($message, strlen('DIFFICULTY_REQUIRED:'))));

                return;
            }
            if (str_starts_with($message, 'DIFFICULTY_INVALID:')) {
                $this->jsonError(422, 'DIFFICULTY_INVALID', trim(substr($message, strlen('DIFFICULTY_INVALID:'))));

                return;
            }
            if (str_contains($message, 'generated quiz')) {
                $this->jsonError(500, 'TEST_SAVE_FAILED', 'Could not save generated quiz.');

                return;
            }

            $this->jsonError(422, 'DRAFT_INVALID', $message);

            return;
        } catch (Throwable) {
            $this->jsonError(500, 'TEST_SAVE_FAILED', 'Could not save generated quiz.');

            return;
        }

        $this->jsonSuccess(['test_id' => (int)$testId]);
    }

    /**
     * Resolve language id from request payload or route query.
     *
     * @return int|null
     */
    private function resolveLanguageId(): ?int
    {
        $languageId = $this->request->getData('language_id');
        if (is_numeric($languageId) && (int)$languageId > 0) {
            return (int)$languageId;
        }

        $langCode = strtolower(trim((string)$this->request->getData(
            'lang',
            $this->request->getQuery('lang', 'en'),
        )));
        $languages = $this->fetchTable('Languages');
        $lang = $languages->find()->where(['code LIKE' => $langCode . '%'])->first();
        if (!$lang) {
            $lang = $languages->find()->first();
        }

        return $lang?->id;
    }

    /**
     * Parse positive integer or null.
     *
     * @param mixed $v Value to parse.
     * @return int|null
     */
    private function intOrNull(mixed $v): ?int
    {
        return is_numeric($v) && (int)$v > 0 ? (int)$v : null;
    }

    /**
     * Convert mixed input to boolean with fallback default.
     *
     * @param mixed $value Raw value.
     * @param bool $default Fallback value.
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
     * Persist uploaded images/documents for AI request.
     *
     * @param int $aiRequestId AI request identifier.
     * @return void
     */
    private function saveUploadedAssets(int $aiRequestId): void
    {
        $files = $this->request->getUploadedFiles();
        $images = $files['images'] ?? null;
        $documents = $files['documents'] ?? null;

        $imageFiles = [];
        if (is_array($images)) {
            $imageFiles = $images;
        } elseif ($images instanceof UploadedFileInterface) {
            $imageFiles = [$images];
        }

        $documentFiles = [];
        if (is_array($documents)) {
            $documentFiles = $documents;
        } elseif ($documents instanceof UploadedFileInterface) {
            $documentFiles = [$documents];
        }

        if (!$imageFiles && !$documentFiles) {
            return;
        }

        $maxCount = (int)Configure::read('AI.maxImages', 6);
        if (count($imageFiles) > $maxCount) {
            throw new RuntimeException('Too many images. Max: ' . $maxCount);
        }

        $allowed = (array)Configure::read('AI.allowedImageMimeTypes', [
            'image/jpeg',
            'image/png',
            'image/webp',
        ]);
        $maxBytes = (int)Configure::read('AI.maxImageBytes', 6 * 1024 * 1024);
        $imageUploadGuard = new ImageUploadGuard();

        $maxDocumentCount = (int)Configure::read('AI.maxDocuments', 4);
        if (count($documentFiles) > $maxDocumentCount) {
            throw new RuntimeException('Too many documents. Max: ' . $maxDocumentCount);
        }
        $allowedDocumentMimes = (array)Configure::read('AI.allowedDocumentMimeTypes', []);
        $maxDocumentBytes = (int)Configure::read('AI.maxDocumentBytes', 8 * 1024 * 1024);

        $baseDir = rtrim(TMP, DS) . DS . 'ai_assets' . DS . (string)$aiRequestId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Upload directory is not writable.');
        }

        $assetsTable = $this->fetchTable('AiRequestAssets');
        $now = FrozenTime::now();

        foreach ($imageFiles as $file) {
            if (!$file instanceof UploadedFileInterface) {
                continue;
            }
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $mime = $imageUploadGuard->assertImageUpload($file, $allowed, $maxBytes);

            $ext = ImageUploadGuard::extensionForMime($mime);
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $absPath = $baseDir . DS . $name;
            $file->moveTo($absPath);

            $hash = hash_file('sha256', $absPath);
            $sha256 = $hash !== false ? $hash : null;
            // Store as a workspace-relative path for the worker.
            $relPath = 'tmp/ai_assets/' . $aiRequestId . '/' . $name;
            $size = (int)$file->getSize();

            $asset = $assetsTable->newEntity([
                'ai_request_id' => $aiRequestId,
                'storage_path' => $relPath,
                'mime_type' => $mime,
                'size_bytes' => $size,
                'sha256' => $sha256,
                'created_at' => $now,
            ]);
            if (!$assetsTable->save($asset)) {
                throw new RuntimeException('Failed to store image metadata.');
            }
        }

        foreach ($documentFiles as $file) {
            if (!$file instanceof UploadedFileInterface) {
                continue;
            }
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($file->getError() !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Document upload failed.');
            }

            $size = (int)$file->getSize();
            if ($size <= 0) {
                throw new RuntimeException('Uploaded document is empty.');
            }
            if ($size > $maxDocumentBytes) {
                throw new RuntimeException('Uploaded document is too large.');
            }

            $mime = $this->detectUploadedMime($file);
            if ($mime === '' || !in_array($mime, $allowedDocumentMimes, true)) {
                throw new RuntimeException('Unsupported document type: ' . ($mime !== '' ? $mime : 'unknown'));
            }

            $ext = $this->extensionForDocumentMime($mime);
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $absPath = $baseDir . DS . $name;
            $file->moveTo($absPath);

            $hash = hash_file('sha256', $absPath);
            $sha256 = $hash !== false ? $hash : null;
            $relPath = 'tmp/ai_assets/' . $aiRequestId . '/' . $name;

            $asset = $assetsTable->newEntity([
                'ai_request_id' => $aiRequestId,
                'storage_path' => $relPath,
                'mime_type' => $mime,
                'size_bytes' => $size,
                'sha256' => $sha256,
                'created_at' => $now,
            ]);
            if (!$assetsTable->save($asset)) {
                throw new RuntimeException('Failed to store document metadata.');
            }
        }
    }

    /**
     * Map allowed document MIME types to extension.
     *
     * @param string $mime MIME type.
     * @return string
     */
    private function extensionForDocumentMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'text/plain' => 'txt',
            'text/markdown' => 'md',
            'text/csv' => 'csv',
            'application/json' => 'json',
            'application/xml', 'text/xml' => 'xml',
            default => throw new RuntimeException('Unsupported document type: ' . $mime),
        };
    }

    /**
     * Detect uploaded file MIME type from content.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded file.
     * @return string
     */
    private function detectUploadedMime(UploadedFileInterface $file): string
    {
        try {
            $stream = $file->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $content = $stream->getContents();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
        } catch (Throwable) {
            $content = '';
        }

        $detected = '';
        if (is_string($content) && $content !== '') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $tmp = finfo_buffer($finfo, $content);
                finfo_close($finfo);
                if (is_string($tmp)) {
                    $detected = strtolower(trim($tmp));
                }
            }
        }

        if ($detected === '') {
            $detected = strtolower(trim((string)$file->getClientMediaType()));
        }

        return $detected;
    }
}
