<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * TestAttempts Controller
 *
 * @property \App\Model\Table\TestAttemptsTable $TestAttempts
 */
class TestAttemptsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->TestAttempts->find()
            ->contain(['Users', 'Tests', 'Categories', 'Difficulties', 'Languages']);
        $testAttempts = $this->paginate($query);

        $this->set(compact('testAttempts'));
    }

    /**
     * View method
     *
     * @param string|null $id Test Attempt id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $testAttempt = $this->TestAttempts->get($id, contain: [
            'Users', 'Tests', 'Categories', 'Difficulties', 'Languages', 'TestAttemptAnswers']);
        $this->set(compact('testAttempt'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $testAttempt = $this->TestAttempts->newEmptyEntity();
        if ($this->request->is('post')) {
            $testAttempt = $this->TestAttempts->patchEntity($testAttempt, $this->request->getData());
            if ($this->TestAttempts->save($testAttempt)) {
                $this->Flash->success(__('The test attempt has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The test attempt could not be saved. Please, try again.'));
        }
        $users = $this->TestAttempts->Users->find('list', limit: 200)->all();
        $tests = $this->TestAttempts->Tests->find('list', limit: 200)->all();
        $categories = $this->TestAttempts->Categories->find('list', limit: 200)->all();
        $difficulties = $this->TestAttempts->Difficulties->find('list', limit: 200)->all();
        $languages = $this->TestAttempts->Languages->find('list', limit: 200)->all();
        $this->set(compact('testAttempt', 'users', 'tests', 'categories', 'difficulties', 'languages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Test Attempt id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $testAttempt = $this->TestAttempts->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $testAttempt = $this->TestAttempts->patchEntity($testAttempt, $this->request->getData());
            if ($this->TestAttempts->save($testAttempt)) {
                $this->Flash->success(__('The test attempt has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The test attempt could not be saved. Please, try again.'));
        }
        $users = $this->TestAttempts->Users->find('list', limit: 200)->all();
        $tests = $this->TestAttempts->Tests->find('list', limit: 200)->all();
        $categories = $this->TestAttempts->Categories->find('list', limit: 200)->all();
        $difficulties = $this->TestAttempts->Difficulties->find('list', limit: 200)->all();
        $languages = $this->TestAttempts->Languages->find('list', limit: 200)->all();
        $this->set(compact('testAttempt', 'users', 'tests', 'categories', 'difficulties', 'languages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Test Attempt id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $testAttempt = $this->TestAttempts->get($id);
        if ($this->TestAttempts->delete($testAttempt)) {
            $this->Flash->success(__('The test attempt has been deleted.'));
        } else {
            $this->Flash->error(__('The test attempt could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
