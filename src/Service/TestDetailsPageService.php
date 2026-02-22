<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Builds view-model data for public and creator test detail pages.
 */
class TestDetailsPageService
{
    /**
     * Build public catalog details data (including user attempt metrics/favorite state).
     *
     * @param int $testId Test id.
     * @param int|null $languageId Resolved language id.
     * @param int|null $userId Authenticated user id.
     * @return array<string, mixed>
     */
    public function buildPublicDetails(int $testId, ?int $languageId, ?int $userId): array
    {
        $testsTable = TableRegistry::getTableLocator()->get('Tests');
        $attemptsTable = TableRegistry::getTableLocator()->get('TestAttempts');

        $test = $testsTable->find()
            ->where([
                'Tests.id' => $testId,
                'Tests.is_public' => true,
            ])
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

        if (!$test) {
            return ['test' => null];
        }

        $attemptsCount = 0;
        $finishedCount = 0;
        $bestAttempt = null;
        $lastAttempt = null;
        $attemptHistory = [];
        $isFavorited = false;

        if ($userId !== null) {
            $attemptsCount = (int)$attemptsTable->find()
                ->where([
                    'TestAttempts.user_id' => $userId,
                    'TestAttempts.test_id' => (int)$test->id,
                ])
                ->count();

            $finishedCount = (int)$attemptsTable->find()
                ->where([
                    'TestAttempts.user_id' => $userId,
                    'TestAttempts.test_id' => (int)$test->id,
                    'TestAttempts.finished_at IS NOT' => null,
                ])
                ->count();

            $bestAttempt = $attemptsTable->find()
                ->where([
                    'TestAttempts.user_id' => $userId,
                    'TestAttempts.test_id' => (int)$test->id,
                    'TestAttempts.finished_at IS NOT' => null,
                ])
                ->orderByDesc('TestAttempts.score')
                ->orderByDesc('TestAttempts.correct_answers')
                ->orderByDesc('TestAttempts.finished_at')
                ->orderByDesc('TestAttempts.id')
                ->first();

            $lastAttempt = $attemptsTable->find()
                ->where([
                    'TestAttempts.user_id' => $userId,
                    'TestAttempts.test_id' => (int)$test->id,
                    'TestAttempts.finished_at IS NOT' => null,
                ])
                ->orderByDesc('TestAttempts.finished_at')
                ->orderByDesc('TestAttempts.id')
                ->first();

            $attemptHistory = $attemptsTable->find()
                ->where([
                    'TestAttempts.user_id' => $userId,
                    'TestAttempts.test_id' => (int)$test->id,
                ])
                ->orderByDesc('TestAttempts.started_at')
                ->orderByDesc('TestAttempts.id')
                ->limit(20)
                ->all();

            $favoritesService = new UserFavoriteTestsService();
            $isFavorited = $favoritesService->isFavorited($userId, (int)$test->id);
        }

        return [
            'test' => $test,
            'attemptsCount' => $attemptsCount,
            'finishedCount' => $finishedCount,
            'bestAttempt' => $bestAttempt,
            'lastAttempt' => $lastAttempt,
            'attemptHistory' => $attemptHistory,
            'isFavorited' => $isFavorited,
        ];
    }

    /**
     * Build creator-owned details data.
     *
     * @param int $testId Test id.
     * @param int $creatorId Creator user id.
     * @param int|null $languageId Resolved language id.
     * @return array<string, mixed>
     */
    public function buildCreatorDetails(int $testId, int $creatorId, ?int $languageId): array
    {
        $testsTable = TableRegistry::getTableLocator()->get('Tests');

        $test = $testsTable->find()
            ->where([
                'Tests.id' => $testId,
                'Tests.created_by' => $creatorId,
            ])
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

        if (!$test) {
            return ['test' => null, 'stats' => null];
        }

        $stats = (new TestStatsService())->buildQuizStats((int)$test->id);

        return [
            'test' => $test,
            'stats' => $stats,
        ];
    }
}
