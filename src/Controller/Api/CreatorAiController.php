<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
use App\Service\AiQuizDraftService;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use OpenApi\Attributes as OA;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

#[OA\Tag(name: 'CreatorAi', description: 'Creator AI quiz generation')]
class CreatorAiController extends AppController
{
    #[OA\Post(
        path: '/api/v1/creator/ai/test-generation',
        summary: 'Create an async AI test generation request (prompt + optional images)',
        tags: ['CreatorAi'],
        security: [['bearerAuth' => []]],
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

        $now = FrozenTime::now();
        $req = $aiRequests->newEntity([
            'user_id' => $userId,
            'language_id' => $languageId,
            'source_medium' => 'mobile_app',
            'source_reference' => 'creator_ai_test_generation',
            'type' => 'test_generation_async',
            'input_payload' => json_encode([
                'prompt' => $prompt,
                'category_id' => $categoryId,
                'difficulty_id' => $difficultyId,
                'question_count' => $questionCount,
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
            $this->saveUploadedImages((int)$req->id);
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
            $req->error_message = 'Failed to store uploaded images.';
            $req->updated_at = FrozenTime::now();
            $aiRequests->save($req);

            $this->jsonError(500, 'UPLOAD_FAILED', 'Failed to store uploaded images.');

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
        ]);
    }

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
                    'number_of_questions' => $test->number_of_questions !== null ? (int)$test->number_of_questions : null,
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

        $opts = [];
        if (is_string($req->input_payload) && $req->input_payload !== '') {
            $tmp = json_decode($req->input_payload, true);
            if (is_array($tmp)) {
                $opts = $tmp;
            }
        }

        $draftService = new AiQuizDraftService();
        try {
            $built = $draftService->validateAndBuildTestData($draft);
        } catch (RuntimeException $e) {
            $this->jsonError(422, 'DRAFT_INVALID', $e->getMessage());

            return;
        }

        // Persist edited draft for audit/debugging.
        if (is_array($incomingDraft) && $incomingDraft) {
            $req->output_payload = json_encode($draft, JSON_UNESCAPED_SLASHES);
        }

        $testData = $built['testData'];
        $testData['created_by'] = $userId;
        $testData['is_public'] = false;
        $testData['category_id'] = isset($opts['category_id']) && is_numeric($opts['category_id']) ? (int)$opts['category_id'] : null;
        $testData['difficulty_id'] = isset($opts['difficulty_id']) && is_numeric($opts['difficulty_id']) ? (int)$opts['difficulty_id'] : null;

        if (empty($testData['category_id'])) {
            $defaultCategory = $this->fetchTable('Categories')->find()
                ->select(['id'])
                ->where(['Categories.is_active' => true])
                ->orderByAsc('Categories.id')
                ->first();
            if ($defaultCategory) {
                $testData['category_id'] = (int)$defaultCategory->id;
            }
        }

        if (empty($testData['category_id'])) {
            $this->jsonError(422, 'CATEGORY_REQUIRED', 'No category selected and no active categories exist.');

            return;
        }

        // Propagate category_id/created_by into questions.
        if (!empty($testData['questions'])) {
            foreach ($testData['questions'] as &$q) {
                if ($testData['category_id']) {
                    $q['category_id'] = $testData['category_id'];
                }
                $q['created_by'] = $userId;
            }
        }

        $tests = $this->fetchTable('Tests');
        $test = $tests->newEmptyEntity();
        $test = $tests->patchEntity($test, $testData, [
            'associated' => [
                'Questions' => ['associated' => [
                    'Answers' => ['associated' => ['AnswerTranslations']],
                    'QuestionTranslations',
                ]],
                'TestTranslations',
            ],
        ]);

        if (!$tests->save($test)) {
            $this->jsonError(500, 'TEST_SAVE_FAILED', 'Could not save generated quiz.');

            return;
        }

        $req->test_id = (int)$test->id;
        $req->updated_at = FrozenTime::now();
        $aiRequests->save($req);

        $this->jsonSuccess(['test_id' => (int)$test->id]);
    }

    private function resolveLanguageId(): ?int
    {
        $languageId = $this->request->getData('language_id');
        if (is_numeric($languageId) && (int)$languageId > 0) {
            return (int)$languageId;
        }

        $langCode = strtolower(trim((string)$this->request->getData('lang', $this->request->getQuery('lang', 'en'))));
        $languages = $this->fetchTable('Languages');
        $lang = $languages->find()->where(['code LIKE' => $langCode . '%'])->first();
        if (!$lang) {
            $lang = $languages->find()->first();
        }

        return $lang?->id;
    }

    private function intOrNull(mixed $v): ?int
    {
        return is_numeric($v) && (int)$v > 0 ? (int)$v : null;
    }

    private function saveUploadedImages(int $aiRequestId): void
    {
        $files = $this->request->getUploadedFiles();
        $images = $files['images'] ?? null;

        $imageFiles = [];
        if (is_array($images)) {
            $imageFiles = $images;
        } elseif ($images instanceof UploadedFileInterface) {
            $imageFiles = [$images];
        }

        if (!$imageFiles) {
            return;
        }

        $maxCount = (int)Configure::read('AI.maxImages', 6);
        if (count($imageFiles) > $maxCount) {
            throw new RuntimeException('Too many images. Max: ' . $maxCount);
        }

        $allowed = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
        $maxBytes = (int)Configure::read('AI.maxImageBytes', 6 * 1024 * 1024);

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
            if ($file->getError() !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload failed.');
            }
            $mime = (string)$file->getClientMediaType();
            if (!in_array($mime, $allowed, true)) {
                throw new RuntimeException('Unsupported image type: ' . $mime);
            }
            $size = (int)$file->getSize();
            if ($size <= 0 || $size > $maxBytes) {
                throw new RuntimeException('Image is too large.');
            }

            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'bin',
            };
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $absPath = $baseDir . DS . $name;
            $file->moveTo($absPath);

            $sha256 = @hash_file('sha256', $absPath) ?: null;
            // Store as a workspace-relative path for the worker.
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
                throw new RuntimeException('Failed to store image metadata.');
            }
        }
    }
}
