<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Exception;
use Throwable;

/**
 * Orchestrates AI-powered answer explanations for quiz reviews.
 *
 * Extracted from TestsController::explainAnswer() (lines 1094-1391).
 * Uses the existing AttemptExplanationService for prompt building.
 */
class AiExplanationService
{
    /**
     * Generate or retrieve an AI explanation for a reviewed answer.
     *
     * @param int $userId Authenticated user id.
     * @param object $attempt TestAttempt entity.
     * @param object $attemptAnswer TestAttemptAnswer entity.
     * @param int|null $languageId Current UI language id.
     * @param string $lang Language code (e.g. 'hu', 'en').
     * @param bool $force Force regeneration even if cached.
     * @return array{success: bool, cached?: bool, cache_scope?: string|null, fallback?: bool, explanation?: string, limit_reached?: bool, message?: string, resets_at?: string, used?: int, limit?: int, remaining?: int, error_code?: string}
     */
    public function getOrGenerate(
        int $userId,
        object $attempt,
        object $attemptAnswer,
        ?int $languageId,
        string $lang,
        bool $force = false,
    ): array {
        $explanationsTable = TableRegistry::getTableLocator()->get('AttemptAnswerExplanations');
        $questionId = (int)$attemptAnswer->question_id;

        // 1. Look for existing explanation on this attempt answer.
        $existingExplanation = null;
        $cacheScope = null;

        if ($languageId !== null) {
            $existingExplanation = $explanationsTable->find()
                ->where([
                    'test_attempt_answer_id' => (int)$attemptAnswer->id,
                    'language_id' => $languageId,
                ])
                ->first();
        }
        if ($existingExplanation === null) {
            $existingExplanation = $explanationsTable->find()
                ->where(['test_attempt_answer_id' => (int)$attemptAnswer->id])
                ->orderByDesc('id')
                ->first();
        }

        if ($existingExplanation) {
            $cacheScope = 'attempt';
        }

        // 2. Cross-user cache lookup.
        if (!$force && $existingExplanation === null) {
            $crossUserQuery = $explanationsTable->find()
                ->innerJoinWith('TestAttemptAnswers', function ($q) use ($questionId, $attemptAnswer) {
                    return $q->where([
                        'TestAttemptAnswers.question_id' => $questionId,
                        'TestAttemptAnswers.is_correct' => (bool)$attemptAnswer->is_correct,
                    ]);
                })
                ->where([
                    'AttemptAnswerExplanations.test_attempt_answer_id !=' => (int)$attemptAnswer->id,
                ])
                ->orderByDesc('AttemptAnswerExplanations.id');

            if ($languageId !== null) {
                $crossUserQuery->where(['AttemptAnswerExplanations.language_id' => $languageId]);
            }

            $crossUserExplanation = $crossUserQuery->first();
            if ($crossUserExplanation !== null) {
                $cacheScope = 'question';

                $now = FrozenTime::now();
                $reuseEntity = $explanationsTable->newEmptyEntity();
                $reuseEntity = $explanationsTable->patchEntity($reuseEntity, [
                    'test_attempt_answer_id' => (int)$attemptAnswer->id,
                    'language_id' => $languageId,
                    'ai_request_id' => $crossUserExplanation->ai_request_id,
                    'source' => 'ai_cache',
                    'explanation_text' => (string)$crossUserExplanation->explanation_text,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $explanationsTable->save($reuseEntity);

                $existingExplanation = $reuseEntity;
            }
        }

        // 3. Return cached if available and not forced.
        if ($existingExplanation && !$force) {
            return [
                'success' => true,
                'cached' => true,
                'cache_scope' => $cacheScope,
                'explanation' => (string)$existingExplanation->explanation_text,
            ];
        }

        // 4. Check rate limit.
        $rateLimitService = new AiRateLimitService();
        $aiExplanationLimit = $rateLimitService->getExplanationLimitInfo($userId);
        if (!$aiExplanationLimit['allowed']) {
            return [
                'success' => false,
                'limit_reached' => true,
                'message' => 'AI explanation limit reached for today.',
                'resets_at' => $aiExplanationLimit['resets_at_iso'],
                'used' => $aiExplanationLimit['used'],
                'limit' => $aiExplanationLimit['limit'],
                'remaining' => $aiExplanationLimit['remaining'],
            ];
        }

        // 5. Load question entity.
        $questionService = new AttemptQuestionService();
        $question = $questionService->getForTest(
            $questionId,
            (int)$attempt->test_id,
            $languageId,
            includeCorrect: true,
        );
        if (!$question) {
            return [
                'success' => false,
                'message' => 'Question not found.',
            ];
        }

        // 6. Build prompt via existing AttemptExplanationService.
        $promptService = new AttemptExplanationService();
        $promptContext = $promptService->buildPromptContext($question, $attemptAnswer, $lang);
        $questionType = $promptContext['question_type'];
        $userInfo = $promptContext['user_info'];
        $correctInfo = $promptContext['correct_info'];
        $promptJson = $promptContext['prompt_json'];
        $systemMessage = $promptContext['system_message'];

        $aiRequestsTable = TableRegistry::getTableLocator()->get('AiRequests');

        // 7. Call AI.
        try {
            $aiService = new AiGatewayService();
            $aiResponse = $aiService->validateOutput(
                $promptJson,
                $systemMessage,
                0.2,
                ['response_format' => ['type' => 'json_object']],
            );
            if (!$aiResponse->success) {
                throw new Exception((string)($aiResponse->error ?? 'AI request failed.'));
            }
            $responseContent = $aiResponse->content();

            $decoded = json_decode((string)$responseContent, true);
            $explanationText = '';
            if (is_array($decoded)) {
                $explanationText = trim((string)($decoded['explanation'] ?? ''));
            }
            if ($explanationText === '') {
                $explanationText = trim((string)$responseContent);
            }
            if ($explanationText === '') {
                throw new Exception('Empty explanation from AI.');
            }

            // Log successful AI request.
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $aiRequestOutputPayload = json_encode(
                ['raw' => (string)$responseContent],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $languageId,
                'source_medium' => 'attempt_review',
                'source_reference' => 'attempt:' . (int)$attempt->id . ':question:' . (int)$question->id,
                'type' => 'attempt_answer_explanation',
                'input_payload' => $promptJson,
                'output_payload' => is_string($aiRequestOutputPayload) ? $aiRequestOutputPayload : '{}',
                'status' => 'success',
            ]);
            $aiRequestsTable->save($aiRequest);

            // Save/update explanation entity.
            $this->saveExplanation(
                $explanationsTable,
                $existingExplanation,
                (int)$attemptAnswer->id,
                $languageId,
                $aiRequest?->id,
                'ai',
                $explanationText,
            );

            return [
                'success' => true,
                'cached' => false,
                'explanation' => $explanationText,
            ];
        } catch (Throwable $e) {
            // Fallback explanation.
            $fallbackExplanation = $promptService->buildFallbackExplanation(
                $questionType,
                $userInfo,
                $correctInfo,
                (bool)$attemptAnswer->is_correct,
                $lang,
            );

            // Log failed AI request.
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $errorPayload = json_encode([
                'error' => $e->getMessage(),
                'trace_hint' => 'attempt_answer_explanation',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $aiErrorCode = 'AI_FAILED';
            if ($e instanceof AiServiceException) {
                $aiErrorCode = $e->getErrorCode();
            }
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $languageId,
                'source_medium' => 'attempt_review',
                'source_reference' => 'attempt:' . (int)$attempt->id . ':question:' . (int)$question->id,
                'type' => 'attempt_answer_explanation',
                'input_payload' => $promptJson,
                'output_payload' => is_string($errorPayload) ? $errorPayload : '{}',
                'status' => 'failed',
                'error_code' => $aiErrorCode,
                'error_message' => $e->getMessage(),
            ]);
            $aiRequestsTable->save($aiRequest);

            // Save fallback explanation.
            $this->saveExplanation(
                $explanationsTable,
                $existingExplanation,
                (int)$attemptAnswer->id,
                $languageId,
                $aiRequest?->id,
                'fallback',
                $fallbackExplanation,
            );

            return [
                'success' => true,
                'cached' => false,
                'fallback' => true,
                'explanation' => $fallbackExplanation,
            ];
        }
    }

    /**
     * Save or update an explanation entity.
     *
     * @param \Cake\ORM\Table $table
     * @param object|null $existing
     * @param int $attemptAnswerId
     * @param int|null $languageId
     * @param int|null $aiRequestId
     * @param string $source
     * @param string $explanationText
     * @return void
     */
    private function saveExplanation(
        Table $table,
        ?object $existing,
        int $attemptAnswerId,
        ?int $languageId,
        ?int $aiRequestId,
        string $source,
        string $explanationText,
    ): void {
        $now = FrozenTime::now();
        $entity = $existing ?: $table->newEmptyEntity();

        $patchData = [
            'test_attempt_answer_id' => $attemptAnswerId,
            'language_id' => $languageId,
            'ai_request_id' => $aiRequestId,
            'source' => $source,
            'explanation_text' => $explanationText,
            'updated_at' => $now,
        ];
        if (!$existing) {
            $patchData['created_at'] = $now;
        }

        $entity = $table->patchEntity($entity, $patchData);
        $table->save($entity);
    }
}
