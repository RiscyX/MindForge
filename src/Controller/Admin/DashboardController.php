<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AdminDashboardService;
use App\Service\BulkActionService;
use Cake\Http\Response;

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

    /**
     * Bulk actions for recent system events.
     *
     * @return \Cake\Http\Response|null
     */
    public function bulk(): ?Response
    {
        $this->request->allowMethod(['post']);

        $action = (string)$this->request->getData('bulk_action');
        $bulkService = new BulkActionService();
        $ids = $bulkService->sanitizeIds($this->request->getData('ids'));

        if (!$ids) {
            $this->Flash->error(__('Select at least one item.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        if ($action !== 'delete') {
            $this->Flash->error(__('Invalid bulk action.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        $result = $bulkService->bulkDelete('ActivityLogs', $ids);

        if ($result['deleted'] > 0) {
            $this->Flash->success(__('Deleted {0} item(s).', $result['deleted']));
        }
        if ($result['failed'] > 0) {
            $this->Flash->error(__('Could not delete {0} item(s).', $result['failed']));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }
}
