<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * TestTranslations Controller
 *
 * @property \App\Model\Table\TestTranslationsTable $TestTranslations
 */
class TestTranslationsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->TestTranslations->find()
            ->contain(['Tests', 'Languages', 'Translators']);
        $testTranslations = $this->paginate($query);

        $this->set(compact('testTranslations'));
    }

    /**
     * View method
     *
     * @param string|null $id Test Translation id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $testTranslation = $this->TestTranslations->get($id, contain: ['Tests', 'Languages', 'Translators']);
        $this->set(compact('testTranslation'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $testTranslation = $this->TestTranslations->newEmptyEntity();
        if ($this->request->is('post')) {
            $testTranslation = $this->TestTranslations->patchEntity($testTranslation, $this->request->getData());
            if ($this->TestTranslations->save($testTranslation)) {
                $this->Flash->success(__('The test translation has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The test translation could not be saved. Please, try again.'));
        }
        $tests = $this->TestTranslations->Tests->find('list', limit: 200)->all();
        $languages = $this->TestTranslations->Languages->find('list', limit: 200)->all();
        $translators = $this->TestTranslations->Translators->find('list', limit: 200)->all();
        $this->set(compact('testTranslation', 'tests', 'languages', 'translators'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Test Translation id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $testTranslation = $this->TestTranslations->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $testTranslation = $this->TestTranslations->patchEntity($testTranslation, $this->request->getData());
            if ($this->TestTranslations->save($testTranslation)) {
                $this->Flash->success(__('The test translation has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The test translation could not be saved. Please, try again.'));
        }
        $tests = $this->TestTranslations->Tests->find('list', limit: 200)->all();
        $languages = $this->TestTranslations->Languages->find('list', limit: 200)->all();
        $translators = $this->TestTranslations->Translators->find('list', limit: 200)->all();
        $this->set(compact('testTranslation', 'tests', 'languages', 'translators'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Test Translation id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $testTranslation = $this->TestTranslations->get($id);
        if ($this->TestTranslations->delete($testTranslation)) {
            $this->Flash->success(__('The test translation has been deleted.'));
        } else {
            $this->Flash->error(__('The test translation could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
