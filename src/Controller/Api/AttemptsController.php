<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\AiAnswerEvaluationService;
use App\Service\AttemptOrderingService;
use App\Service\AttemptQuestionService;
use App\Service\AttemptReviewService;
use App\Service\AttemptSubmissionService;
use App\Service\AttemptViewPayloadService;
use App\Service\LanguageResolverService;
use App\Service\OfflineAttemptSyncService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Attempts', description: 'Quiz attempt endpoints')]
class AttemptsController extends AppController
{
    /**
     * Sync offline-completed attempts from mobile queue.
     *
     * @return void
     */
    #[OA\Post(
        path: '/api/v1/me/attempts/offline-sync',
        summary: 'Sync offline attempt results',
        tags: ['Attempts'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(type: 'object'),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Sync result'),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 422, description: 'Invalid payload'),
        ],
    )]
    public function offlineSync(): void
    {
        $this->request->allowMethod(['post']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $payload = $this->request->getData();
        $items = [];
        if (is_array($payload) && array_is_list($payload)) {
            $items = $payload;
        } elseif (is_array($payload)) {
            $rawItems = $payload['items'] ?? ($payload['attempts'] ?? null);
            if (is_array($rawItems)) {
                $items = $rawItems;
            }
        }

        if (empty($items)) {
            $this->jsonError(422, 'INVALID_SYNC_PAYLOAD', 'Expected a non-empty items array.');

            return;
        }

        $defaultLangCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $service = new OfflineAttemptSyncService();
        $result = $service->syncBatch($userId, $items, $defaultLangCode);

        $this->jsonSuccess($result);
    }

    /**
     * Get attempt payload for quiz taking.
     *
     * @param string|null $id Attempt id.
     * @return void
     */
    #[OA\Get(
        path: '/api/v1/attempts/{id}',
        summary: 'Get an attempt with questions (no correct answers)',
        tags: ['Attempts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Attempt payload',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'attempt', type: 'object'),
                        new OA\Property(property: 'test', type: 'object', nullable: true),
                        new OA\Property(property: 'questions', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Invalid attempt'),
        ],
    )]
    public function view(?string $id = null): void
    {
        $this->request->allowMethod(['get']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get((int)$id);
        if ((int)$attempt->user_id !== $userId) {
            $this->jsonError(403, 'FORBIDDEN', 'Attempt does not belong to user.');

            return;
        }

        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $langId = (new LanguageResolverService())->resolveIdWithFallback((int)($attempt->language_id ?? 0), $langCode);
        $testId = (int)($attempt->test_id ?? 0);
        if ($testId <= 0) {
            $this->jsonError(422, 'ATTEMPT_INVALID', 'Attempt does not have a test_id.');

            return;
        }

        $viewService = new AttemptViewPayloadService();
        $test = $viewService->loadTestWithTranslations($testId, $langId);

        $questionService = new AttemptQuestionService();
        $questions = $questionService->listForTest($testId, $langId, includeCorrect: false);
        $orderingService = new AttemptOrderingService();
        $questions = $orderingService->orderQuestions($questions, (int)$attempt->id);

        $payload = [
            'attempt' => $viewService->attemptSummary($attempt),
            'test' => $test ? $viewService->testSummary($test) : null,
            'questions' => array_map(
                fn($q) => $viewService->questionPayload($q, includeCorrect: false, attemptId: (int)$attempt->id),
                $questions,
            ),
        ];

        $this->jsonSuccess($payload);
    }

    /**
     * Submit answers for an in-progress attempt.
     *
     * @param string|null $id Attempt id.
     * @return void
     */
    #[OA\Post(
        path: '/api/v1/attempts/{id}/submit',
        summary: 'Submit answers for an attempt (finalizes score)',
        tags: ['Attempts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Submit result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'attempt', type: 'object'),
                        new OA\Property(property: 'submitted', type: 'boolean', example: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Invalid attempt or questions'),
            new OA\Response(response: 500, description: 'Submit failed'),
        ],
    )]
    public function submit(?string $id = null): void
    {
        $this->request->allowMethod(['post']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get((int)$id);
        if ((int)$attempt->user_id !== $userId) {
            $this->jsonError(403, 'FORBIDDEN', 'Attempt does not belong to user.');

            return;
        }

        $attemptAnswers = $this->fetchTable('TestAttemptAnswers');
        $existing = (int)$attemptAnswers->find()->where(['test_attempt_id' => (int)$attempt->id])->count();
        if ($attempt->finished_at !== null || $existing > 0) {
            // Idempotent: return the current attempt summary.
            $viewService = new AttemptViewPayloadService();
            $this->jsonSuccess([
                'attempt' => $viewService->attemptSummary($attempt),
                'submitted' => true,
            ]);

            return;
        }

        $testId = (int)($attempt->test_id ?? 0);
        if ($testId <= 0) {
            $this->jsonError(422, 'ATTEMPT_INVALID', 'Attempt does not have a test_id.');

            return;
        }

        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $langResolver = new LanguageResolverService();
        $langId = $langResolver->resolveIdWithFallback((int)($attempt->language_id ?? 0), $langCode);
        $questionService = new AttemptQuestionService();
        $questions = $questionService->listForTest($testId, $langId, includeCorrect: true);
        if (!$questions) {
            $this->jsonError(422, 'NO_ACTIVE_QUESTIONS', 'This test has no active questions.');

            return;
        }

        $input = $this->request->getData('answers');
        $input = is_array($input) ? $input : [];

        $langCode = $langResolver->resolveCode((int)$langId);
        $aiEvalService = new AiAnswerEvaluationService();
        $submissionService = new AttemptSubmissionService();
        $result = $submissionService->submit(
            $attempt,
            $questions,
            $input,
            function (
                object $question,
                string $userAnswerText,
                array $correctTextsRaw,
            ) use (
                $userId,
                $langId,
                $langCode,
                $aiEvalService,
            ): bool {
                return $aiEvalService->evaluate(
                    $userId,
                    $question,
                    $userAnswerText,
                    $correctTextsRaw,
                    'mobile_app',
                    (int)$langId > 0 ? (int)$langId : null,
                    $langCode,
                );
            },
        );
        if (!$result['ok']) {
            $this->jsonError(500, 'SUBMIT_FAILED', 'Could not submit attempt answers.');

            return;
        }

        $viewService2 = new AttemptViewPayloadService();
        $this->jsonSuccess([
            'attempt' => $viewService2->attemptSummary($attempt),
            'submitted' => true,
        ]);
    }

    /**
     * Return review payload for a finished attempt.
     *
     * @param string|null $id Attempt id.
     * @return void
     */
    #[OA\Get(
        path: '/api/v1/attempts/{id}/review',
        summary: 'Get a review payload for a finished attempt',
        tags: ['Attempts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Review payload',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'attempt', type: 'object'),
                        new OA\Property(property: 'review', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 409, description: 'Attempt not finished'),
            new OA\Response(response: 422, description: 'Invalid attempt'),
        ],
    )]
    public function review(?string $id = null): void
    {
        $this->request->allowMethod(['get']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get((int)$id);
        if ((int)$attempt->user_id !== $userId) {
            $this->jsonError(403, 'FORBIDDEN', 'Attempt does not belong to user.');

            return;
        }

        if ($attempt->finished_at === null) {
            $this->jsonError(409, 'ATTEMPT_NOT_FINISHED', 'Attempt is not finished yet.');

            return;
        }

        $testId = (int)($attempt->test_id ?? 0);
        if ($testId <= 0) {
            $this->jsonError(422, 'ATTEMPT_INVALID', 'Attempt does not have a test_id.');

            return;
        }

        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $langId = (new LanguageResolverService())->resolveIdWithFallback((int)($attempt->language_id ?? 0), $langCode);
        $questionService = new AttemptQuestionService();
        $questions = $questionService->listForTest($testId, $langId, includeCorrect: true);
        $attemptAnswers = $questionService->answersByQuestionId((int)$attempt->id);

        $viewService = new AttemptViewPayloadService();
        $reviewService = new AttemptReviewService();
        $reviewItems = $reviewService->buildReviewItems(
            $questions,
            $attemptAnswers,
            fn(object $question) => $viewService->questionPayload($question, includeCorrect: false),
        );

        $this->jsonSuccess([
            'attempt' => $viewService->attemptSummary($attempt),
            'review' => $reviewItems,
        ]);
    }
}
