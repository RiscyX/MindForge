<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * Questions Controller
 *
 * @property \App\Model\Table\QuestionsTable $Questions
 */
class QuestionsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Questions->find()
            ->contain(['Tests', 'Categories', 'Difficulties', 'OriginalLanguages']);
        $questions = $this->paginate($query);

        $this->set(compact('questions'));
    }

    /**
     * View method
     *
     * @param string|null $id Question id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $question = $this->Questions->get($id, contain: [
                'Tests',
                'Categories',
                'Difficulties',
                'OriginalLanguages',
                'Answers',
                'QuestionTranslations',
                'TestAttemptAnswers']);
        $this->set(compact('question'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $question = $this->Questions->newEmptyEntity();
        if ($this->request->is('post')) {
            $question = $this->Questions->patchEntity($question, $this->request->getData());
            if ($this->Questions->save($question)) {
                $this->Flash->success(__('The question has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The question could not be saved. Please, try again.'));
        }
        $tests = $this->Questions->Tests->find('list', limit: 200)->all();
        $categories = $this->Questions->Categories->find('list', limit: 200)->all();
        $difficulties = $this->Questions->Difficulties->find('list', limit: 200)->all();
        $originalLanguages = $this->Questions->OriginalLanguages->find('list', limit: 200)->all();
        $this->set(compact('question', 'tests', 'categories', 'difficulties', 'originalLanguages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Question id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $question = $this->Questions->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $question = $this->Questions->patchEntity($question, $this->request->getData());
            if ($this->Questions->save($question)) {
                $this->Flash->success(__('The question has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The question could not be saved. Please, try again.'));
        }
        $tests = $this->Questions->Tests->find('list', limit: 200)->all();
        $categories = $this->Questions->Categories->find('list', limit: 200)->all();
        $difficulties = $this->Questions->Difficulties->find('list', limit: 200)->all();
        $originalLanguages = $this->Questions->OriginalLanguages->find('list', limit: 200)->all();
        $this->set(compact('question', 'tests', 'categories', 'difficulties', 'originalLanguages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Question id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $question = $this->Questions->get($id);
        if ($this->Questions->delete($question)) {
            $this->Flash->success(__('The question has been deleted.'));
        } else {
            $this->Flash->error(__('The question could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
