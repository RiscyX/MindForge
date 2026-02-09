<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Throwable;

/**
 * Questions Controller
 *
 * @property \App\Model\Table\QuestionsTable $Questions
 */
class QuestionsController extends AppController
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
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = $this->resolveLanguageId($langCode);

        $query = $this->Questions
            ->find()
            ->contain([
                'Tests',
                'Categories.CategoryTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['CategoryTranslations.language_id' => $languageId]);
                },
                'Difficulties.DifficultyTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['DifficultyTranslations.language_id' => $languageId]);
                },
                'QuestionTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
            ])
            ->orderByAsc('Questions.id');

        $questions = $query->all();

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
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = $this->resolveLanguageId($langCode);

        $question = $this->Questions->get($id, contain: [
            'Tests',
            'Categories.CategoryTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                return $languageId === null ? $q : $q->where(['CategoryTranslations.language_id' => $languageId]);
            },
            'Difficulties.DifficultyTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                return $languageId === null ? $q : $q->where(['DifficultyTranslations.language_id' => $languageId]);
            },
            'Answers.AnswerTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                return $languageId === null ? $q : $q->where(['AnswerTranslations.language_id' => $languageId]);
            },
            'QuestionTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                return $languageId === null ? $q : $q->where(['QuestionTranslations.language_id' => $languageId]);
            },
            'TestAttemptAnswers',
        ]);
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

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The question could not be saved. Please, try again.'));
        }
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = $this->resolveLanguageId($langCode);

        $tests = $this->Questions->Tests->find('list', limit: 200)->all();

        $categories = $this->Questions->Categories->CategoryTranslations->find('list', [
            'keyField' => 'category_id',
            'valueField' => 'name',
        ])
            ->where($languageId === null ? [] : ['language_id' => $languageId])
            ->all();

        $difficulties = $this->Questions->Difficulties->DifficultyTranslations->find('list', [
            'keyField' => 'difficulty_id',
            'valueField' => 'name',
        ])
            ->where($languageId === null ? [] : ['language_id' => $languageId])
            ->all();
        $this->set(compact('question', 'tests', 'categories', 'difficulties'));
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

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The question could not be saved. Please, try again.'));
        }
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = $this->resolveLanguageId($langCode);

        $tests = $this->Questions->Tests->find('list', limit: 200)->all();

        $categories = $this->Questions->Categories->CategoryTranslations->find('list', [
            'keyField' => 'category_id',
            'valueField' => 'name',
        ])
            ->where($languageId === null ? [] : ['language_id' => $languageId])
            ->all();

        $difficulties = $this->Questions->Difficulties->DifficultyTranslations->find('list', [
            'keyField' => 'difficulty_id',
            'valueField' => 'name',
        ])
            ->where($languageId === null ? [] : ['language_id' => $languageId])
            ->all();
        $this->set(compact('question', 'tests', 'categories', 'difficulties'));
    }

    private function resolveLanguageId(string $langCode): ?int
    {
        $language = $this->fetchTable('Languages')
            ->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        if ($language === null) {
            $language = $this->fetchTable('Languages')->find()->first();
        }

        return $language?->id === null ? null : (int)$language->id;
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

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }

    /**
     * Bulk actions for the index table.
     *
     * @return \\Cake\\Http\\Response|null
     */
    public function bulk(): ?Response
    {
        $this->request->allowMethod(['post']);

        $action = (string)$this->request->getData('bulk_action');
        $rawIds = $this->request->getData('ids');
        $ids = is_array($rawIds) ? $rawIds : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0)));

        if (!$ids) {
            $this->Flash->error(__('Select at least one item.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        if ($action !== 'delete') {
            $this->Flash->error(__('Invalid bulk action.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        $deleted = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $entity = $this->Questions->get((string)$id);
                if ($this->Questions->delete($entity)) {
                    $deleted += 1;
                } else {
                    $failed += 1;
                }
            } catch (Throwable) {
                $failed += 1;
            }
        }

        if ($deleted > 0) {
            $this->Flash->success(__('Deleted {0} item(s).', $deleted));
        }
        if ($failed > 0) {
            $this->Flash->error(__('Could not delete {0} item(s).', $failed));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }
}
