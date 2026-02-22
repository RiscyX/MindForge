<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Aggregates per-quiz statistics for a user (attempt counts, best scores).
 *
 * Extracted from Api\StatsController::quizzes().
 */
class UserQuizStatsService
{
    /**
     * Build quiz stats for a user: attempt counts, best finished attempt per quiz.
     *
     * @param int $userId
     * @param int|null $langId Language ID for translations (null = default).
     * @return list<array<string, mixed>> Sorted quiz stat items, ready for API serialization.
     */
    public function getQuizStats(int $userId, ?int $langId): array
    {
        $attemptsTable = TableRegistry::getTableLocator()->get('TestAttempts');

        // All attempts count (including unfinished) per test.
        $countRows = $attemptsTable->find()
            ->select([
                'test_id' => 'TestAttempts.test_id',
                'attempts_count' => $attemptsTable->find()->func()->count('TestAttempts.id'),
            ])
            ->where([
                'TestAttempts.user_id' => $userId,
                'TestAttempts.test_id IS NOT' => null,
            ])
            ->groupBy(['TestAttempts.test_id'])
            ->enableHydration(false)
            ->all()
            ->toList();

        $attemptsCountByTest = [];
        $testIds = [];
        foreach ($countRows as $row) {
            $tid = (int)($row['test_id'] ?? 0);
            $cnt = (int)($row['attempts_count'] ?? 0);
            if ($tid > 0 && $cnt > 0) {
                $attemptsCountByTest[$tid] = $cnt;
                $testIds[] = $tid;
            }
        }

        if (!$testIds) {
            return [];
        }

        $bestByTest = $this->findBestAttempts($attemptsTable, $userId, $testIds);
        $tests = $this->fetchTestMetadata(array_keys($attemptsCountByTest), $langId);

        return $this->buildItems($tests, $attemptsCountByTest, $bestByTest);
    }

    /**
     * Find the best finished attempt per test (by score, then correct_answers, then finished_at).
     *
     * @param \Cake\ORM\Table $attemptsTable
     * @param int $userId
     * @param list<int> $testIds
     * @return array<int, \Cake\Datasource\EntityInterface>
     */
    private function findBestAttempts(Table $attemptsTable, int $userId, array $testIds): array
    {
        $bestAttempts = $attemptsTable->find()
            ->where([
                'TestAttempts.user_id' => $userId,
                'TestAttempts.test_id IN' => $testIds,
                'TestAttempts.finished_at IS NOT' => null,
            ])
            ->orderByAsc('TestAttempts.test_id')
            ->orderByDesc('TestAttempts.score')
            ->orderByDesc('TestAttempts.correct_answers')
            ->orderByDesc('TestAttempts.finished_at')
            ->orderByDesc('TestAttempts.id')
            ->all()
            ->toArray();

        $bestByTest = [];
        foreach ($bestAttempts as $attempt) {
            $tid = (int)($attempt->test_id ?? 0);
            if ($tid <= 0) {
                continue;
            }
            if (!isset($bestByTest[$tid])) {
                $bestByTest[$tid] = $attempt;
            }
        }

        return $bestByTest;
    }

    /**
     * Fetch test metadata with translations.
     *
     * @param list<int> $testIds
     * @param int|null $langId
     * @return array<int, \Cake\Datasource\EntityInterface>
     */
    private function fetchTestMetadata(array $testIds, ?int $langId): array
    {
        $testsTable = TableRegistry::getTableLocator()->get('Tests');

        return $testsTable->find()
            ->where([
                'Tests.id IN' => $testIds,
                'Tests.is_public' => true,
            ])
            ->contain([
                'Categories.CategoryTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['CategoryTranslations.language_id' => $langId])
                    : $q,
                'Difficulties.DifficultyTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['DifficultyTranslations.language_id' => $langId])
                    : $q,
                'TestTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['TestTranslations.language_id' => $langId])
                    : $q,
            ])
            ->all()
            ->indexBy('id')
            ->toArray();
    }

    /**
     * Build and sort the final quiz stat items.
     *
     * @param array<int, \Cake\Datasource\EntityInterface> $tests
     * @param array<int, int> $attemptsCountByTest
     * @param array<int, \Cake\Datasource\EntityInterface> $bestByTest
     * @return list<array<string, mixed>>
     */
    private function buildItems(array $tests, array $attemptsCountByTest, array $bestByTest): array
    {
        $items = [];
        foreach ($tests as $testId => $test) {
            $translation = $test->test_translations[0] ?? null;
            $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
            $catTrans = $test->category?->category_translations[0] ?? null;

            $best = $bestByTest[(int)$testId] ?? null;

            $items[] = [
                'test' => [
                    'id' => (int)$test->id,
                    'title' => $translation?->title ?? 'Untitled Test',
                    'description' => $translation?->description ?? '',
                    'category' => $catTrans?->name ?? null,
                    'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
                    'difficulty' => $diffTrans?->name ?? null,
                    'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
                ],
                'attempts_count' => (int)($attemptsCountByTest[(int)$testId] ?? 0),
                'best_attempt' => $best ? [
                    'id' => (int)$best->id,
                    'finished_at' => $best->finished_at?->format('c'),
                    'score' => $best->score !== null ? (float)$best->score : null,
                    'total_questions' => $best->total_questions !== null ? (int)$best->total_questions : null,
                    'correct_answers' => $best->correct_answers !== null ? (int)$best->correct_answers : null,
                ] : null,
            ];
        }

        // Most relevant first: highest score, then most attempts.
        usort($items, static function (array $a, array $b): int {
            $as = (float)($a['best_attempt']['score'] ?? -1);
            $bs = (float)($b['best_attempt']['score'] ?? -1);
            if ($as !== $bs) {
                return $bs <=> $as;
            }

            $ac = (int)($a['attempts_count'] ?? 0);
            $bc = (int)($b['attempts_count'] ?? 0);

            return $bc <=> $ac;
        });

        return $items;
    }
}
