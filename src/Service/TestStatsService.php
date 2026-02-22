<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Quiz statistics aggregation service.
 *
 * Extracted from TestsController::buildQuizStats() (lines 1630-1687).
 */
class TestStatsService
{
    /**
     * Build aggregate quiz statistics from attempts.
     *
     * @param int $testId Test id.
     * @return array{attempts: int, finished: int, completionRate: float, avgScore: float, bestScore: float, avgCorrectRate: float, uniqueUsers: int}
     */
    public function buildQuizStats(int $testId): array
    {
        $attemptsTable = TableRegistry::getTableLocator()->get('TestAttempts');
        $quizAttempts = $attemptsTable->find()->where(['TestAttempts.test_id' => $testId]);

        $attemptsCount = (int)(clone $quizAttempts)->count();
        $finishedCount = (int)(clone $quizAttempts)
            ->where(['TestAttempts.finished_at IS NOT' => null])
            ->count();

        $finishedWithScore = (clone $quizAttempts)
            ->where([
                'TestAttempts.finished_at IS NOT' => null,
                'TestAttempts.score IS NOT' => null,
            ]);

        $avgScoreRow = (clone $finishedWithScore)
            ->select(['avg_score' => $attemptsTable->find()->func()->avg('TestAttempts.score')])
            ->enableHydration(false)
            ->first();

        $bestScoreRow = (clone $finishedWithScore)
            ->select(['best_score' => $attemptsTable->find()->func()->max('TestAttempts.score')])
            ->enableHydration(false)
            ->first();

        $correctnessRow = (clone $quizAttempts)
            ->where([
                'TestAttempts.finished_at IS NOT' => null,
                'TestAttempts.correct_answers IS NOT' => null,
                'TestAttempts.total_questions IS NOT' => null,
                'TestAttempts.total_questions >' => 0,
            ])
            ->select([
                'sum_correct' => $attemptsTable->find()->func()->sum('TestAttempts.correct_answers'),
                'sum_total' => $attemptsTable->find()->func()->sum('TestAttempts.total_questions'),
            ])
            ->enableHydration(false)
            ->first();

        $uniqueUsers = (int)(clone $quizAttempts)
            ->select(['user_id'])
            ->distinct(['user_id'])
            ->count();

        $sumCorrect = (float)($correctnessRow['sum_correct'] ?? 0);
        $sumTotal = (float)($correctnessRow['sum_total'] ?? 0);

        return [
            'attempts' => $attemptsCount,
            'finished' => $finishedCount,
            'completionRate' => $attemptsCount > 0 ? $finishedCount / $attemptsCount * 100.0 : 0.0,
            'avgScore' => (float)($avgScoreRow['avg_score'] ?? 0.0),
            'bestScore' => (float)($bestScoreRow['best_score'] ?? 0.0),
            'avgCorrectRate' => $sumTotal > 0 ? $sumCorrect / $sumTotal * 100.0 : 0.0,
            'uniqueUsers' => $uniqueUsers,
        ];
    }
}
