<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;
use RuntimeException;
use Throwable;

/**
 * Evaluates free-text quiz answers using AI.
 *
 * Eliminates duplication between TestsController::evaluateTextAnswerWithAi()
 * and Api\AttemptsController::evaluateTextAnswerWithAi().
 */
class AiAnswerEvaluationService
{
    /**
     * Evaluate whether a user's text answer is semantically correct.
     *
     * @param int $userId Authenticated user id.
     * @param object $question Question entity (with question_translations).
     * @param string $userAnswer The user's submitted text answer.
     * @param array<int, string> $acceptedAnswers Accepted answer texts.
     * @param string $sourceMedium Source medium for AI request logging (e.g. 'quiz_submit', 'mobile_app').
     * @param int|null $languageId Language id for AI request logging.
     * @param string|null $langCodeOrId Language code (e.g. 'hu', 'en') or null to default to 'en'.
     * @return bool Whether the answer is considered correct by AI.
     */
    public function evaluate(
        int $userId,
        object $question,
        string $userAnswer,
        array $acceptedAnswers,
        string $sourceMedium = 'quiz_submit',
        ?int $languageId = null,
        ?string $langCodeOrId = null,
    ): bool {
        $rateLimitService = new AiRateLimitService();
        $limit = $rateLimitService->getTextEvaluationLimitInfo($userId);
        if (!$limit['allowed']) {
            return false;
        }

        $questionText = '';
        if (!empty($question->question_translations)) {
            $questionText = trim((string)($question->question_translations[0]->content ?? ''));
        }

        $langCode = strtolower(trim($langCodeOrId ?? 'en'));
        $outputLanguage = str_starts_with($langCode, 'hu') ? 'Hungarian' : 'English';

        $payload = [
            'question_type' => 'text',
            'question' => $questionText,
            'accepted_answers' => array_values($acceptedAnswers),
            'user_answer' => $userAnswer,
            'instruction' => 'Decide if user answer should be accepted as semantically equivalent.',
            'output_language' => $outputLanguage,
        ];

        $prompt = (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $systemMessage = 'You validate short text quiz answers. Return ONLY strict JSON: '
            . '{"is_correct":true|false,"confidence":0..1,"reason":"short"}. '
            . 'Accept minor phrasing, synonyms, and grammar differences, but reject different meaning.';

        $aiRequests = TableRegistry::getTableLocator()->get('AiRequests');
        try {
            $ai = new AiGatewayService();
            $aiResponse = $ai->validateOutput(
                $prompt,
                $systemMessage,
                0.0,
                ['response_format' => ['type' => 'json_object']],
            );
            if (!$aiResponse->success) {
                throw new RuntimeException((string)($aiResponse->error ?? 'AI request failed.'));
            }
            $content = $aiResponse->content();

            $decoded = json_decode((string)$content, true);
            $isCorrect = is_array($decoded) && isset($decoded['is_correct'])
                ? (bool)$decoded['is_correct']
                : false;

            $outputPayload = json_encode(['raw' => (string)$content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $req = $aiRequests->newEntity([
                'user_id' => $userId,
                'language_id' => $languageId,
                'source_medium' => $sourceMedium,
                'source_reference' => 'question:' . (int)($question->id ?? 0),
                'type' => 'text_answer_evaluation',
                'input_payload' => $prompt,
                'output_payload' => is_string($outputPayload) ? $outputPayload : '{}',
                'status' => 'success',
            ]);
            $aiRequests->save($req);

            return $isCorrect;
        } catch (Throwable $e) {
            $errorPayload = json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $aiErrCode = 'AI_FAILED';
            if ($e instanceof AiServiceException) {
                $aiErrCode = $e->getErrorCode();
            }
            $req = $aiRequests->newEntity([
                'user_id' => $userId,
                'language_id' => $languageId,
                'source_medium' => $sourceMedium,
                'source_reference' => 'question:' . (int)($question->id ?? 0),
                'type' => 'text_answer_evaluation',
                'input_payload' => $prompt,
                'output_payload' => is_string($errorPayload) ? $errorPayload : '{}',
                'status' => 'failed',
                'error_code' => $aiErrCode,
                'error_message' => $e->getMessage(),
            ]);
            $aiRequests->save($req);

            return false;
        }
    }

    /**
     * Normalize text answers for plain comparison fallback.
     *
     * @param string $value
     * @return string
     */
    public function normalizeForCompare(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = preg_replace('/[\p{P}\p{S}]+/u', '', $value) ?? $value;

        return trim($value);
    }
}
