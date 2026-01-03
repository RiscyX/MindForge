<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * AnswerTranslations Controller
 *
 * @property \App\Model\Table\AnswerTranslationsTable $AnswerTranslations
 */
class AnswerTranslationsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->AnswerTranslations->find()
            ->contain(['Answers', 'Languages']);
        $answerTranslations = $this->paginate($query);

        $this->set(compact('answerTranslations'));
    }

    /**
     * View method
     *
     * @param string|null $id Answer Translation id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $answerTranslation = $this->AnswerTranslations->get($id, contain: ['Answers', 'Languages']);
        $this->set(compact('answerTranslation'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $answerTranslation = $this->AnswerTranslations->newEmptyEntity();
        if ($this->request->is('post')) {
            $answerTranslation = $this->AnswerTranslations->patchEntity($answerTranslation, $this->request->getData());
            if ($this->AnswerTranslations->save($answerTranslation)) {
                $this->Flash->success(__('The answer translation has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The answer translation could not be saved. Please, try again.'));
        }
        $answers = $this->AnswerTranslations->Answers->find('list', limit: 200)->all();
        $languages = $this->AnswerTranslations->Languages->find('list', limit: 200)->all();
        $this->set(compact('answerTranslation', 'answers', 'languages'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Answer Translation id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $answerTranslation = $this->AnswerTranslations->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $answerTranslation = $this->AnswerTranslations->patchEntity($answerTranslation, $this->request->getData());
            if ($this->AnswerTranslations->save($answerTranslation)) {
                $this->Flash->success(__('The answer translation has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The answer translation could not be saved. Please, try again.'));
        }
        $answers = $this->AnswerTranslations->Answers->find('list', limit: 200)->all();
        $languages = $this->AnswerTranslations->Languages->find('list', limit: 200)->all();
        $this->set(compact('answerTranslation', 'answers', 'languages'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Answer Translation id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $answerTranslation = $this->AnswerTranslations->get($id);
        if ($this->AnswerTranslations->delete($answerTranslation)) {
            $this->Flash->success(__('The answer translation has been deleted.'));
        } else {
            $this->Flash->error(__('The answer translation could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
