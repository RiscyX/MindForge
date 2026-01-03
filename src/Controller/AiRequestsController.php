<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * AiRequests Controller
 *
 * @property \App\Model\Table\AiRequestsTable $AiRequests
 */
class AiRequestsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->AiRequests->find()
            ->contain(['Users', 'Tests', 'Languages']);
        $aiRequests = $this->paginate($query);

        $this->set(compact('aiRequests'));
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
        $aiRequest = $this->AiRequests->get($id, contain: ['Users', 'Tests', 'Languages']);
        $this->set(compact('aiRequest'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
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
        $tests = $this->AiRequests->Tests->find('list', limit: 200)->all();
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
        $tests = $this->AiRequests->Tests->find('list', limit: 200)->all();
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
        $this->request->allowMethod(['post', 'delete']);
        $aiRequest = $this->AiRequests->get($id);
        if ($this->AiRequests->delete($aiRequest)) {
            $this->Flash->success(__('The ai request has been deleted.'));
        } else {
            $this->Flash->error(__('The ai request could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
