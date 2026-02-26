<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AiRequestStatsService;
use App\Service\BulkActionService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * AiRequests Controller
 *
 * @property \App\Model\Table\AiRequestsTable $AiRequests
 */
class AiRequestsController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setLayout('admin');
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

        $filterUserId = (int)$this->request->getQuery('user_id', 0);

        $hasTestId = $this->AiRequests->getSchema()->hasColumn('test_id');

        $contain = ['Users', 'Languages'];
        if ($hasTestId) {
            $contain[] = 'Tests';
        }

        $query = $this->AiRequests->find()
            ->contain($contain)
            ->orderByDesc('AiRequests.created_at');

        if ($filterUserId > 0) {
            $query->where(['AiRequests.user_id' => $filterUserId]);
        }

        $aiRequests = $query->all();

        $statsService = new AiRequestStatsService();
        $stats = $statsService->getStats();
        $userOptions = $statsService->getUserOptions();

        $this->set(compact('aiRequests', 'stats', 'filterUserId', 'userOptions'));
    }

    /**
     * Bulk actions for the index table.
     *
     * @return \Cake\Http\Response|null
     */
    public function bulk(): ?Response
    {
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

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

        $result = $bulkService->bulkDelete('AiRequests', $ids);

        if ($result['deleted'] > 0) {
            $this->Flash->success(__('Deleted {0} item(s).', $result['deleted']));
        }
        if ($result['failed'] > 0) {
            $this->Flash->error(__('Could not delete {0} item(s).', $result['failed']));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }

    /**
     * View method
     *
     * @param string|null $id Ai Request id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

        $hasTestId = $this->AiRequests->getSchema()->hasColumn('test_id');

        $contain = ['Users', 'Languages'];
        if ($hasTestId) {
            $contain[] = 'Tests';
        }

        $aiRequest = $this->AiRequests->get($id, contain: $contain);
        $this->set(compact('aiRequest'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

        $hasTestId = $this->AiRequests->getSchema()->hasColumn('test_id');

        $aiRequest = $this->AiRequests->newEmptyEntity();
        if ($this->request->is('post')) {
            $aiRequest = $this->AiRequests->patchEntity($aiRequest, $this->request->getData(), [
                'fields' => [
                    'user_id',
                    'language_id',
                    'source_medium',
                    'source_reference',
                    'type',
                    'prompt_version',
                    'provider',
                    'model',
                    'duration_ms',
                    'prompt_tokens',
                    'completion_tokens',
                    'total_tokens',
                    'cost_usd',
                    'input_payload',
                    'output_payload',
                    'status',
                    'test_id',
                    'started_at',
                    'finished_at',
                    'error_code',
                    'error_message',
                    'meta',
                ],
            ]);
            if ($this->AiRequests->save($aiRequest)) {
                $this->Flash->success(__('The ai request has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ai request could not be saved. Please, try again.'));
        }
        $users = $this->AiRequests->Users->find('list', limit: 200)->all();
        $tests = $hasTestId ? $this->AiRequests->Tests->find('list', limit: 200)->all() : [];
        $languages = $this->AiRequests->Languages->find('list', limit: 200)->all();
        $this->set(compact('aiRequest', 'users', 'tests', 'languages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Ai Request id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

        $hasTestId = $this->AiRequests->getSchema()->hasColumn('test_id');

        $aiRequest = $this->AiRequests->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $aiRequest = $this->AiRequests->patchEntity($aiRequest, $this->request->getData(), [
                'fields' => [
                    'user_id',
                    'language_id',
                    'source_medium',
                    'source_reference',
                    'type',
                    'prompt_version',
                    'provider',
                    'model',
                    'duration_ms',
                    'prompt_tokens',
                    'completion_tokens',
                    'total_tokens',
                    'cost_usd',
                    'input_payload',
                    'output_payload',
                    'status',
                    'test_id',
                    'started_at',
                    'finished_at',
                    'error_code',
                    'error_message',
                    'meta',
                ],
            ]);
            if ($this->AiRequests->save($aiRequest)) {
                $this->Flash->success(__('The ai request has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ai request could not be saved. Please, try again.'));
        }
        $users = $this->AiRequests->Users->find('list', limit: 200)->all();
        $tests = $hasTestId ? $this->AiRequests->Tests->find('list', limit: 200)->all() : [];
        $languages = $this->AiRequests->Languages->find('list', limit: 200)->all();
        $this->set(compact('aiRequest', 'users', 'tests', 'languages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Ai Request id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

        $this->request->allowMethod(['post', 'delete']);
        $aiRequest = $this->AiRequests->get($id);
        if ($this->AiRequests->delete($aiRequest)) {
            $this->Flash->success(__('The ai request has been deleted.'));
        } else {
            $this->Flash->error(__('The ai request could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    private function redirectToAdminPrefix(): ?Response
    {
        $prefix = (string)$this->request->getParam('prefix', '');
        if ($prefix === 'Admin') {
            return null;
        }

        $route = [
            'prefix' => 'Admin',
            'controller' => 'AiRequests',
            'action' => (string)$this->request->getParam('action', 'index'),
            'lang' => (string)$this->request->getParam('lang', 'en'),
        ];

        foreach ((array)$this->request->getParam('pass', []) as $arg) {
            $route[] = $arg;
        }

        $query = (array)$this->request->getQueryParams();
        if ($query !== []) {
            $route['?'] = $query;
        }

        return $this->redirect($route);
    }
}
