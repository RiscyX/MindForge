<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\BulkActionService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Difficulties Controller
 *
 * @property \App\Model\Table\DifficultiesTable $Difficulties
 */
class DifficultiesController extends AppController
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
        $langCode = $this->request->getParam('lang');

        $query = $this->Difficulties->find()
            ->contain(['DifficultyTranslations' => function ($q) use ($langCode) {
                return $q->contain(['Languages'])
                    ->where(['Languages.code LIKE' => $langCode . '%']);
            }])
            ->orderByAsc('Difficulties.id');

        $difficulties = $query->all();

        // Fallback for missing translations
        // We might want to load ALL translations if the specific one isn't found,
        // to show a fallback. But for now let's try to just load the specific one.
        // If we want a fallback logic (e.g. show English if Hungarian missing),
        // we might need a more complex query or post-processing.

        // Let's load all translations if we want to be safe, or just stick to the requested one.
        // The previous code block filters translations. If empty, the entity will have an empty array.

        $this->set(compact('difficulties'));
    }

    /**
     * View method
     *
     * @param string|null $id Difficulty id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $difficulty = $this->Difficulties->get($id, contain: [
            'Questions', 'TestAttempts', 'Tests']);
        $this->set(compact('difficulty'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $difficulty = $this->Difficulties->newEmptyEntity();

        if ($this->request->is('post')) {
            $difficulty = $this->Difficulties->patchEntity($difficulty, $this->request->getData());
            if ($this->Difficulties->save($difficulty)) {
                $this->Flash->success(__('The difficulty has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The difficulty could not be saved. Please, try again.'));
        }

        $languages = $this->fetchTable('Languages')->find('all');
        $this->set(compact('difficulty', 'languages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Difficulty id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $difficulty = $this->Difficulties->get($id, contain: ['DifficultyTranslations']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $difficulty = $this->Difficulties->patchEntity($difficulty, $this->request->getData());
            if ($this->Difficulties->save($difficulty)) {
                $this->Flash->success(__('The difficulty has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The difficulty could not be saved. Please, try again.'));
        }

        $languages = $this->fetchTable('Languages')->find('all');
        $this->set(compact('difficulty', 'languages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Difficulty id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $difficulty = $this->Difficulties->get($id);
        if ($this->Difficulties->delete($difficulty)) {
            $this->Flash->success(__('The difficulty has been deleted.'));
        } else {
            $this->Flash->error(__('The difficulty could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }

    /**
     * Bulk actions for the index table.
     *
     * @return \\Cake\\Http\\Response|null
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

        $result = $bulkService->bulkDelete('Difficulties', $ids);

        if ($result['deleted'] > 0) {
            $this->Flash->success(__('Deleted {0} item(s).', $result['deleted']));
        }
        if ($result['failed'] > 0) {
            $this->Flash->error(__('Could not delete {0} item(s).', $result['failed']));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }
}
