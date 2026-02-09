<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AdminDashboardService;
use Cake\Http\Response;
use Throwable;

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

        /** @var \App\Model\Table\ActivityLogsTable $activityLogs */
        $activityLogs = $this->fetchTable('ActivityLogs');

        $action = (string)$this->request->getData('bulk_action');
        $rawIds = $this->request->getData('ids');
        $ids = is_array($rawIds) ? $rawIds : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0)));

        if (!$ids) {
            $this->Flash->error(__('Select at least one item.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        if ($action !== 'delete') {
            $this->Flash->error(__('Invalid bulk action.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        $deleted = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $entity = $activityLogs->get((string)$id);
                if ($activityLogs->delete($entity)) {
                    $deleted += 1;
                } else {
                    $failed += 1;
                }
            } catch (Throwable) {
                $failed += 1;
            }
        }

        if ($deleted > 0) {
            $this->Flash->success(__('Deleted {0} item(s).', $deleted));
        }
        if ($failed > 0) {
            $this->Flash->error(__('Could not delete {0} item(s).', $failed));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }
}
