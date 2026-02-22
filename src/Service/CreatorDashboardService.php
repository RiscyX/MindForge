<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Provides aggregate metrics for the quiz creator dashboard.
 */
class CreatorDashboardService
{
    use LocatorAwareTrait;

    /**
     * Get dashboard metrics for a quiz creator.
     *
     * @param int $userId The creator's user ID.
     * @return array{totalQuizzes: int, publicQuizzes: int, totalAttempts: int, finishedAttempts: int}
     */
    public function getMetrics(int $userId): array
    {
        if ($userId <= 0) {
            return [
                'totalQuizzes' => 0,
                'publicQuizzes' => 0,
                'totalAttempts' => 0,
                'finishedAttempts' => 0,
            ];
        }

        $testsTable = $this->fetchTable('Tests');
        $attemptsTable = $this->fetchTable('TestAttempts');

        $totalQuizzes = (int)$testsTable->find()
            ->where(['Tests.created_by' => $userId])
            ->count();

        $publicQuizzes = (int)$testsTable->find()
            ->where(['Tests.created_by' => $userId, 'Tests.is_public' => true])
            ->count();

        $totalAttempts = (int)$attemptsTable->find()
            ->innerJoinWith('Tests', function ($q) use ($userId) {
                return $q->where(['Tests.created_by' => $userId]);
            })
            ->count();

        $finishedAttempts = (int)$attemptsTable->find()
            ->innerJoinWith('Tests', function ($q) use ($userId) {
                return $q->where(['Tests.created_by' => $userId]);
            })
            ->where(['TestAttempts.finished_at IS NOT' => null])
            ->count();

        return [
            'totalQuizzes' => $totalQuizzes,
            'publicQuizzes' => $publicQuizzes,
            'totalAttempts' => $totalAttempts,
            'finishedAttempts' => $finishedAttempts,
        ];
    }
}
