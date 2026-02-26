<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Exception;
use Throwable;

/**
 * Orchestrates AI-powered quiz translation.
 *
 * Extracted from TestsController::translateWithAi() (lines 2572-2756).
 */
class AiTranslationService
{
    /**
     * Translate quiz content into all configured languages using AI.
     *
     * @param int $sourceLanguageId Source language id.
     * @param array<string, mixed> $testSource Test data (title, description).
     * @param array<int, array<string, mixed>> $questionsSource Questions with answers.
     * @param int|null $userId Authenticated user id.
     * @param string|null $testId Test id for logging (null for unsaved).
     * @return array{success: bool, data?: array<string, mixed>, message?: string, error_code?: string, retried?: bool, debug?: string, http_status?: int}
     */
    public function translate(
        int $sourceLanguageId,
        array $testSource,
        array $questionsSource,
        ?int $userId = null,
        ?string $testId = null,
    ): array {
        $title = trim((string)($testSource['title'] ?? ''));
        $description = trim((string)($testSource['description'] ?? ''));

        if ($sourceLanguageId <= 0 || $title === '') {
            return [
                'success' => false,
                'message' => 'Missing source language or title.',
                'http_status' => 422,
            ];
        }

        try {
            $languagesQuery = TableRegistry::getTableLocator()->get('Languages')
                ->find()
                ->orderByAsc('Languages.id');
            $languages = [];
            foreach ($languagesQuery->all() as $lang) {
                $languages[(int)$lang->id] = (string)($lang->name ?? $lang->code ?? 'Lang ' . $lang->id);
            }

            if (!$languages) {
                throw new Exception('No languages configured.');
            }

            $sourcePayload = $this->buildSourcePayload(
                $sourceLanguageId,
                $title,
                $description,
                $questionsSource,
            );

            $promptService = new AiQuizPromptService();
            $systemMessage = $promptService->getTranslationSystemPrompt($languages, $sourceLanguageId);

            $prompt = json_encode($sourcePayload);
            if ($prompt === false) {
                throw new Exception('Failed to encode translation payload.');
            }

            $aiService = new AiGatewayService();
            $aiResponse = $aiService->generateQuizFromText(
                $prompt,
                $systemMessage,
                0.2,
                ['response_format' => ['type' => 'json_object']],
            );
            if (!$aiResponse->success) {
                throw new Exception((string)($aiResponse->error ?? 'AI request failed.'));
            }
            $responseContent = $aiResponse->content();

            $json = json_decode($responseContent, true);

            // Log successful AI request.
            $this->logAiRequest(
                $userId,
                $sourceLanguageId,
                $testId,
                $prompt,
                $responseContent,
                'success',
            );

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON from AI',
                    'debug' => $responseContent,
                    'http_status' => 500,
                ];
            }

            return [
                'success' => true,
                'data' => $json,
            ];
        } catch (Throwable $e) {
            $errorCode = 'AI_FAILED';
            $userMessage = $e->getMessage();
            if ($e instanceof AiServiceException) {
                $errorCode = $e->getErrorCode();
                $userMessage = $e->getUserMessage();
            }

            // Log failed AI request.
            $this->logAiRequest(
                $userId,
                $sourceLanguageId,
                $testId,
                json_encode(['source_language_id' => $sourceLanguageId, 'title' => $title]) ?: '{}',
                $e->getMessage(),
                'failed',
                $errorCode,
                $e->getMessage(),
            );

            Log::error('AI translateWithAi failed: ' . $e->getMessage());

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
     * Build the source payload for the AI translation prompt.
     *
     * @param int $sourceLanguageId
     * @param string $title
     * @param string $description
     * @param array<int, array<string, mixed>> $questionsSource
     * @return array<string, mixed>
     */
    private function buildSourcePayload(
        int $sourceLanguageId,
        string $title,
        string $description,
        array $questionsSource,
    ): array {
        $sourcePayload = [
            'source_language_id' => $sourceLanguageId,
            'test' => [
                'title' => $title,
                'description' => $description,
            ],
            'questions' => [],
        ];

        foreach ($questionsSource as $q) {
            if (!is_array($q)) {
                continue;
            }

            $qId = isset($q['id']) ? (int)$q['id'] : null;
            $qType = (string)($q['type'] ?? $q['question_type'] ?? '');
            $qContent = trim((string)($q['content'] ?? ''));
            if ($qContent === '') {
                continue;
            }

            $answersOut = [];
            $answers = $q['answers'] ?? [];
            if (is_array($answers)) {
                foreach ($answers as $a) {
                    if (!is_array($a)) {
                        continue;
                    }
                    $aId = isset($a['id']) ? (int)$a['id'] : null;
                    $aContent = trim((string)($a['content'] ?? ''));
                    $isCorrect = (bool)($a['is_correct'] ?? false);
                    $matchSide = trim((string)($a['match_side'] ?? ''));
                    $matchGroup = isset($a['match_group']) && is_numeric($a['match_group'])
                        ? (int)$a['match_group']
                        : null;
                    if ($aContent === '') {
                        continue;
                    }
                    $answersOut[] = [
                        'id' => $aId,
                        'is_correct' => $isCorrect,
                        'content' => $aContent,
                        'match_side' => $matchSide !== '' ? $matchSide : null,
                        'match_group' => $matchGroup,
                    ];
                }
            }

            $sourcePayload['questions'][] = [
                'id' => $qId,
                'type' => $qType,
                'content' => $qContent,
                'answers' => $answersOut,
            ];
        }

        return $sourcePayload;
    }

    /**
     * Log an AI request to the AiRequests table.
     *
     * @param int|null $userId
     * @param int $sourceLanguageId
     * @param string|null $testId
     * @param string $inputPayload
     * @param string $outputPayload
     * @param string $status
     * @param string|null $errorCode
     * @param string|null $errorMessage
     * @return void
     */
    private function logAiRequest(
        ?int $userId,
        int $sourceLanguageId,
        ?string $testId,
        string $inputPayload,
        string $outputPayload,
        string $status,
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): void {
        $aiRequestsTable = TableRegistry::getTableLocator()->get('AiRequests');
        $data = [
            'user_id' => $userId,
            'language_id' => $sourceLanguageId,
            'source_medium' => 'test_payload',
            'source_reference' => $testId ? 'test:' . $testId : 'test:unsaved',
            'type' => 'test_translation',
            'input_payload' => $this->normalizeJsonPayload($inputPayload),
            'output_payload' => $this->normalizeJsonPayload($outputPayload),
            'status' => $status,
        ];
        if ($errorCode !== null) {
            $data['error_code'] = $errorCode;
        }
        if ($errorMessage !== null) {
            $data['error_message'] = $errorMessage;
        }

        $entity = $aiRequestsTable->newEmptyEntity();
        $entity = $aiRequestsTable->patchEntity($entity, $data);
        $aiRequestsTable->save($entity);
    }

    /**
     * @param mixed $payload
     * @return string
     */
    private function normalizeJsonPayload(mixed $payload): string
    {
        if (is_string($payload)) {
            json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $payload;
            }

            $wrapped = json_encode(['raw' => $payload], JSON_UNESCAPED_SLASHES);

            return is_string($wrapped) ? $wrapped : '{}';
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '{}';
    }
}
