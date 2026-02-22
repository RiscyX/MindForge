<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Role;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

/**
 * Orchestrates quiz attempt lifecycle: starting, loading, aborting, and
 * submission guard checks.
 *
 * Extracted from TestsController::start(), take(), abort(), submit().
 */
class TestAttemptService
{
    use LocatorAwareTrait;

    /**
     * Start a new quiz attempt for the given user.
     *
     * @param int $testId Test id.
     * @param int $userId Authenticated user id.
     * @param int|null $roleId User role id.
     * @param int|null $languageId Resolved language id.
     * @return array{ok: bool, attempt_id?: int, error?: string}
     */
    public function start(int $testId, int $userId, ?int $roleId, ?int $languageId): array
    {
        $testsTable = $this->fetchTable('Tests');

        if ($roleId === Role::USER) {
            $test = $testsTable->find()
                ->where(['Tests.id' => $testId, 'Tests.is_public' => true])
                ->contain(['Categories', 'Difficulties'])
                ->first();
        } else {
            $test = $testsTable->find()
                ->where(['Tests.id' => $testId])
                ->contain(['Categories', 'Difficulties'])
                ->first();
        }

        if (!$test) {
            return ['ok' => false, 'error' => 'TEST_NOT_FOUND'];
        }

        $questionsCount = (int)$this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$test->id,
                'Questions.is_active' => true,
            ])
            ->count();

        if ($questionsCount <= 0) {
            return ['ok' => false, 'error' => 'NO_ACTIVE_QUESTIONS'];
        }

        $now = FrozenTime::now();
        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->newEmptyEntity();
        $attempt = $attempts->patchEntity($attempt, [
            'user_id' => $userId,
            'test_id' => (int)$test->id,
            'category_id' => $test->category_id,
            'difficulty_id' => $test->difficulty_id,
            'language_id' => $languageId,
            'started_at' => $now,
            'created_at' => $now,
            'total_questions' => $questionsCount,
            'correct_answers' => 0,
        ]);

        if (!$attempts->save($attempt)) {
            return ['ok' => false, 'error' => 'SAVE_FAILED'];
        }

        return ['ok' => true, 'attempt_id' => (int)$attempt->id];
    }

    /**
     * Load an attempt and verify ownership. Returns the attempt entity
     * or an error descriptor.
     *
     * @param int $attemptId Attempt id.
     * @param int $userId Authenticated user id.
     * @param array<string> $contain Optional contain associations.
     * @return array{ok: bool, attempt?: object, error?: string}
     */
    public function loadOwned(int $attemptId, int $userId, array $contain = []): array
    {
        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($attemptId, contain: $contain);

        if ((int)$attempt->user_id !== $userId) {
            return ['ok' => false, 'error' => 'FORBIDDEN'];
        }

        return ['ok' => true, 'attempt' => $attempt];
    }

    /**
     * Sync the user's currently selected language onto an in-progress attempt.
     *
     * @param object $attempt The attempt entity.
     * @param int|null $languageId The resolved language id.
     * @return void
     */
    public function syncLanguage(object $attempt, ?int $languageId): void
    {
        if ($languageId === null) {
            return;
        }

        if ((int)($attempt->language_id ?? 0) === $languageId) {
            return;
        }

        try {
            $attempt->language_id = $languageId;
            $this->fetchTable('TestAttempts')->save($attempt, ['validate' => false]);
        } catch (Throwable) {
            // Non-blocking: keep rendering with the selected language.
        }
    }

    /**
     * Fetch a test with language-filtered translations for the take/review UI.
     *
     * @param int $testId Test id.
     * @param int|null $languageId Resolved language id.
     * @param array<string> $extraContains Additional contain keys (e.g. 'Questions').
     * @return object|null
     */
    public function loadTestWithTranslations(int $testId, ?int $languageId, array $extraContains = []): ?object
    {
        $testsTable = $this->fetchTable('Tests');

        $contain = [
            'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                return $languageId ? $q->where(['CategoryTranslations.language_id' => $languageId]) : $q;
            },
            'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                return $languageId ? $q->where(['DifficultyTranslations.language_id' => $languageId]) : $q;
            },
            'TestTranslations' => function ($q) use ($languageId) {
                return $languageId ? $q->where(['TestTranslations.language_id' => $languageId]) : $q;
            },
        ];

        foreach ($extraContains as $key) {
            $contain[$key] = [];
        }

        return $testsTable->find()
            ->where(['Tests.id' => $testId])
            ->contain($contain)
            ->first();
    }

    /**
     * Abort (delete) an in-progress attempt after verifying ownership and state.
     *
     * @param int $attemptId Attempt id.
     * @param int $userId Authenticated user id.
     * @return array{ok: bool, test_id?: int, error?: string}
     */
    public function abort(int $attemptId, int $userId): array
    {
        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($attemptId);

        if ((int)$attempt->user_id !== $userId) {
            return ['ok' => false, 'error' => 'FORBIDDEN'];
        }

        if ($attempt->finished_at !== null) {
            return ['ok' => false, 'error' => 'ALREADY_FINISHED', 'test_id' => (int)($attempt->test_id ?? 0)];
        }

        $testId = (int)($attempt->test_id ?? 0);

        if ($attempts->delete($attempt)) {
            return ['ok' => true, 'test_id' => $testId];
        }

        return ['ok' => false, 'error' => 'DELETE_FAILED', 'test_id' => $testId];
    }

    /**
     * Check whether an attempt has already been submitted (has answers recorded).
     *
     * @param int $attemptId Attempt id.
     * @return bool
     */
    public function hasExistingAnswers(int $attemptId): bool
    {
        return (int)$this->fetchTable('TestAttemptAnswers')->find()
            ->where(['test_attempt_id' => $attemptId])
            ->count() > 0;
    }

    /**
     * Fetch explanation entities for a set of attempt answers, grouped by
     * attempt answer id with language-priority selection.
     *
     * @param array<int> $attemptAnswerIds List of TestAttemptAnswer ids.
     * @param int|null $languageId Preferred language id.
     * @return array<int, object> Indexed by test_attempt_answer_id.
     */
    public function loadExplanations(array $attemptAnswerIds, ?int $languageId): array
    {
        if (empty($attemptAnswerIds)) {
            return [];
        }

        $explanations = $this->fetchTable('AttemptAnswerExplanations')->find()
            ->where(['test_attempt_answer_id IN' => $attemptAnswerIds])
            ->all();

        $result = [];
        foreach ($explanations as $explanation) {
            $taaId = (int)($explanation->test_attempt_answer_id ?? 0);
            $langOfExplanation = $explanation->language_id !== null ? (int)$explanation->language_id : null;

            if (!isset($result[$taaId])) {
                $result[$taaId] = $explanation;
                continue;
            }

            $existingLang = $result[$taaId]->language_id !== null
                ? (int)$result[$taaId]->language_id
                : null;
            if ($languageId !== null && $langOfExplanation === $languageId && $existingLang !== $languageId) {
                $result[$taaId] = $explanation;
            }
        }

        return $result;
    }

    /**
     * Fetch a test with authorization-aware conditions and language-filtered translations.
     *
     * Used by the stats page where creators can only see their own tests.
     *
     * @param int $testId Test id.
     * @param int $userId Authenticated user id.
     * @param int|null $roleId User role id.
     * @param int|null $languageId Resolved language id.
     * @return object|null
     */
    public function loadTestForStats(int $testId, int $userId, ?int $roleId, ?int $languageId): ?object
    {
        $conditions = ['Tests.id' => $testId];
        if ($roleId === Role::CREATOR) {
            $conditions['Tests.created_by'] = $userId;
        }

        $testsTable = $this->fetchTable('Tests');

        return $testsTable->find()
            ->where($conditions)
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                    return $languageId ? $q->where(['CategoryTranslations.language_id' => $languageId]) : $q;
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                    return $languageId ? $q->where(['DifficultyTranslations.language_id' => $languageId]) : $q;
                },
                'TestTranslations' => function ($q) use ($languageId) {
                    return $languageId ? $q->where(['TestTranslations.language_id' => $languageId]) : $q;
                },
            ])
            ->first();
    }
}
