<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * UserFavoriteTests Controller
 *
 * @property \App\Model\Table\UserFavoriteTestsTable $UserFavoriteTests
 */
class UserFavoriteTestsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->UserFavoriteTests->find()
            ->contain(['Users', 'Tests']);
        $userFavoriteTests = $this->paginate($query);

        $this->set(compact('userFavoriteTests'));
    }

    /**
     * View method
     *
     * @param string|null $id User Favorite Test id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $userFavoriteTest = $this->UserFavoriteTests->get($id, contain: ['Users', 'Tests']);
        $this->set(compact('userFavoriteTest'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $userFavoriteTest = $this->UserFavoriteTests->newEmptyEntity();
        if ($this->request->is('post')) {
            $userFavoriteTest = $this->UserFavoriteTests->patchEntity($userFavoriteTest, $this->request->getData());
            if ($this->UserFavoriteTests->save($userFavoriteTest)) {
                $this->Flash->success(__('The user favorite test has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user favorite test could not be saved. Please, try again.'));
        }
        $users = $this->UserFavoriteTests->Users->find('list', limit: 200)->all();
        $tests = $this->UserFavoriteTests->Tests->find('list', limit: 200)->all();
        $this->set(compact('userFavoriteTest', 'users', 'tests'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User Favorite Test id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $userFavoriteTest = $this->UserFavoriteTests->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $userFavoriteTest = $this->UserFavoriteTests->patchEntity($userFavoriteTest, $this->request->getData());
            if ($this->UserFavoriteTests->save($userFavoriteTest)) {
                $this->Flash->success(__('The user favorite test has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user favorite test could not be saved. Please, try again.'));
        }
        $users = $this->UserFavoriteTests->Users->find('list', limit: 200)->all();
        $tests = $this->UserFavoriteTests->Tests->find('list', limit: 200)->all();
        $this->set(compact('userFavoriteTest', 'users', 'tests'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User Favorite Test id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $userFavoriteTest = $this->UserFavoriteTests->get($id);
        if ($this->UserFavoriteTests->delete($userFavoriteTest)) {
            $this->Flash->success(__('The user favorite test has been deleted.'));
        } else {
            $this->Flash->error(__('The user favorite test could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
