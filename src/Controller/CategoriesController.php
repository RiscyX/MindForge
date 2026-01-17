<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Categories Controller
 *
 * @property \App\Model\Table\CategoriesTable $Categories
 */
class CategoriesController extends AppController
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
        $query = $this->Categories
            ->find()
            ->contain(['CategoryTranslations' => ['Languages']])
            ->orderByAsc('Categories.id');

        $categories = $query->all();

        $this->set(compact('categories'));
    }

    /**
     * View method
     *
     * @param string|null $id Category id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $category = $this->Categories->get($id, contain: [
            'CategoryTranslations', 'Questions', 'TestAttempts', 'Tests', 'UserFavoriteCategories']);
        $this->set(compact('category'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $category = $this->Categories->newEmptyEntity();
        $languages = $this->fetchTable('Languages')->find('all');

        if ($this->request->is('post')) {
            $category = $this->Categories->patchEntity($category, $this->request->getData(), [
                'associated' => ['CategoryTranslations'],
            ]);
            if ($this->Categories->save($category)) {
                $this->Flash->success(__('The category has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The category could not be saved. Please, try again.'));
        } else {
            $translations = [];
            foreach ($languages as $language) {
                $t = $this->Categories->CategoryTranslations->newEmptyEntity();
                $t->language_id = $language->id;
                $translations[] = $t;
            }
            $category->category_translations = $translations;
        }
        $this->set(compact('category', 'languages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Category id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $category = $this->Categories->get($id, contain: ['CategoryTranslations']);
        $languages = $this->fetchTable('Languages')->find('all');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $category = $this->Categories->patchEntity($category, $this->request->getData(), [
                'associated' => ['CategoryTranslations'],
            ]);
            if ($this->Categories->save($category)) {
                $this->Flash->success(__('The category has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The category could not be saved. Please, try again.'));
        } else {
            $existing = [];
            foreach ($category->category_translations as $t) {
                $existing[$t->language_id] = $t;
            }

            $completeTranslations = [];
            foreach ($languages as $language) {
                if (isset($existing[$language->id])) {
                    $completeTranslations[] = $existing[$language->id];
                } else {
                    $t = $this->Categories->CategoryTranslations->newEmptyEntity();
                    $t->language_id = $language->id;
                    $completeTranslations[] = $t;
                }
            }
            $category->category_translations = $completeTranslations;
        }
        $this->set(compact('category', 'languages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Category id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $category = $this->Categories->get($id);
        if ($this->Categories->delete($category)) {
            $this->Flash->success(__('The category has been deleted.'));
        } else {
            $this->Flash->error(__('The category could not be deleted. Please, try again.'));
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
        $rawIds = $this->request->getData('ids');
        $ids = is_array($rawIds) ? $rawIds : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($v) => $v > 0)));

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
                $entity = $this->Categories->get((string)$id);
                if ($this->Categories->delete($entity)) {
                    $deleted += 1;
                } else {
                    $failed += 1;
                }
            } catch (\Throwable) {
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
