<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BulkActionService;
use App\Service\DeviceLogStatsService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * DeviceLogs Controller
 *
 * @property \App\Model\Table\DeviceLogsTable $DeviceLogs
 */
class DeviceLogsController extends AppController
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

        $filters = [
            'user_id' => (string)$this->request->getQuery('user_id', ''),
            'device_type' => (string)$this->request->getQuery('device_type', ''),
            'from' => (string)$this->request->getQuery('from', ''),
            'to' => (string)$this->request->getQuery('to', ''),
        ];

        $statsService = new DeviceLogStatsService();
        $deviceLogs = $statsService->getFilteredLogs($filters);
        $stats = $statsService->getStats();

        $this->set(compact('deviceLogs', 'stats', 'filters'));
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

        $result = $bulkService->bulkDelete('DeviceLogs', $ids);

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
     * @param string|null $id Device Log id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

        $deviceLog = $this->DeviceLogs->get($id, contain: ['Users']);
        $this->set(compact('deviceLog'));
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

        $deviceLog = $this->DeviceLogs->newEmptyEntity();
        if ($this->request->is('post')) {
            $deviceLog = $this->DeviceLogs->patchEntity($deviceLog, $this->request->getData());
            if ($this->DeviceLogs->save($deviceLog)) {
                $this->Flash->success(__('The device log has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The device log could not be saved. Please, try again.'));
        }
        $users = $this->DeviceLogs->Users->find('list', limit: 200)->all();
        $this->set(compact('deviceLog', 'users'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Device Log id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

        $deviceLog = $this->DeviceLogs->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $deviceLog = $this->DeviceLogs->patchEntity($deviceLog, $this->request->getData());
            if ($this->DeviceLogs->save($deviceLog)) {
                $this->Flash->success(__('The device log has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The device log could not be saved. Please, try again.'));
        }
        $users = $this->DeviceLogs->Users->find('list', limit: 200)->all();
        $this->set(compact('deviceLog', 'users'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Device Log id.
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
        $deviceLog = $this->DeviceLogs->get($id);
        if ($this->DeviceLogs->delete($deviceLog)) {
            $this->Flash->success(__('The device log has been deleted.'));
        } else {
            $this->Flash->error(__('The device log could not be deleted. Please, try again.'));
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
            'controller' => 'DeviceLogs',
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
