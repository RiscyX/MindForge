<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Role;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

class OfflineAttemptSyncService
{
    use LocatorAwareTrait;

    /**
     * @param int $userId
     * @param array<int, mixed> $items
     * @param string $defaultLangCode
     * @return array{results: array<int, array<string, mixed>>, synced_count: int, duplicate_count: int, failed_count: int}
     */
    public function syncBatch(int $userId, array $items, string $defaultLangCode = 'en'): array
    {
        $results = [];
        $syncedCount = 0;
        $duplicateCount = 0;
        $failedCount = 0;

        $langResolver = new LanguageResolverService();
        $attemptService = new TestAttemptService();
        $questionService = new AttemptQuestionService();
        $submissionService = new AttemptSubmissionService();
        $aiEvalService = new AiAnswerEvaluationService();

        $syncKeys = $this->fetchTable('OfflineSyncAttempts');
        $attemptsTable = $this->fetchTable('TestAttempts');

        foreach ($items as $idx => $rawItem) {
            $item = is_array($rawItem) ? $rawItem : [];
            $clientAttemptId = $this->extractClientAttemptId($item, $idx);
            $testId = $this->extractTestId($item);
            $answers = $this->normalizeAnswersInput($item['answers'] ?? []);
            $languageIdRaw = $item['language_id'] ?? ($item['languageId'] ?? null);
            $langCode = strtolower(trim((string)($item['lang'] ?? ($item['language'] ?? $defaultLangCode))));
            $resolvedLangIdRaw = is_numeric($languageIdRaw) ? (int)$languageIdRaw : null;
            $langId = $langResolver->resolveIdWithFallback($resolvedLangIdRaw, $langCode);
            $resolvedLangCode = $langResolver->resolveCode((int)($langId ?? 0));

            if ($testId <= 0) {
                $results[] = $this->failedResult($clientAttemptId, 'INVALID_TEST_ID', 'Missing or invalid test_id.');
                $failedCount++;

                continue;
            }
            if (empty($answers)) {
                $results[] = $this->failedResult(
                    $clientAttemptId,
                    'INVALID_ANSWERS',
                    'Missing or invalid answers payload.',
                );
                $failedCount++;

                continue;
            }

            $existing = $syncKeys->find()
                ->where([
                    'user_id' => $userId,
                    'client_attempt_id' => $clientAttemptId,
                ])
                ->first();
            if ($existing !== null) {
                $results[] = [
                    'client_attempt_id' => $clientAttemptId,
                    'status' => 'duplicate',
                    'attempt_id' => $existing->test_attempt_id !== null ? (int)$existing->test_attempt_id : null,
                ];
                $duplicateCount++;

                continue;
            }

            $now = FrozenTime::now();
            $syncKey = $syncKeys->newEntity([
                'user_id' => $userId,
                'client_attempt_id' => $clientAttemptId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            try {
                if (!$syncKeys->save($syncKey)) {
                    $results[] = $this->failedResult(
                        $clientAttemptId,
                        'SYNC_KEY_SAVE_FAILED',
                        'Could not reserve sync key.',
                    );
                    $failedCount++;

                    continue;
                }
            } catch (Throwable) {
                $existingAfterConflict = $syncKeys->find()
                    ->where([
                        'user_id' => $userId,
                        'client_attempt_id' => $clientAttemptId,
                    ])
                    ->first();
                $results[] = [
                    'client_attempt_id' => $clientAttemptId,
                    'status' => 'duplicate',
                    'attempt_id' => $existingAfterConflict?->test_attempt_id !== null
                        ? (int)$existingAfterConflict->test_attempt_id
                        : null,
                ];
                $duplicateCount++;

                continue;
            }

            $start = $attemptService->start($testId, $userId, Role::USER, $langId);
            if (!$start['ok']) {
                $syncKeys->delete($syncKey);
                $results[] = $this->failedResult(
                    $clientAttemptId,
                    (string)($start['error'] ?? 'ATTEMPT_CREATE_FAILED'),
                    'Could not create attempt for offline sync.',
                );
                $failedCount++;

                continue;
            }

            $attemptId = (int)$start['attempt_id'];
            $attempt = $attemptsTable->get($attemptId);
            $questions = $questionService->listForTest($testId, $langId, includeCorrect: true);

            $submit = $submissionService->submit(
                $attempt,
                $questions,
                $answers,
                function (
                    object $question,
                    string $userAnswerText,
                    array $correctTextsRaw,
                ) use (
                    $userId,
                    $langId,
                    $resolvedLangCode,
                    $aiEvalService,
                ): bool {
                    return $aiEvalService->evaluate(
                        $userId,
                        $question,
                        $userAnswerText,
                        $correctTextsRaw,
                        'mobile_app_offline_sync',
                        (int)($langId ?? 0) > 0 ? (int)$langId : null,
                        $resolvedLangCode,
                    );
                },
            );

            if (!$submit['ok']) {
                $syncKeys->delete($syncKey);
                $attemptsTable->delete($attempt);
                $results[] = $this->failedResult(
                    $clientAttemptId,
                    'SUBMIT_FAILED',
                    'Could not submit offline attempt.',
                );
                $failedCount++;

                continue;
            }

            $syncKey->test_attempt_id = $attemptId;
            $syncKey->updated_at = FrozenTime::now();
            $syncKeys->save($syncKey, ['validate' => false]);

            $results[] = [
                'client_attempt_id' => $clientAttemptId,
                'status' => 'synced',
                'attempt_id' => $attemptId,
            ];
            $syncedCount++;
        }

        return [
            'results' => $results,
            'synced_count' => $syncedCount,
            'duplicate_count' => $duplicateCount,
            'failed_count' => $failedCount,
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @param string|int $fallbackIndex
     * @return string
     */
    private function extractClientAttemptId(array $item, int|string $fallbackIndex): string
    {
        $id = trim((string)($item['client_attempt_id'] ?? ($item['clientAttemptId'] ?? '')));
        if ($id !== '') {
            return $id;
        }

        return 'generated-' . (string)$fallbackIndex . '-' . sha1(json_encode($item) ?: 'empty');
    }

    /**
     * @param array<string, mixed> $item
     * @return int
     */
    private function extractTestId(array $item): int
    {
        $raw = $item['test_id'] ?? ($item['testId'] ?? null);
        if (is_numeric($raw)) {
            return (int)$raw;
        }

        $nestedTest = $item['test'] ?? ($item['offlineTest'] ?? null);
        if (is_array($nestedTest) && is_numeric($nestedTest['id'] ?? null)) {
            return (int)$nestedTest['id'];
        }

        return 0;
    }

    /**
     * @param mixed $rawAnswers
     * @return array<mixed>
     */
    private function normalizeAnswersInput(mixed $rawAnswers): array
    {
        if (!is_array($rawAnswers)) {
            return [];
        }

        $normalized = [];
        foreach ($rawAnswers as $key => $value) {
            $hasQuestionId = is_numeric($key) && is_array($value)
                && is_numeric($value['question_id'] ?? ($value['questionId'] ?? null));
            if ($hasQuestionId) {
                $qid = (int)($value['question_id'] ?? $value['questionId']);
                $normalized[$qid] = $this->normalizeAnswerValue($value);

                continue;
            }

            if (is_numeric($key)) {
                $normalized[(int)$key] = $this->normalizeAnswerValue($value);
            }
        }

        return $normalized;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeAnswerValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_key_exists('answer_id', $value)) {
            return $value['answer_id'];
        }
        if (array_key_exists('answerId', $value)) {
            return $value['answerId'];
        }
        if (array_key_exists('text', $value) || array_key_exists('pairs', $value)) {
            return $value;
        }
        if (array_key_exists('user_answer_text', $value)) {
            return ['text' => $value['user_answer_text']];
        }
        if (array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function failedResult(string $clientAttemptId, string $errorCode, string $errorMessage): array
    {
        return [
            'client_attempt_id' => $clientAttemptId,
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
        ];
    }
}
