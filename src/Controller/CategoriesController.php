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
        $query = $this->Categories->find()->contain(['CategoryTranslations' => ['Languages']]);
        $categories = $this->paginate($query);

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
}
