<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * TestAttemptAnswers Controller
 *
 * @property \App\Model\Table\TestAttemptAnswersTable $TestAttemptAnswers
 */
class TestAttemptAnswersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->TestAttemptAnswers->find()
            ->contain(['TestAttempts', 'Questions', 'Answers']);
        $testAttemptAnswers = $this->paginate($query);

        $this->set(compact('testAttemptAnswers'));
    }

    /**
     * View method
     *
     * @param string|null $id Test Attempt Answer id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $testAttemptAnswer = $this->TestAttemptAnswers->get($id, contain: ['TestAttempts', 'Questions', 'Answers']);
        $this->set(compact('testAttemptAnswer'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $testAttemptAnswer = $this->TestAttemptAnswers->newEmptyEntity();
        if ($this->request->is('post')) {
            $testAttemptAnswer = $this->TestAttemptAnswers->patchEntity($testAttemptAnswer, $this->request->getData());
            if ($this->TestAttemptAnswers->save($testAttemptAnswer)) {
                $this->Flash->success(__('The test attempt answer has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The test attempt answer could not be saved. Please, try again.'));
        }
        $testAttempts = $this->TestAttemptAnswers->TestAttempts->find('list', limit: 200)->all();
        $questions = $this->TestAttemptAnswers->Questions->find('list', limit: 200)->all();
        $answers = $this->TestAttemptAnswers->Answers->find('list', limit: 200)->all();
        $this->set(compact('testAttemptAnswer', 'testAttempts', 'questions', 'answers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Test Attempt Answer id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $testAttemptAnswer = $this->TestAttemptAnswers->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $testAttemptAnswer = $this->TestAttemptAnswers->patchEntity($testAttemptAnswer, $this->request->getData());
            if ($this->TestAttemptAnswers->save($testAttemptAnswer)) {
                $this->Flash->success(__('The test attempt answer has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The test attempt answer could not be saved. Please, try again.'));
        }
        $testAttempts = $this->TestAttemptAnswers->TestAttempts->find('list', limit: 200)->all();
        $questions = $this->TestAttemptAnswers->Questions->find('list', limit: 200)->all();
        $answers = $this->TestAttemptAnswers->Answers->find('list', limit: 200)->all();
        $this->set(compact('testAttemptAnswer', 'testAttempts', 'questions', 'answers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Test Attempt Answer id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $testAttemptAnswer = $this->TestAttemptAnswers->get($id);
        if ($this->TestAttemptAnswers->delete($testAttemptAnswer)) {
            $this->Flash->success(__('The test attempt answer has been deleted.'));
        } else {
            $this->Flash->error(__('The test attempt answer could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
