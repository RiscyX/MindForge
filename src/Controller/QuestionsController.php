<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AdminActivityLogService;
use App\Service\BulkActionService;
use App\Service\LanguageResolverService;
use App\Service\QuestionEditorService;
use App\Service\QuestionIndexService;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;

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
        $languageId = (new LanguageResolverService())->resolveId($langCode);
        $filters = [
            'category' => (string)$this->request->getQuery('category', ''),
            'question_type' => (string)$this->request->getQuery('question_type', ''),
            'is_active' => (string)$this->request->getQuery('is_active', ''),
            'source_type' => (string)$this->request->getQuery('source_type', ''),
            'needs_review' => (string)$this->request->getQuery('needs_review', ''),
        ];

        $indexService = new QuestionIndexService();
        $questions = $indexService->getFilteredQuestions($filters, $languageId);
        $categoryOptions = $indexService->getCategoryOptions($languageId);
        $staticOptions = $indexService->getStaticFilterOptions();

        $questionTypeOptions = $staticOptions['questionTypeOptions'];
        $sourceTypeOptions = $staticOptions['sourceTypeOptions'];
        $activeOptions = $staticOptions['activeOptions'];
        $needsReviewOptions = $staticOptions['needsReviewOptions'];

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
        $languageId = (new LanguageResolverService())->resolveId($langCode);

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
            $question = $this->Questions->patchEntity($question, $this->request->getData(), [
                'fields' => [
                    'test_id',
                    'category_id',
                    'difficulty_id',
                    'question_type',
                    'source_type',
                    'is_active',
                    'needs_review',
                    'position',
                    'question_translations',
                    'answers',
                ],
                'associated' => [
                    'QuestionTranslations' => [
                        'fields' => ['id', 'language_id', 'content', 'explanation'],
                    ],
                    'Answers' => [
                        'fields' => [
                            'id', 'is_correct', 'source_type',
                            'match_side', 'match_group', 'position', 'answer_translations',
                        ],
                        'associated' => [
                            'AnswerTranslations' => [
                                'fields' => ['id', 'language_id', 'content'],
                            ],
                        ],
                    ],
                ],
            ]);
            if ($this->Questions->save($question)) {
                $this->Flash->success(__('The question has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The question could not be saved. Please, try again.'));
        }
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = (new LanguageResolverService())->resolveId($langCode);

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
            $editorService = new QuestionEditorService();
            $data = $this->request->getData();
            $data['answers'] = $editorService->normalizeAnswersPayload((array)($data['answers'] ?? []));

            $question = $this->Questions->patchEntity($question, $data, [
                'fields' => [
                    'test_id',
                    'category_id',
                    'difficulty_id',
                    'question_type',
                    'source_type',
                    'is_active',
                    'needs_review',
                    'position',
                    'question_translations',
                    'answers',
                ],
                'associated' => [
                    'QuestionTranslations' => [
                        'fields' => ['id', 'language_id', 'content', 'explanation'],
                    ],
                    'Answers' => [
                        'fields' => [
                            'id', 'is_correct', 'source_type',
                            'match_side', 'match_group', 'position', 'answer_translations',
                        ],
                        'associated' => [
                            'AnswerTranslations' => [
                                'fields' => ['id', 'language_id', 'content'],
                            ],
                        ],
                    ],
                ],
            ]);

            $editorService->validateCorrectAnswer($question);

            if (!$question->getErrors() && $this->Questions->save($question, ['associated' => ['Answers']])) {
                $this->Flash->success(__('The question has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }

            $this->Flash->error(__('The question could not be saved. Please, try again.'));
        }
        $langCode = (string)$this->request->getParam('lang', 'en');
        $languageId = (new LanguageResolverService())->resolveId($langCode);

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
            (new AdminActivityLogService())->log($this->request, 'admin_delete_question', ['id' => $question->id]);
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
        $bulkService = new BulkActionService();
        $ids = $bulkService->sanitizeIds($this->request->getData('ids'));

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

        if ($action === 'delete') {
            $result = $bulkService->bulkDelete('Questions', $ids);
            if ($result['deleted'] > 0) {
                (new AdminActivityLogService())->log($this->request, 'admin_bulk_delete_questions', [
                    'count' => $result['deleted'],
                    'ids' => implode(',', $ids),
                ]);
                $this->Flash->success(__('Deleted {0} item(s).', $result['deleted']));
            }
            if ($result['failed'] > 0) {
                $this->Flash->error(__('Could not delete {0} item(s).', $result['failed']));
            }
        } elseif (in_array($action, ['activate', 'deactivate'], true)) {
            $isActive = $action === 'activate';
            $updated = $bulkService->bulkUpdateField('Questions', $ids, 'is_active', $isActive);
            if ($updated > 0) {
                $this->Flash->success(
                    $isActive
                        ? __('Activated {0} item(s).', $updated)
                        : __('Deactivated {0} item(s).', $updated),
                );
            }
        } else {
            $needsReview = $action === 'mark_review';
            $updated = $bulkService->bulkUpdateField('Questions', $ids, 'needs_review', $needsReview);
            if ($updated > 0) {
                $this->Flash->success(
                    $needsReview
                        ? __('Flagged {0} item(s) for review.', $updated)
                        : __('Cleared review flag for {0} item(s).', $updated),
                );
            }
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
