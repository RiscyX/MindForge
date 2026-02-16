<?php
declare(strict_types=1);

namespace App\Controller\QuizCreator;

class DashboardController extends AppController
{
    /**
     * Render creator dashboard metrics.
     *
     * @return void
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : 0;

        $totalQuizzes = 0;
        $publicQuizzes = 0;
        $totalAttempts = 0;
        $finishedAttempts = 0;

        if ($userId > 0) {
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
        }

        $this->set('title', __('Quiz Creator'));
        $this->set(compact('totalQuizzes', 'publicQuizzes', 'totalAttempts', 'finishedAttempts'));
    }
}
