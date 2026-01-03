<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * UserFavoriteCategories Controller
 *
 * @property \App\Model\Table\UserFavoriteCategoriesTable $UserFavoriteCategories
 */
class UserFavoriteCategoriesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->UserFavoriteCategories->find()
            ->contain(['Users', 'Categories']);
        $userFavoriteCategories = $this->paginate($query);

        $this->set(compact('userFavoriteCategories'));
    }

    /**
     * View method
     *
     * @param string|null $id User Favorite Category id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $userFavoriteCategory = $this->UserFavoriteCategories->get($id, contain: ['Users', 'Categories']);
        $this->set(compact('userFavoriteCategory'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $userFavoriteCategory = $this->UserFavoriteCategories->newEmptyEntity();
        if ($this->request->is('post')) {
            $userFavoriteCategory =
                $this->UserFavoriteCategories->patchEntity($userFavoriteCategory, $this->request->getData());
            if ($this->UserFavoriteCategories->save($userFavoriteCategory)) {
                $this->Flash->success(__('The user favorite category has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user favorite category could not be saved. Please, try again.'));
        }
        $users = $this->UserFavoriteCategories->Users->find('list', limit: 200)->all();
        $categories = $this->UserFavoriteCategories->Categories->find('list', limit: 200)->all();
        $this->set(compact('userFavoriteCategory', 'users', 'categories'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User Favorite Category id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $userFavoriteCategory = $this->UserFavoriteCategories->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $userFavoriteCategory =
                $this->UserFavoriteCategories->patchEntity($userFavoriteCategory, $this->request->getData());
            if ($this->UserFavoriteCategories->save($userFavoriteCategory)) {
                $this->Flash->success(__('The user favorite category has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user favorite category could not be saved. Please, try again.'));
        }
        $users = $this->UserFavoriteCategories->Users->find('list', limit: 200)->all();
        $categories = $this->UserFavoriteCategories->Categories->find('list', limit: 200)->all();
        $this->set(compact('userFavoriteCategory', 'users', 'categories'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User Favorite Category id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $userFavoriteCategory = $this->UserFavoriteCategories->get($id);
        if ($this->UserFavoriteCategories->delete($userFavoriteCategory)) {
            $this->Flash->success(__('The user favorite category has been deleted.'));
        } else {
            $this->Flash->error(__('The user favorite category could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
