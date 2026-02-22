<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Question;
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
        $filters = [
            'category' => (string)$this->request->getQuery('category', ''),
            'question_type' => (string)$this->request->getQuery('question_type', ''),
            'is_active' => (string)$this->request->getQuery('is_active', ''),
            'source_type' => (string)$this->request->getQuery('source_type', ''),
            'needs_review' => (string)$this->request->getQuery('needs_review', ''),
        ];

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

        if ($filters['category'] !== '' && ctype_digit($filters['category'])) {
            $query->where(['Questions.category_id' => (int)$filters['category']]);
        }

        $questionTypes = [
            Question::TYPE_MULTIPLE_CHOICE,
            Question::TYPE_TRUE_FALSE,
            Question::TYPE_TEXT,
        ];
        if (in_array($filters['question_type'], $questionTypes, true)) {
            $query->where(['Questions.question_type' => $filters['question_type']]);
        }

        if (in_array($filters['source_type'], ['human', 'ai'], true)) {
            $query->where(['Questions.source_type' => $filters['source_type']]);
        }

        if ($filters['is_active'] === '1') {
            $query->where(['Questions.is_active' => true]);
        } elseif ($filters['is_active'] === '0') {
            $query->where(['Questions.is_active' => false]);
        }

        if ($filters['needs_review'] === '1') {
            $query->where(['Questions.needs_review' => true]);
        } elseif ($filters['needs_review'] === '0') {
            $query->where(['Questions.needs_review' => false]);
        }

        $questions = $query->all();

        $categoryOptions = $this->Questions->Categories->CategoryTranslations->find('list', [
            'keyField' => 'category_id',
            'valueField' => 'name',
        ])
            ->where($languageId === null ? [] : ['language_id' => $languageId])
            ->all()
            ->toArray();

        $questionTypeOptions = [
            Question::TYPE_MULTIPLE_CHOICE => __('Multiple Choice'),
            Question::TYPE_TRUE_FALSE => __('True/False'),
            Question::TYPE_TEXT => __('Text'),
        ];
        $sourceTypeOptions = [
            'human' => __('Human'),
            'ai' => __('AI'),
        ];
        $activeOptions = [
            '1' => __('Active'),
            '0' => __('Inactive'),
        ];
        $needsReviewOptions = [
            '1' => __('Needs Review'),
            '0' => __('Reviewed'),
        ];

        $this->set(compact(
            'questions',
            'filters',
            'categoryOptions',
            'questionTypeOptions',
            'sourceTypeOptions',
            'activeOptions',
            'needsReviewOptions',
        ));
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
        $question = $this->Questions->get($id, contain: [
            'Answers' => function (SelectQuery $q): SelectQuery {
                return $q
                    ->orderByAsc('Answers.position')
                    ->orderByAsc('Answers.id');
            },
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $data['answers'] = $this->normalizeAnswersPayload((array)($data['answers'] ?? []));

            $question = $this->Questions->patchEntity($question, $data, [
                'associated' => [
                    'Answers',
                ],
            ]);

            $hasCorrectAnswer = false;
            foreach ($question->answers as $answer) {
                if ((bool)$answer->is_correct) {
                    $hasCorrectAnswer = true;

                    break;
                }
            }

            if (!$hasCorrectAnswer) {
                $question->setError('answers', [
                    'correct' => __('At least one correct answer is required.'),
                ]);
            }

            if (!$question->getErrors() && $this->Questions->save($question, ['associated' => ['Answers']])) {
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
     * Normalize answer rows posted from inline edit form.
     *
     * @param array<int|string, mixed> $answers Raw answer rows.
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAnswersPayload(array $answers): array
    {
        $normalized = [];
        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $id = isset($answer['id']) && is_numeric($answer['id']) ? (int)$answer['id'] : null;
            $sourceText = trim((string)($answer['source_text'] ?? ''));
            $sourceType = (string)($answer['source_type'] ?? 'human');
            if (!in_array($sourceType, ['human', 'ai'], true)) {
                $sourceType = 'human';
            }
            $position = isset($answer['position']) && $answer['position'] !== '' && is_numeric($answer['position'])
                ? (int)$answer['position']
                : null;
            $isCorrect = (string)($answer['is_correct'] ?? '0') === '1';

            $isMeaningful = $id !== null || $sourceText !== '' || $isCorrect || $position !== null;
            if (!$isMeaningful) {
                continue;
            }

            $row = [
                'source_type' => $sourceType,
                'source_text' => $sourceText,
                'position' => $position,
                'is_correct' => $isCorrect,
            ];
            if ($id !== null) {
                $row['id'] = $id;
            }
            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * Resolve current language id with fallback.
     *
     * @param string $langCode Requested language code.
     * @return int|null
     */
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
            if (method_exists($this, 'logAdminAction')) {
                $this->logAdminAction('admin_delete_question', ['id' => $question->id]);
            }
            $this->Flash->success(__('The question has been deleted.'));
        } else {
            $this->Flash->error(__('The question could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'action' => 'index',
            'lang' => $this->request->getParam('lang'),
            '?' => $this->request->getQueryParams(),
        ]);
    }

    /**
     * Toggle active state for a question.
     *
     * @param string|null $id Question id.
     * @return \Cake\Http\Response|null
     */
    public function toggleActive(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $question = $this->Questions->get($id);
        $question->is_active = !$question->is_active;
        if ($this->Questions->save($question)) {
            $this->Flash->success(
                $question->is_active
                    ? __('The question has been activated.')
                    : __('The question has been deactivated.'),
            );
        } else {
            $this->Flash->error(__('The question status could not be changed. Please, try again.'));
        }

        return $this->redirect([
            'action' => 'index',
            'lang' => $this->request->getParam('lang'),
            '?' => $this->request->getQueryParams(),
        ]);
    }

    /**
     * Toggle needs_review flag for a question.
     *
     * @param string|null $id Question id.
     * @return \Cake\Http\Response|null
     */
    public function toggleNeedsReview(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $question = $this->Questions->get($id);
        $question->needs_review = !$question->needs_review;
        if ($this->Questions->save($question)) {
            $this->Flash->success(
                $question->needs_review
                    ? __('The question has been flagged for review.')
                    : __('The review flag has been cleared.'),
            );
        } else {
            $this->Flash->error(__('The review flag could not be changed. Please, try again.'));
        }

        return $this->redirect([
            'action' => 'index',
            'lang' => $this->request->getParam('lang'),
            '?' => $this->request->getQueryParams(),
        ]);
    }

    /**
     * Bulk actions for the index table.
     *
     * @return \\Cake\\Http\\Response|null
     */
    public function bulk(): ?Response
    {
        $this->request->allowMethod(['post']);

        $returnFilters = $this->extractReturnFilters();
        $action = (string)$this->request->getData('bulk_action');
        $rawIds = $this->request->getData('ids');
        $ids = is_array($rawIds) ? $rawIds : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0)));

        if (!$ids) {
            $this->Flash->error(__('Select at least one item.'));

            return $this->redirect([
                'action' => 'index',
                'lang' => $this->request->getParam('lang'),
                '?' => $returnFilters,
            ]);
        }

        if (!in_array($action, ['delete', 'activate', 'deactivate', 'mark_review', 'unmark_review'], true)) {
            $this->Flash->error(__('Invalid bulk action.'));

            return $this->redirect([
                'action' => 'index',
                'lang' => $this->request->getParam('lang'),
                '?' => $returnFilters,
            ]);
        }

        $deleted = 0;
        $updated = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $entity = $this->Questions->get((string)$id);
                if ($action === 'delete') {
                    if ($this->Questions->delete($entity)) {
                        $deleted += 1;
                        if (method_exists($this, 'logAdminAction')) {
                            $this->logAdminAction('admin_delete_question', ['id' => $entity->id]);
                        }
                    } else {
                        $failed += 1;
                    }
                } elseif (in_array($action, ['activate', 'deactivate'], true)) {
                    $entity->is_active = $action === 'activate';
                    if ($this->Questions->save($entity)) {
                        $updated += 1;
                    } else {
                        $failed += 1;
                    }
                } else {
                    $entity->needs_review = $action === 'mark_review';
                    if ($this->Questions->save($entity)) {
                        $updated += 1;
                    } else {
                        $failed += 1;
                    }
                }
            } catch (Throwable) {
                $failed += 1;
            }
        }

        if ($deleted > 0) {
            $this->Flash->success(__('Deleted {0} item(s).', $deleted));
        }
        if ($updated > 0) {
            if ($action === 'activate') {
                $this->Flash->success(__('Activated {0} item(s).', $updated));
            } elseif ($action === 'deactivate') {
                $this->Flash->success(__('Deactivated {0} item(s).', $updated));
            } elseif ($action === 'mark_review') {
                $this->Flash->success(__('Flagged {0} item(s) for review.', $updated));
            } else {
                $this->Flash->success(__('Cleared review flag for {0} item(s).', $updated));
            }
        }
        if ($failed > 0) {
            $this->Flash->error(
                $action === 'delete'
                    ? __('Could not delete {0} item(s).', $failed)
                    : __('Could not update {0} item(s).', $failed),
            );
        }

        return $this->redirect([
            'action' => 'index',
            'lang' => $this->request->getParam('lang'),
            '?' => $returnFilters,
        ]);
    }

    /**
     * Extract and normalize filter query values for redirect targets.
     *
     * @return array<string, string>
     */
    private function extractReturnFilters(): array
    {
        $raw = $this->request->getData('return_filters');
        if (!is_array($raw)) {
            return [];
        }

        $allowed = ['category', 'question_type', 'is_active', 'source_type', 'needs_review'];
        $filters = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }
            $value = trim((string)$raw[$key]);
            if ($value === '') {
                continue;
            }
            $filters[$key] = $value;
        }

        return $filters;
    }
}
