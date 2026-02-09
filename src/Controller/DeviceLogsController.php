<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Throwable;

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
        $query = $this->DeviceLogs->find()
            ->contain(['Users'])
            ->orderByDesc('DeviceLogs.created_at');

        $deviceLogs = $query->all();

        $since = FrozenTime::now()->subHours(24);
        $statsTotal = (int)$this->DeviceLogs->find()->count();
        $statsLast24h = (int)$this->DeviceLogs->find()->where(['DeviceLogs.created_at >=' => $since])->count();
        $statsUniqueUsers = (int)$this->DeviceLogs->find()->select(['user_id'])->distinct(['user_id'])->where(['DeviceLogs.user_id IS NOT' => null])->count();
        $statsUniqueIps24h = (int)$this->DeviceLogs->find()->select(['ip_address'])->distinct(['ip_address'])->where([
            'DeviceLogs.created_at >=' => $since,
            'DeviceLogs.ip_address IS NOT' => null,
        ])->count();

        $stats = [
            'total' => $statsTotal,
            'last24h' => $statsLast24h,
            'uniqueUsers' => $statsUniqueUsers,
            'uniqueIps24h' => $statsUniqueIps24h,
        ];

        $this->set(compact('deviceLogs', 'stats'));
    }

    /**
     * Bulk actions for the index table.
     *
     * @return \Cake\Http\Response|null
     */
    public function bulk(): ?Response
    {
        $this->request->allowMethod(['post']);

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
                $entity = $this->DeviceLogs->get((string)$id);
                if ($this->DeviceLogs->delete($entity)) {
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

    /**
     * View method
     *
     * @param string|null $id Device Log id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
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
        $this->request->allowMethod(['post', 'delete']);
        $deviceLog = $this->DeviceLogs->get($id);
        if ($this->DeviceLogs->delete($deviceLog)) {
            $this->Flash->success(__('The device log has been deleted.'));
        } else {
            $this->Flash->error(__('The device log could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }
}
