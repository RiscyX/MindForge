<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
use App\Service\AiQuestionGenerationPipelineService;
use App\Service\AiRequestDetailService;
use App\Service\AiTestGenerationRequestService;
use App\Service\LanguageResolverService;
use Cake\I18n\FrozenTime;
use OpenApi\Attributes as OA;
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

        $langCode = strtolower(trim((string)$this->request->getData('lang', $this->request->getQuery('lang', 'en'))));
        $languageId = (new LanguageResolverService())->resolveIdWithFallback(
            (int)($this->request->getData('language_id') ?? 0),
            $langCode,
        );

        $service = new AiTestGenerationRequestService();
        $result = $service->create(
            userId: $userId,
            prompt: $prompt,
            categoryId: $this->intOrNull($this->request->getData('category_id')),
            difficultyId: $this->intOrNull($this->request->getData('difficulty_id')),
            questionCount: $this->intOrNull($this->request->getData('question_count')),
            isPublic: $this->boolOrDefault($this->request->getData('is_public'), true),
            languageId: $languageId,
            uploadedFiles: $this->request->getUploadedFiles(),
        );

        if (!$result['ok']) {
            $errorMap = [
                AiTestGenerationRequestService::CODE_LIMIT_REACHED => [
                    429, 'AI_LIMIT_REACHED', 'AI generation limit reached. Limit resets tomorrow.',
                ],
                AiTestGenerationRequestService::CODE_CATEGORY_REQUIRED => [
                    422, 'CATEGORY_REQUIRED', 'Category is required.',
                ],
                AiTestGenerationRequestService::CODE_CATEGORY_INVALID => [
                    422, 'CATEGORY_INVALID', 'Category is invalid or inactive.',
                ],
                AiTestGenerationRequestService::CODE_DIFFICULTY_REQUIRED => [
                    422, 'DIFFICULTY_REQUIRED', 'Difficulty is required.',
                ],
                AiTestGenerationRequestService::CODE_DIFFICULTY_INVALID => [
                    422, 'DIFFICULTY_INVALID', 'Difficulty is invalid or inactive.',
                ],
                AiTestGenerationRequestService::CODE_REQUEST_CREATE_FAILED => [
                    500, 'REQUEST_CREATE_FAILED', 'Could not create AI request.',
                ],
                AiTestGenerationRequestService::CODE_UPLOAD_FAILED => [
                    422, 'UPLOAD_FAILED', $result['error_message'] ?? 'Failed to store uploaded assets.',
                ],
            ];

            // Rate-limit has special response shape
            if ($result['code'] === AiTestGenerationRequestService::CODE_LIMIT_REACHED) {
                $this->response = $this->response->withStatus(429);
                $this->jsonSuccess([
                    'ok' => false,
                    'error' => [
                        'code' => 'AI_LIMIT_REACHED',
                        'message' => 'AI generation limit reached. Limit resets tomorrow.',
                    ],
                    'resets_at' => $result['resets_at'] ?? FrozenTime::tomorrow()->format('c'),
                ]);

                return;
            }

            $err = $errorMap[$result['code']] ?? [500, 'REQUEST_CREATE_FAILED', 'Could not create AI request.'];
            $this->jsonError($err[0], $err[1], $err[2]);

            return;
        }

        $this->response = $this->response->withStatus(202);
        $this->jsonSuccess([
            'ai_request' => [
                'id' => $result['request_id'],
                'status' => $result['status'],
                'created_at' => $result['created_at'],
            ],
            'poll_url' => '/api/v1/creator/ai/requests/' . $result['request_id'],
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

        $langCode = strtolower(trim((string)$this->request->getData('lang', $this->request->getQuery('lang', 'en'))));
        $langId = (new LanguageResolverService())->resolveIdWithFallback(
            $req->language_id !== null ? (int)$req->language_id : 0,
            $langCode,
        );

        $detailService = new AiRequestDetailService();
        $payload = $detailService->buildViewPayload($req, $userId, $langId);

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

        $detailService = new AiRequestDetailService();
        $draftResult = $detailService->resolveDraft(
            $req,
            is_array($this->request->getData('draft')) ? $this->request->getData('draft') : null,
        );
        if (!$draftResult['ok']) {
            $this->jsonError(422, $draftResult['error_code'], $draftResult['error_message']);

            return;
        }
        $draft = $draftResult['draft'];
        $isClientDraft = is_array($this->request->getData('draft')) && !empty($this->request->getData('draft'));

        $pipeline = new AiQuestionGenerationPipelineService();
        try {
            $testId = $pipeline->applyDraftFromRequest(
                $req,
                $draft,
                $isClientDraft,
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
}
