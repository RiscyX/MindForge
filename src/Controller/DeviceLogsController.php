<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query\SelectQuery;
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
        $redirect = $this->redirectToAdminPrefix();
        if ($redirect !== null) {
            return $redirect;
        }

        $filters = [
            'q' => trim((string)$this->request->getQuery('q', '')),
            'device_type' => (string)$this->request->getQuery('device_type', ''),
            'from' => (string)$this->request->getQuery('from', ''),
            'to' => (string)$this->request->getQuery('to', ''),
        ];

        $limitOptions = [25, 50, 100, 200];
        $limit = (int)$this->request->getQuery('limit', 50);
        if (!in_array($limit, $limitOptions, true)) {
            $limit = 50;
        }

        $query = $this->DeviceLogs->find()
            ->select([
                'DeviceLogs.id',
                'DeviceLogs.user_id',
                'DeviceLogs.ip_address',
                'DeviceLogs.user_agent',
                'DeviceLogs.device_type',
                'DeviceLogs.country',
                'DeviceLogs.city',
                'DeviceLogs.created_at',
            ])
            ->contain([
                'Users' => function (SelectQuery $q): SelectQuery {
                    return $q->select(['Users.id', 'Users.email']);
                },
            ])
            ->orderByDesc('DeviceLogs.created_at');

        if (in_array($filters['device_type'], ['0', '1', '2'], true)) {
            $query->where(['DeviceLogs.device_type' => (int)$filters['device_type']]);
        }

        if ($filters['from'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from']) === 1) {
            $query->where(['DeviceLogs.created_at >=' => $filters['from'] . ' 00:00:00']);
        }
        if ($filters['to'] !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to']) === 1) {
            $query->where(['DeviceLogs.created_at <=' => $filters['to'] . ' 23:59:59']);
        }

        if ($filters['q'] !== '') {
            $qLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
            $query
                ->leftJoinWith('Users')
                ->where([
                    'OR' => [
                        'DeviceLogs.ip_address LIKE' => $qLike,
                        'DeviceLogs.country LIKE' => $qLike,
                        'DeviceLogs.city LIKE' => $qLike,
                        'DeviceLogs.user_agent LIKE' => $qLike,
                        'Users.email LIKE' => $qLike,
                    ],
                ])
                ->distinct(['DeviceLogs.id']);
        }

        $this->paginate = [
            'limit' => $limit,
            'maxLimit' => 200,
            'order' => ['DeviceLogs.created_at' => 'DESC'],
        ];

        $deviceLogs = $this->paginate($query);

        $since = FrozenTime::now()->subHours(24);
        $statsTotal = (int)$this->DeviceLogs->find()->count();
        $statsLast24h = (int)$this->DeviceLogs->find()->where(['DeviceLogs.created_at >=' => $since])->count();
        $statsUniqueUsers = (int)$this->DeviceLogs->find()
            ->select(['user_id'])
            ->distinct(['user_id'])
            ->where(['DeviceLogs.user_id IS NOT' => null])
            ->count();
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

        $this->set(compact('deviceLogs', 'stats', 'filters', 'limit', 'limitOptions'));
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
