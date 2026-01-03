<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * CategoryTranslations Controller
 *
 * @property \App\Model\Table\CategoryTranslationsTable $CategoryTranslations
 */
class CategoryTranslationsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->CategoryTranslations->find()
            ->contain(['Categories', 'Languages']);
        $categoryTranslations = $this->paginate($query);

        $this->set(compact('categoryTranslations'));
    }

    /**
     * View method
     *
     * @param string|null $id Category Translation id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $categoryTranslation = $this->CategoryTranslations->get($id, contain: ['Categories', 'Languages']);
        $this->set(compact('categoryTranslation'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $categoryTranslation = $this->CategoryTranslations->newEmptyEntity();
        if ($this->request->is('post')) {
            $categoryTranslation =
                $this->CategoryTranslations->patchEntity($categoryTranslation, $this->request->getData());
            if ($this->CategoryTranslations->save($categoryTranslation)) {
                $this->Flash->success(__('The category translation has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The category translation could not be saved. Please, try again.'));
        }
        $categories = $this->CategoryTranslations->Categories->find('list', limit: 200)->all();
        $languages = $this->CategoryTranslations->Languages->find('list', limit: 200)->all();
        $this->set(compact('categoryTranslation', 'categories', 'languages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Category Translation id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $categoryTranslation = $this->CategoryTranslations->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $categoryTranslation =
                $this->CategoryTranslations->patchEntity($categoryTranslation, $this->request->getData());
            if ($this->CategoryTranslations->save($categoryTranslation)) {
                $this->Flash->success(__('The category translation has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The category translation could not be saved. Please, try again.'));
        }
        $categories = $this->CategoryTranslations->Categories->find('list', limit: 200)->all();
        $languages = $this->CategoryTranslations->Languages->find('list', limit: 200)->all();
        $this->set(compact('categoryTranslation', 'categories', 'languages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Category Translation id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $categoryTranslation = $this->CategoryTranslations->get($id);
        if ($this->CategoryTranslations->delete($categoryTranslation)) {
            $this->Flash->success(__('The category translation has been deleted.'));
        } else {
            $this->Flash->error(__('The category translation could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
