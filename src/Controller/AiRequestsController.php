<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Throwable;

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

        $since = FrozenTime::now()->subHours(24);

        $statsTotal = (int)$this->AiRequests->find()->count();
        $statsLast24h = (int)$this->AiRequests->find()->where(['AiRequests.created_at >=' => $since])->count();
        $statsSuccessTotal = (int)$this->AiRequests->find()->where(['AiRequests.status' => 'success'])->count();
        $statsSuccess24h = (int)$this->AiRequests->find()->where([
            'AiRequests.created_at >=' => $since,
            'AiRequests.status' => 'success',
        ])->count();
        $statsUniqueUsers24h = (int)$this->AiRequests->find()
            ->select(['user_id'])
            ->distinct(['user_id'])
            ->where([
                'AiRequests.created_at >=' => $since,
                'AiRequests.user_id IS NOT' => null,
            ])
            ->count();

        $topTypes24h = $this->AiRequests->find()
            ->select([
                'type' => 'AiRequests.type',
                'count' => $this->AiRequests->find()->func()->count('*'),
            ])
            ->where(['AiRequests.created_at >=' => $since])
            ->groupBy(['AiRequests.type'])
            ->orderByDesc('count')
            ->limit(5)
            ->enableHydration(false)
            ->all()
            ->toList();

        $topSources24h = $this->AiRequests->find()
            ->select([
                'source_reference' => 'AiRequests.source_reference',
                'count' => $this->AiRequests->find()->func()->count('*'),
            ])
            ->where([
                'AiRequests.created_at >=' => $since,
                'AiRequests.source_reference IS NOT' => null,
                'AiRequests.source_reference !=' => '',
            ])
            ->groupBy(['AiRequests.source_reference'])
            ->orderByDesc('count')
            ->limit(5)
            ->enableHydration(false)
            ->all()
            ->toList();

        $totalTokensRow = $this->AiRequests->find()
            ->select(['s' => $this->AiRequests->find()->func()->sum('AiRequests.total_tokens')])
            ->enableHydration(false)
            ->first();
        $statsTotalTokens = $totalTokensRow ? (int)($totalTokensRow['s'] ?? 0) : 0;

        $totalCostRow = $this->AiRequests->find()
            ->select(['s' => $this->AiRequests->find()->func()->sum('AiRequests.cost_usd')])
            ->enableHydration(false)
            ->first();
        $statsTotalCostUsd = $totalCostRow ? round((float)($totalCostRow['s'] ?? 0), 6) : 0.0;

        // User options for the filter dropdown (users who have at least one ai_request)
        $userOptions = $this->AiRequests->Users->find('list', [
            'keyField' => 'id',
            'valueField' => 'email',
        ])
            ->innerJoin(
                ['AR' => 'ai_requests'],
                ['AR.user_id = Users.id'],
            )
            ->distinct(['Users.id'])
            ->orderByAsc('Users.email')
            ->toArray();

        $stats = [
            'total' => $statsTotal,
            'last24h' => $statsLast24h,
            'successTotal' => $statsSuccessTotal,
            'success24h' => $statsSuccess24h,
            'uniqueUsers24h' => $statsUniqueUsers24h,
            'topTypes24h' => $topTypes24h,
            'topSources24h' => $topSources24h,
            'totalTokens' => $statsTotalTokens,
            'totalCostUsd' => $statsTotalCostUsd,
        ];

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
                $entity = $this->AiRequests->get((string)$id);
                if ($this->AiRequests->delete($entity)) {
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
            $aiRequest = $this->AiRequests->patchEntity($aiRequest, $this->request->getData());
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
            $aiRequest = $this->AiRequests->patchEntity($aiRequest, $this->request->getData());
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
