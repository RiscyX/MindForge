<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\ActivityLog;
use App\Model\Entity\Question;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Exception;
use Throwable;

/**
 * Orchestrates AI-driven quiz generation used by TestsController::generateWithAi().
 */
class AiTestGenerationService
{
    /**
     * @param int|null $userId Authenticated user id.
     * @param string $prompt Raw user prompt.
     * @param int|null $questionCount Requested question count.
     * @param string $langCode Current UI language code.
     * @param array<string, mixed> $uploadedFiles Request uploaded files map.
     * @param string|null $ipAddress Client IP address.
     * @param string $userAgent Request user-agent.
     * @return array{success: bool, data?: array<string, mixed>, message?: string, error_code?: string, retried?: bool, debug?: string, limit_reached?: bool, resets_at?: string, http_status?: int}
     */
    public function generate(
        ?int $userId,
        string $prompt,
        ?int $questionCount,
        string $langCode,
        array $uploadedFiles,
        ?string $ipAddress,
        string $userAgent,
    ): array {
        $aiGenerationLimit = (new AiRateLimitService())->getGenerationLimitInfo($userId);
        if (!$aiGenerationLimit['allowed']) {
            return [
                'success' => false,
                'limit_reached' => true,
                'message' => __('AI generation limit reached. Limit resets tomorrow.'),
                'resets_at' => (string)($aiGenerationLimit['resets_at_iso'] ?? ''),
                'http_status' => 429,
            ];
        }

        if (trim($prompt) === '') {
            return [
                'success' => false,
                'message' => 'Empty prompt',
                'http_status' => 200,
            ];
        }

        try {
            $currentLanguageId = (new LanguageResolverService())->resolveId($langCode);

            $languages = TableRegistry::getTableLocator()->get('Languages')->find('list')->toArray();
            $promptService = new AiQuizPromptService();
            $systemMessage = $promptService->getGenerationSystemPrompt($languages);
            $finalPrompt = $promptService->buildGenerationUserPrompt($prompt, $questionCount);
            $documentContext = (new DocumentExtractorService())->buildContextForAi($uploadedFiles);
            if ($documentContext !== '') {
                $finalPrompt .= "\n\nUse these uploaded source documents as additional context. "
                    . "Prioritize factual consistency with them:\n\n"
                    . $documentContext;
            }

            $aiService = new AiGatewayService();
            $aiResponse = $aiService->generateQuizFromText(
                $finalPrompt,
                $systemMessage,
                0.45,
                ['response_format' => ['type' => 'json_object']],
            );
            if (!$aiResponse->success) {
                throw new Exception((string)($aiResponse->error ?? 'AI request failed.'));
            }
            $responseContent = $aiResponse->content();

            $json = json_decode($responseContent, true);

            $aiRequestsTable = TableRegistry::getTableLocator()->get('AiRequests');
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $currentLanguageId,
                'source_medium' => 'user_prompt',
                'source_reference' => 'test_generator',
                'type' => 'test_generation',
                'input_payload' => json_encode([
                    'prompt' => $prompt,
                    'question_count' => $questionCount,
                    'final_prompt' => $finalPrompt,
                ]),
                'output_payload' => $responseContent,
                'status' => 'success',
            ]);
            $aiRequestsTable->save($aiRequest);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON from AI',
                    'debug' => $responseContent,
                    'http_status' => 200,
                ];
            }

            $this->normalizeGeneratedQuestions($json, $languages);
            $this->logGeneratedTestActivity($userId, $ipAddress, $userAgent);

            return [
                'success' => true,
                'data' => $json,
                'http_status' => 200,
            ];
        } catch (Throwable $e) {
            $errorCode = 'AI_FAILED';
            $userMessage = $e->getMessage();
            if ($e instanceof AiServiceException) {
                $errorCode = $e->getErrorCode();
                $userMessage = $e->getUserMessage();
            }

            $aiRequestsTable = TableRegistry::getTableLocator()->get('AiRequests');
            $failedReq = $aiRequestsTable->newEmptyEntity();
            $failedReq = $aiRequestsTable->patchEntity($failedReq, [
                'user_id' => $userId,
                'source_medium' => 'user_prompt',
                'source_reference' => 'test_generator',
                'type' => 'test_generation',
                'input_payload' => json_encode([
                    'prompt' => $prompt,
                    'question_count' => $questionCount,
                ]),
                'output_payload' => $e->getMessage(),
                'status' => 'failed',
                'error_code' => $errorCode,
                'error_message' => $e->getMessage(),
            ]);
            $aiRequestsTable->save($failedReq);

            Log::error('AI generateWithAi failed: ' . $e->getMessage());

            $httpStatus = $e instanceof AiServiceException && $e->getHttpStatus() === 429 ? 429 : 500;

            return [
                'success' => false,
                'error_code' => $errorCode,
                'message' => $userMessage,
                'retried' => $e instanceof AiServiceException ? $e->wasRetried() : false,
                'http_status' => $httpStatus,
            ];
        }
    }

    /**
     * @param array<string, mixed> $json
     * @param array<int|string, mixed> $languages
     * @return void
     */
    private function normalizeGeneratedQuestions(array &$json, array $languages): void
    {
        if (!isset($json['questions']) || !is_array($json['questions'])) {
            return;
        }

        foreach ($json['questions'] as &$question) {
            if (!is_array($question)) {
                continue;
            }
            $question['source_type'] = 'ai';
            $qType = (string)($question['type'] ?? '');

            if (
                $qType === Question::TYPE_TEXT
                && (!isset($question['answers']) || !is_array($question['answers']) || !$question['answers'])
            ) {
                $fallbackAnswers = $question['accepted_answers'] ?? ($question['text_answers'] ?? null);
                if (is_array($fallbackAnswers)) {
                    $normalizedAnswers = [];
                    foreach ($fallbackAnswers as $candidate) {
                        if (is_string($candidate)) {
                            $text = trim($candidate);
                            if ($text === '') {
                                continue;
                            }
                            $translations = [];
                            foreach ($languages as $langId => $_langName) {
                                $translations[(string)$langId] = $text;
                            }
                            $normalizedAnswers[] = [
                                'is_correct' => true,
                                'source_type' => 'ai',
                                'translations' => $translations,
                            ];

                            continue;
                        }

                        if (is_array($candidate)) {
                            $translations = $candidate['translations'] ?? [];
                            if (!is_array($translations)) {
                                $translations = [];
                            }
                            $normalizedAnswers[] = [
                                'is_correct' => true,
                                'source_type' => 'ai',
                                'translations' => $translations,
                            ];
                        }
                    }
                    if ($normalizedAnswers) {
                        $question['answers'] = $normalizedAnswers;
                    }
                }
            }

            if (
                (string)($question['type'] ?? '') === Question::TYPE_MATCHING
                && isset($question['pairs'])
                && is_array($question['pairs'])
                && !isset($question['answers'])
            ) {
                $answers = [];
                $group = 1;
                foreach ($question['pairs'] as $pair) {
                    if (!is_array($pair)) {
                        continue;
                    }
                    $leftTranslations = $pair['left_translations'] ?? ($pair['left'] ?? []);
                    $rightTranslations = $pair['right_translations'] ?? ($pair['right'] ?? []);
                    if (!is_array($leftTranslations) || !is_array($rightTranslations)) {
                        continue;
                    }

                    $answers[] = [
                        'source_type' => 'ai',
                        'is_correct' => false,
                        'match_side' => 'left',
                        'match_group' => $group,
                        'translations' => $leftTranslations,
                    ];
                    $answers[] = [
                        'source_type' => 'ai',
                        'is_correct' => false,
                        'match_side' => 'right',
                        'match_group' => $group,
                        'translations' => $rightTranslations,
                    ];
                    $group += 1;
                }
                $question['answers'] = $answers;
            }

            if (isset($question['answers']) && is_array($question['answers'])) {
                foreach ($question['answers'] as &$answer) {
                    if (!is_array($answer)) {
                        continue;
                    }
                    $answer['source_type'] = 'ai';
                    if ($qType === Question::TYPE_MATCHING) {
                        $answer['is_correct'] = false;
                    } elseif ($qType === Question::TYPE_TEXT) {
                        $answer['is_correct'] = true;
                    }
                }
                unset($answer);
            }
        }
        unset($question);
    }

    /**
     * @param int|null $userId
     * @param string|null $ipAddress
     * @param string $userAgent
     * @return void
     */
    private function logGeneratedTestActivity(?int $userId, ?string $ipAddress, string $userAgent): void
    {
        $activityLogsTable = TableRegistry::getTableLocator()->get('ActivityLogs');
        $activityLog = $activityLogsTable->newEmptyEntity();
        $activityLog = $activityLogsTable->patchEntity($activityLog, [
            'user_id' => $userId,
            'action' => ActivityLog::TYPE_AI_GENERATED_TEST,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
        $activityLogsTable->save($activityLog);
    }
}
