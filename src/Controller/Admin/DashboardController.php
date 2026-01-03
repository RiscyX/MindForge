<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AdminDashboardService;

class DashboardController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        /** @var \App\Model\Table\ActivityLogsTable $activityLogs */
        $activityLogs = $this->fetchTable('ActivityLogs');

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');

        /** @var \App\Model\Table\TestsTable $tests */
        $tests = $this->fetchTable('Tests');

        /** @var \App\Model\Table\QuestionsTable $questions */
        $questions = $this->fetchTable('Questions');

        /** @var \App\Model\Table\AiRequestsTable $aiRequests */
        $aiRequests = $this->fetchTable('AiRequests');

        $service = new AdminDashboardService($activityLogs, $users, $tests, $questions, $aiRequests);

        $stats = $service->getStats();
        $recentEvents = $service->getRecentSystemEvents(10);

        $this->set(compact('stats', 'recentEvents'));
    }
}
