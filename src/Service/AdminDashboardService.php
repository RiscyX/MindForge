<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\ActivityLogsTable;
use App\Model\Table\AiRequestsTable;
use App\Model\Table\QuestionsTable;
use App\Model\Table\TestsTable;
use App\Model\Table\UsersTable;
use Cake\I18n\FrozenTime;

class AdminDashboardService
{
    /**
     * @param \App\Model\Table\ActivityLogsTable $activityLogs
     * @param \App\Model\Table\UsersTable $users
     * @param \App\Model\Table\TestsTable $tests
     * @param \App\Model\Table\QuestionsTable $questions
     * @param \App\Model\Table\AiRequestsTable $aiRequests
     */
    public function __construct(
        private ActivityLogsTable $activityLogs,
        private UsersTable $users,
        private TestsTable $tests,
        private QuestionsTable $questions,
        private AiRequestsTable $aiRequests,
    ) {
    }

    /**
     * Fetch dashboard stats.
     *
     * @return array{
     *   totalUsers:int,
     *   activeUsers:int,
     *   totalTests:int,
     *   totalQuestions:int,
     *   todaysLogins:int,
     *   aiRequests:int
     * }
     */
    public function getStats(): array
    {
        $cutoff = FrozenTime::now()->subHours(24);

        return [
            'totalUsers' => (int)$this->users->find()->count(),
            'activeUsers' => (int)$this->users->find()->where(['Users.is_active' => 1])->count(),
            'totalTests' => (int)$this->tests->find()->count(),
            'totalQuestions' => (int)$this->questions->find()->count(),
            'todaysLogins' => (int)$this->users->find()->where(['Users.last_login_at >=' => $cutoff])->count(),
            'aiRequests' => (int)$this->aiRequests->find()->count(),
        ];
    }

    /**
     * Fetch recent system events from ActivityLogs.
     *
     * @param int $limit
     * @return list<array{ts:string,type:string,user:string,details:string,status:string}>
     */
    public function getRecentSystemEvents(int $limit = 10): array
    {
        $limit = max(1, $limit);

        $query = $this->activityLogs->find()
            ->select([
                'id',
                'user_id',
                'action',
                'ip_address',
                'created_at',
            ])
            ->contain([
                'Users' => function ($q) {
                    return $q->select(['id', 'email', 'username']);
                },
            ])
            ->orderByDesc('ActivityLogs.created_at')
            ->limit($limit);

        $events = [];
        foreach ($query as $log) {
            $createdAt = $log->created_at;
            $ts = $createdAt instanceof FrozenTime ? $createdAt->format('Y-m-d H:i:s') : (string)$createdAt;

            $type = $this->formatEventType((string)$log->action);
            $status = $this->statusFromAction((string)$log->action);

            $user = 'System';
            if ($log->user !== null) {
                $user = (string)($log->user->email ?? $log->user->username ?? 'User');
            } elseif ($log->user_id !== null) {
                $user = 'User #' . (string)$log->user_id;
            }

            $details = $this->detailsFromLog((string)$log->action, (string)($log->ip_address ?? ''));

            $events[] = [
                'ts' => $ts,
                'type' => $type,
                'user' => $user,
                'details' => $details,
                'status' => $status,
            ];
        }

        return $events;
    }

    /**
     * @param string $action
     * @return string
     */
    private function formatEventType(string $action): string
    {
        if ($action === 'login') {
            return 'User Login';
        }
        if ($action === 'logout') {
            return 'User Logout';
        }
        if (str_starts_with($action, 'login_failed')) {
            return 'Failed Login';
        }

        return $action;
    }

    /**
     * @param string $action
     * @return string
     */
    private function statusFromAction(string $action): string
    {
        if ($action === 'login' || $action === 'logout') {
            return 'Success';
        }
        if (str_starts_with($action, 'login_failed')) {
            return 'Failed';
        }

        return 'Success';
    }

    /**
     * @param string $action
     * @param string $ipAddress
     * @return string
     */
    private function detailsFromLog(string $action, string $ipAddress): string
    {
        if (str_starts_with($action, 'login_failed:')) {
            $reason = trim(substr($action, strlen('login_failed:')));

            return $reason !== '' ? $reason : 'Invalid credentials';
        }

        if ($ipAddress !== '') {
            return 'IP: ' . $ipAddress;
        }

        return '';
    }
}
