<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Difficulties Controller
 *
 * @property \App\Model\Table\DifficultiesTable $Difficulties
 */
class DifficultiesController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->setLayout('admin');
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Difficulties->find()->contain(['DifficultyTranslations' => ['Languages']]);
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
        $difficulty = $this->Difficulties->get($id, contain: [
            'DifficultyTranslations', 'Questions', 'TestAttempts', 'Tests']);
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
        $languages = $this->fetchTable('Languages')->find('all');

        if ($this->request->is('post')) {
            $difficulty = $this->Difficulties->patchEntity($difficulty, $this->request->getData(), [
                'associated' => ['DifficultyTranslations'],
            ]);
            if ($this->Difficulties->save($difficulty)) {
                $this->Flash->success(__('The difficulty has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The difficulty could not be saved. Please, try again.'));
        } else {
            $translations = [];
            foreach ($languages as $language) {
                $t = $this->Difficulties->DifficultyTranslations->newEmptyEntity();
                $t->language_id = $language->id;
                $translations[] = $t;
            }
            $difficulty->difficulty_translations = $translations;
        }
        $this->set(compact('difficulty', 'languages'));
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
        $difficulty = $this->Difficulties->get($id, contain: ['DifficultyTranslations']);
        $languages = $this->fetchTable('Languages')->find('all');

        if ($this->request->is(['patch', 'post', 'put'])) {
            $difficulty = $this->Difficulties->patchEntity($difficulty, $this->request->getData(), [
                'associated' => ['DifficultyTranslations'],
            ]);
            if ($this->Difficulties->save($difficulty)) {
                $this->Flash->success(__('The difficulty has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The difficulty could not be saved. Please, try again.'));
        } else {
            // Ensure all languages have a translation entity
            $existingTranslations = [];
            foreach ($difficulty->difficulty_translations as $t) {
                $existingTranslations[$t->language_id] = $t;
            }

            $translations = [];
            foreach ($languages as $language) {
                if (isset($existingTranslations[$language->id])) {
                    $translations[] = $existingTranslations[$language->id];
                } else {
                    $t = $this->Difficulties->DifficultyTranslations->newEmptyEntity();
                    $t->language_id = $language->id;
                    $translations[] = $t;
                }
            }
            $difficulty->difficulty_translations = $translations;
        }
        $this->set(compact('difficulty', 'languages'));
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

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }
}
