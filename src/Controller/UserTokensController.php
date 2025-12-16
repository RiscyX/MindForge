<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * UserTokens Controller
 *
 * @property \App\Model\Table\UserTokensTable $UserTokens
 */
class UserTokensController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->UserTokens->find()
            ->contain(['Users']);
        $userTokens = $this->paginate($query);

        $this->set(compact('userTokens'));
    }

    /**
     * View method
     *
     * @param string|null $id User Token id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $userToken = $this->UserTokens->get($id, contain: ['Users']);
        $this->set(compact('userToken'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $userToken = $this->UserTokens->newEmptyEntity();
        if ($this->request->is('post')) {
            $userToken = $this->UserTokens->patchEntity($userToken, $this->request->getData());
            if ($this->UserTokens->save($userToken)) {
                $this->Flash->success(__('The user token has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user token could not be saved. Please, try again.'));
        }
        $users = $this->UserTokens->Users->find('list', limit: 200)->all();
        $this->set(compact('userToken', 'users'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User Token id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $userToken = $this->UserTokens->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $userToken = $this->UserTokens->patchEntity($userToken, $this->request->getData());
            if ($this->UserTokens->save($userToken)) {
                $this->Flash->success(__('The user token has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user token could not be saved. Please, try again.'));
        }
        $users = $this->UserTokens->Users->find('list', limit: 200)->all();
        $this->set(compact('userToken', 'users'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User Token id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $userToken = $this->UserTokens->get($id);
        if ($this->UserTokens->delete($userToken)) {
            $this->Flash->success(__('The user token has been deleted.'));
        } else {
            $this->Flash->error(__('The user token could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
