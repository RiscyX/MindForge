<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Throwable;

/**
 * Answers Controller
 *
 * @property \App\Model\Table\AnswersTable $Answers
 */
class AnswersController extends AppController
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

        $query = $this->Answers
            ->find()
            ->contain([
                'Questions.QuestionTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
                'AnswerTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['AnswerTranslations.language_id' => $languageId]);
                },
            ])
            ->orderByAsc('Answers.id');

        $answers = $query->all();

        $this->set(compact('answers'));
    }

    /**
     * View method
     *
     * @param string|null $id Answer id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = $this->resolveLanguageId($langCode);

        $answer = $this->Answers->get($id, contain: [
            'Questions.QuestionTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                return $languageId === null ? $q : $q->where(['QuestionTranslations.language_id' => $languageId]);
            },
            'AnswerTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                return $languageId === null ? $q : $q->where(['AnswerTranslations.language_id' => $languageId]);
            },
            'TestAttemptAnswers',
        ]);
        $this->set(compact('answer'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $answer = $this->Answers->newEmptyEntity();
        if ($this->request->is('post')) {
            $answer = $this->Answers->patchEntity($answer, $this->request->getData());
            if ($this->Answers->save($answer)) {
                $this->Flash->success(__('The answer has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The answer could not be saved. Please, try again.'));
        }
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = $this->resolveLanguageId($langCode);

        $questionsQuery = $this->Answers->Questions->find()
            ->contain([
                'QuestionTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
            ])
            ->orderByAsc('Questions.id');

        $questions = [];
        foreach ($questionsQuery->all() as $q) {
            $content = '';
            if (!empty($q->question_translations)) {
                $content = (string)($q->question_translations[0]->content ?? '');
            }
            $label = $content !== '' ? $content : 'Question #' . $q->id;
            $label = substr($label, 0, 90);
            $questions[(string)$q->id] = $label;
        }
        $this->set(compact('answer', 'questions'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Answer id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $answer = $this->Answers->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $answer = $this->Answers->patchEntity($answer, $this->request->getData());
            if ($this->Answers->save($answer)) {
                $this->Flash->success(__('The answer has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The answer could not be saved. Please, try again.'));
        }
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = $this->resolveLanguageId($langCode);

        $questionsQuery = $this->Answers->Questions->find()
            ->contain([
                'QuestionTranslations' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $languageId === null ? $q : $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
            ])
            ->orderByAsc('Questions.id');

        $questions = [];
        foreach ($questionsQuery->all() as $q) {
            $content = '';
            if (!empty($q->question_translations)) {
                $content = (string)($q->question_translations[0]->content ?? '');
            }
            $label = $content !== '' ? $content : 'Question #' . $q->id;
            $label = substr($label, 0, 90);
            $questions[(string)$q->id] = $label;
        }
        $this->set(compact('answer', 'questions'));
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
     * @param string|null $id Answer id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $answer = $this->Answers->get($id);
        if ($this->Answers->delete($answer)) {
            $this->Flash->success(__('The answer has been deleted.'));
        } else {
            $this->Flash->error(__('The answer could not be deleted. Please, try again.'));
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
                $entity = $this->Answers->get((string)$id);
                if ($this->Answers->delete($entity)) {
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
