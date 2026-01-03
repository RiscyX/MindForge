<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * Difficulties Controller
 *
 * @property \App\Model\Table\DifficultiesTable $Difficulties
 */
class DifficultiesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Difficulties->find();
        $difficulties = $this->paginate($query);

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
        $difficulty = $this->Difficulties->get($id, contain: ['Questions', 'TestAttempts', 'Tests']);
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

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The difficulty could not be saved. Please, try again.'));
        }
        $this->set(compact('difficulty'));
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
        $difficulty = $this->Difficulties->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $difficulty = $this->Difficulties->patchEntity($difficulty, $this->request->getData());
            if ($this->Difficulties->save($difficulty)) {
                $this->Flash->success(__('The difficulty has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The difficulty could not be saved. Please, try again.'));
        }
        $this->set(compact('difficulty'));
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

        return $this->redirect(['action' => 'index']);
    }
}
