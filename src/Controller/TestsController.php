<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\ActivityLog;
use App\Model\Entity\Language;
use App\Model\Entity\Question;
use App\Model\Entity\Role;
use App\Service\AiService;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;
use Cake\View\JsonView;
use Exception;
use Throwable;
use function Cake\Core\env;

/**
 * Tests Controller
 *
 * @property \App\Model\Table\TestsTable $Tests
 */
class TestsController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        $identity = $this->Authentication->getIdentity();
        $roleId = $identity ? (int)$identity->get('role_id') : null;

        // Public/consumer-facing pages use the default layout.
        if (
            !$this->request->getParam('prefix') &&
            ($roleId === null || $roleId === Role::USER || $roleId === Role::CREATOR)
        ) {
            $this->viewBuilder()->setLayout('default');

            return;
        }

        $this->viewBuilder()->setLayout('admin');
    }

    /**
     * @return array<\class-string>
     */
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $identity = $this->Authentication->getIdentity();
        $roleId = $identity ? (int)$identity->get('role_id') : null;
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $isCreatorCatalog = $roleId === Role::CREATOR && !$this->request->getParam('prefix');

        $langCode = $this->request->getParam('lang');
        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        // Fallback
        if (!$language) {
             $language = $this->fetchTable('Languages')->find()->first();
        }

        $query = $this->Tests
            ->find()
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($language) {
                    return $q->where(['CategoryTranslations.language_id' => $language->id ?? null]);
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($language) {
                    return $q->where(['DifficultyTranslations.language_id' => $language->id ?? null]);
                },
                'TestTranslations' => function ($q) use ($language) {
                    return $q->where(['TestTranslations.language_id' => $language->id ?? null]);
                },
            ])
            ->orderByAsc('Tests.id');

        // Catalog for regular users: only public tests.
        if ($roleId === Role::USER && !$this->request->getParam('prefix')) {
            $query->where(['Tests.is_public' => true]);
        }

        $filters = [
            'q' => trim((string)$this->request->getQuery('q', '')),
            'category' => (string)$this->request->getQuery('category', ''),
            'difficulty' => (string)$this->request->getQuery('difficulty', ''),
            'visibility' => (string)$this->request->getQuery('visibility', ''),
            'sort' => (string)$this->request->getQuery('sort', 'latest'),
        ];

        $categoryOptions = [];
        $difficultyOptions = [];

        if ($isCreatorCatalog && $userId !== null) {
            $query->where(['Tests.created_by' => $userId]);

            $creatorBase = $this->Tests->find()
                ->where(['Tests.created_by' => $userId]);

            $categoryRows = (clone $creatorBase)
                ->select(['category_id'])
                ->where(['Tests.category_id IS NOT' => null])
                ->distinct(['Tests.category_id'])
                ->enableHydration(false)
                ->all();
            $categoryIds = [];
            foreach ($categoryRows as $row) {
                $cid = (int)($row['category_id'] ?? 0);
                if ($cid > 0) {
                    $categoryIds[] = $cid;
                }
            }

            $difficultyRows = (clone $creatorBase)
                ->select(['difficulty_id'])
                ->where(['Tests.difficulty_id IS NOT' => null])
                ->distinct(['Tests.difficulty_id'])
                ->enableHydration(false)
                ->all();
            $difficultyIds = [];
            foreach ($difficultyRows as $row) {
                $did = (int)($row['difficulty_id'] ?? 0);
                if ($did > 0) {
                    $difficultyIds[] = $did;
                }
            }

            if ($categoryIds) {
                $categoryTranslations = $this->fetchTable('CategoryTranslations')->find()
                    ->select(['category_id', 'name'])
                    ->where([
                        'CategoryTranslations.category_id IN' => $categoryIds,
                        'CategoryTranslations.language_id' => $language->id ?? null,
                    ])
                    ->enableHydration(false)
                    ->all();

                foreach ($categoryTranslations as $row) {
                    $cid = (int)($row['category_id'] ?? 0);
                    $name = trim((string)($row['name'] ?? ''));
                    if ($cid > 0 && $name !== '') {
                        $categoryOptions[$cid] = $name;
                    }
                }
                asort($categoryOptions);
            }

            if ($difficultyIds) {
                $difficultyTranslations = $this->fetchTable('DifficultyTranslations')->find()
                    ->select(['difficulty_id', 'name'])
                    ->where([
                        'DifficultyTranslations.difficulty_id IN' => $difficultyIds,
                        'DifficultyTranslations.language_id' => $language->id ?? null,
                    ])
                    ->enableHydration(false)
                    ->all();

                foreach ($difficultyTranslations as $row) {
                    $did = (int)($row['difficulty_id'] ?? 0);
                    $name = trim((string)($row['name'] ?? ''));
                    if ($did > 0 && $name !== '') {
                        $difficultyOptions[$did] = $name;
                    }
                }
                asort($difficultyOptions);
            }

            if ($filters['category'] !== '' && ctype_digit($filters['category'])) {
                $query->where(['Tests.category_id' => (int)$filters['category']]);
            }

            if ($filters['difficulty'] !== '' && ctype_digit($filters['difficulty'])) {
                $query->where(['Tests.difficulty_id' => (int)$filters['difficulty']]);
            }

            if ($filters['visibility'] === 'public') {
                $query->where(['Tests.is_public' => true]);
            } elseif ($filters['visibility'] === 'private') {
                $query->where(['Tests.is_public' => false]);
            }

            if ($filters['q'] !== '') {
                $qLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
                $query
                    ->matching('TestTranslations', function ($q) use ($language, $qLike) {
                        $query = $q->where([
                            'OR' => [
                                'TestTranslations.title LIKE' => $qLike,
                                'TestTranslations.description LIKE' => $qLike,
                            ],
                        ]);
                        if ($language && $language->id !== null) {
                            $query = $query->where(['TestTranslations.language_id' => (int)$language->id]);
                        }

                        return $query;
                    })
                    ->distinct(['Tests.id']);
            }

            $sort = $filters['sort'];
            if ($sort === 'oldest') {
                $query->orderByAsc('Tests.id');
            } elseif ($sort === 'updated') {
                $query->orderByDesc('Tests.updated_at')->orderByDesc('Tests.id');
            } else {
                $query->orderByDesc('Tests.id');
                $filters['sort'] = 'latest';
            }
        }

        $tests = $query->all();

        // Difficulty ordering is admin-configurable, but ids are increasing.
        // Expose a stable difficulty_id -> rank mapping for UI color coding.
        $difficultyRanks = [];
        $difficultyCount = 0;
        try {
            $difficultyIds = $this->fetchTable('Difficulties')->find()
                ->select(['id'])
                ->orderByAsc('id')
                ->enableHydration(false)
                ->all()
                ->toList();

            $difficultyCount = count($difficultyIds);
            $rank = 0;
            foreach ($difficultyIds as $row) {
                $did = (int)($row['id'] ?? 0);
                if ($did > 0) {
                    $difficultyRanks[$did] = $rank;
                    $rank += 1;
                }
            }
        } catch (Throwable) {
            // Keep UI working if difficulties table is unavailable.
        }

        $this->set(compact(
            'tests',
            'roleId',
            'difficultyRanks',
            'difficultyCount',
            'isCreatorCatalog',
            'filters',
            'categoryOptions',
            'difficultyOptions',
        ));
    }

    /**
     * Start a test as the currently logged-in user.
     *
     * Creates a TestAttempt row and redirects to the take flow.
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null
     */
    public function start(?string $id = null): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;

        if ($userId === null) {
            $redirectUrl = Router::url(['controller' => 'Tests', 'action' => 'start', $id, 'lang' => $lang], true);

            return $this->redirect([
                'controller' => 'Users',
                'action' => 'login',
                'lang' => $lang,
                '?' => ['redirect' => $redirectUrl],
            ]);
        }

        $roleId = (int)($identity->get('role_id') ?? 0);
        if ($roleId === Role::USER) {
            $test = $this->Tests->find()
                ->where(['Tests.id' => $id, 'Tests.is_public' => true])
                ->contain(['Categories', 'Difficulties'])
                ->first();
        } else {
            $test = $this->Tests->find()
                ->where(['Tests.id' => $id])
                ->contain(['Categories', 'Difficulties'])
                ->first();
        }

        if (!$test) {
            $this->Flash->error(__('Quiz not found.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;

        $questionsCount = (int)$this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$test->id,
                'Questions.is_active' => true,
            ])
            ->count();

        if ($questionsCount <= 0) {
            $this->Flash->error(__('This quiz has no active questions yet.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $now = FrozenTime::now();
        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->newEmptyEntity();
        $attempt = $attempts->patchEntity($attempt, [
            'user_id' => $userId,
            'test_id' => (int)$test->id,
            'category_id' => $test->category_id,
            'difficulty_id' => $test->difficulty_id,
            'language_id' => $languageId,
            'started_at' => $now,
            'created_at' => $now,
            'total_questions' => $questionsCount,
            'correct_answers' => 0,
        ]);

        if (!$attempts->save($attempt)) {
            $this->Flash->error(__('Could not start the quiz. Please try again.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        return $this->redirect(['action' => 'take', $attempt->id, 'lang' => $lang]);
    }

    /**
     * Render the quiz attempt form.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|void
     */
    public function take(?string $id = null)
    {
        $this->request->allowMethod(['get']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to start a quiz.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id, contain: ['Tests']);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }
        if ($attempt->finished_at !== null) {
            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        // Use the currently selected UI language for translations.
        // If the attempt is still in progress, persist the user's language switch.
        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;
        if ($languageId !== null && (int)($attempt->language_id ?? 0) !== $languageId) {
            try {
                $attempt->language_id = $languageId;
                $attempts->save($attempt, ['validate' => false]);
            } catch (Throwable) {
                // Non-blocking: keep rendering with the selected language.
            }
        }

        $test = $this->Tests->find()
            ->where(['Tests.id' => (int)$attempt->test_id])
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['CategoryTranslations.language_id' => $languageId]);
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['DifficultyTranslations.language_id' => $languageId]);
                },
                'TestTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['TestTranslations.language_id' => $languageId]);
                },
            ])
            ->first();

        $questions = $this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$attempt->test_id,
                'Questions.is_active' => true,
            ])
            ->orderByAsc('Questions.position')
            ->orderByAsc('Questions.id')
            ->contain([
                'QuestionTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
                'Answers' => function ($q) {
                    return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
                },
                'Answers.AnswerTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['AnswerTranslations.language_id' => $languageId]);
                },
            ])
            ->all();

        $this->set(compact('attempt', 'test', 'questions'));
    }

    /**
     * Submit a quiz attempt.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|null
     */
    public function submit(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to submit a quiz.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }
        if ($attempt->finished_at !== null) {
            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        $existingCount = (int)$this->fetchTable('TestAttemptAnswers')->find()
            ->where(['test_attempt_id' => (int)$attempt->id])
            ->count();
        if ($existingCount > 0) {
            $this->Flash->error(__('This attempt has already been submitted.'));

            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        // Submit should follow the currently selected UI language.
        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;
        if ($languageId !== null && (int)($attempt->language_id ?? 0) !== $languageId) {
            try {
                $attempt->language_id = $languageId;
                $attempts->save($attempt, ['validate' => false]);
            } catch (Throwable) {
                // Non-blocking.
            }
        }
        $questions = $this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$attempt->test_id,
                'Questions.is_active' => true,
            ])
            ->orderByAsc('Questions.position')
            ->orderByAsc('Questions.id')
            ->contain([
                'Answers' => function ($q) {
                    return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
                },
                'Answers.AnswerTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['AnswerTranslations.language_id' => $languageId]);
                },
            ])
            ->all()
            ->toList();

        if (!$questions) {
            $this->Flash->error(__('This quiz has no active questions.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $answersInput = $this->request->getData('answers');
        $answersInput = is_array($answersInput) ? $answersInput : [];

        $now = FrozenTime::now();
        $attemptAnswersTable = $this->fetchTable('TestAttemptAnswers');
        $attemptAnswerEntities = [];
        $correct = 0;

        foreach ($questions as $question) {
            $qid = (int)$question->id;
            $questionType = (string)$question->question_type;

            $chosen = $answersInput[$qid] ?? null;
            $chosenAnswerId = null;
            $userAnswerText = null;
            $isCorrect = false;

            if ($questionType === Question::TYPE_TEXT) {
                $userAnswerText = trim((string)$chosen);

                $correctTexts = [];
                foreach ($question->answers ?? [] as $answer) {
                    if (!$answer->is_correct) {
                        continue;
                    }
                    $t = '';
                    if (!empty($answer->answer_translations)) {
                        $t = (string)($answer->answer_translations[0]->content ?? '');
                    }
                    if ($t === '' && isset($answer->source_text)) {
                        $t = (string)$answer->source_text;
                    }
                    $t = trim($t);
                    if ($t !== '') {
                        $correctTexts[] = strtolower($t);
                    }
                }
                if ($userAnswerText !== '' && $correctTexts) {
                    $isCorrect = in_array(strtolower($userAnswerText), $correctTexts, true);
                }
            } else {
                $chosenAnswerId = is_numeric($chosen) ? (int)$chosen : null;
                $answerCorrectMap = [];
                foreach ($question->answers ?? [] as $answer) {
                    $answerCorrectMap[(int)$answer->id] = (bool)$answer->is_correct;
                }
                if ($chosenAnswerId !== null && $chosenAnswerId > 0) {
                    if (!array_key_exists($chosenAnswerId, $answerCorrectMap)) {
                        $chosenAnswerId = null;
                        $isCorrect = false;
                    } else {
                        $isCorrect = (bool)$answerCorrectMap[$chosenAnswerId];
                    }
                }
            }

            if ($isCorrect) {
                $correct += 1;
            }

            $attemptAnswerEntities[] = $attemptAnswersTable->newEntity([
                'test_attempt_id' => (int)$attempt->id,
                'question_id' => $qid,
                'answer_id' => $chosenAnswerId,
                'user_answer_text' => $userAnswerText,
                'is_correct' => $isCorrect,
                'answered_at' => $now,
            ]);
        }

        $total = count($questions);
        $pct = $total > 0 ? $correct / $total * 100.0 : 0.0;
        $score = number_format($pct, 2, '.', '');

        $conn = $attempts->getConnection();
        $conn->begin();
        try {
            if (!$attemptAnswersTable->saveMany($attemptAnswerEntities)) {
                throw new Exception('Failed to save answers.');
            }

            $attempt->finished_at = $now;
            $attempt->total_questions = $total;
            $attempt->correct_answers = $correct;
            $attempt->score = $score;

            if (!$attempts->save($attempt)) {
                throw new Exception('Failed to finalize attempt.');
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $this->Flash->error(__('Could not submit your answers. Please try again.'));

            return $this->redirect(['action' => 'take', $attempt->id, 'lang' => $lang]);
        }

        return $this->redirect(['action' => 'review', $attempt->id, 'lang' => $lang]);
    }

    /**
     * Show quiz attempt results.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|void
     */
    public function result(?string $id = null)
    {
        $this->request->allowMethod(['get']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to view results.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id, contain: ['Tests']);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }

        $this->set(compact('attempt'));
    }

    /**
     * Review a finished quiz attempt, one question at a time.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|void
     */
    public function review(?string $id = null)
    {
        $this->request->allowMethod(['get']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to view results.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }

        if ($attempt->finished_at === null) {
            return $this->redirect(['action' => 'take', $attempt->id, 'lang' => $lang]);
        }

        // Review uses the currently selected UI language for translations.
        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;

        $test = $this->Tests->find()
            ->where(['Tests.id' => (int)$attempt->test_id])
            ->contain([
                'TestTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['TestTranslations.language_id' => $languageId]);
                },
            ])
            ->first();

        $questions = $this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$attempt->test_id,
                'Questions.is_active' => true,
            ])
            ->orderByAsc('Questions.position')
            ->orderByAsc('Questions.id')
            ->contain([
                'QuestionTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
                'Answers' => function ($q) {
                    return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
                },
                'Answers.AnswerTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['AnswerTranslations.language_id' => $languageId]);
                },
            ])
            ->all();

        $attemptAnswers = $this->fetchTable('TestAttemptAnswers')->find()
            ->where(['test_attempt_id' => (int)$attempt->id])
            ->all()
            ->indexBy('question_id')
            ->toArray();

        $this->set(compact('attempt', 'test', 'questions', 'attemptAnswers'));
    }

    /**
     * Bulk actions for the index table.
     *
     * @return \Cake\Http\Response|null
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
                $entity = $this->Tests->get((string)$id);
                if ($this->Tests->delete($entity)) {
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

    /**
     * Resolve current route language with fallback.
     *
     * @return \App\Model\Entity\Language|null
     */
    private function resolveLanguage(): ?Language
    {
        $langCode = (string)$this->request->getParam('lang', 'en');
        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        if ($language) {
            return $language;
        }

        return $this->fetchTable('Languages')->find()->first();
    }

    /**
     * View method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $identity = $this->Authentication->getIdentity();
        $roleId = $identity ? (int)$identity->get('role_id') : null;

        // Regular user detail page: keep it minimal and focused on user stats.
        if (!$this->request->getParam('prefix') && ($roleId === null || $roleId === Role::USER)) {
            $lang = (string)$this->request->getParam('lang', 'en');
            $language = $this->resolveLanguage();
            $languageId = $language ? (int)$language->id : null;

            $test = $this->Tests->find()
                ->where([
                    'Tests.id' => $id,
                    'Tests.is_public' => true,
                ])
                ->contain([
                    'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['CategoryTranslations.language_id' => $languageId]) : $q;
                    },
                    'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['DifficultyTranslations.language_id' => $languageId]) : $q;
                    },
                    'TestTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['TestTranslations.language_id' => $languageId]) : $q;
                    },
                ])
                ->first();

            if (!$test) {
                $this->Flash->error(__('Quiz not found.'));

                return $this->redirect(['action' => 'index', 'lang' => $lang]);
            }

            $userId = $identity ? (int)$identity->getIdentifier() : null;
            $attemptsCount = 0;
            $finishedCount = 0;
            $bestAttempt = null;
            $lastAttempt = null;

            if ($userId !== null) {
                $attemptsTable = $this->fetchTable('TestAttempts');

                $attemptsCount = (int)$attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                    ])
                    ->count();

                $finishedCount = (int)$attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                        'TestAttempts.finished_at IS NOT' => null,
                    ])
                    ->count();

                $bestAttempt = $attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                        'TestAttempts.finished_at IS NOT' => null,
                    ])
                    ->orderByDesc('TestAttempts.score')
                    ->orderByDesc('TestAttempts.correct_answers')
                    ->orderByDesc('TestAttempts.finished_at')
                    ->orderByDesc('TestAttempts.id')
                    ->first();

                $lastAttempt = $attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                        'TestAttempts.finished_at IS NOT' => null,
                    ])
                    ->orderByDesc('TestAttempts.finished_at')
                    ->orderByDesc('TestAttempts.id')
                    ->first();
            }

            $this->set(compact('test', 'attemptsCount', 'finishedCount', 'bestAttempt', 'lastAttempt'));
            $this->viewBuilder()->setTemplate('catalog_view');

            return;
        }

        if (!$this->request->getParam('prefix') && $roleId === Role::CREATOR) {
            $lang = (string)$this->request->getParam('lang', 'en');
            $language = $this->resolveLanguage();
            $languageId = $language ? (int)$language->id : null;
            $userId = $identity ? (int)$identity->getIdentifier() : null;

            $test = $this->Tests->find()
                ->where([
                    'Tests.id' => $id,
                    'Tests.created_by' => $userId,
                ])
                ->contain([
                    'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['CategoryTranslations.language_id' => $languageId]) : $q;
                    },
                    'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['DifficultyTranslations.language_id' => $languageId]) : $q;
                    },
                    'TestTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['TestTranslations.language_id' => $languageId]) : $q;
                    },
                ])
                ->first();

            if (!$test) {
                $this->Flash->error(__('Quiz not found.'));

                return $this->redirect(['action' => 'index', 'lang' => $lang]);
            }

            $attemptsTable = $this->fetchTable('TestAttempts');
            $quizAttempts = $attemptsTable->find()->where(['TestAttempts.test_id' => (int)$test->id]);

            $attemptsCount = (int)(clone $quizAttempts)->count();
            $finishedCount = (int)(clone $quizAttempts)
                ->where(['TestAttempts.finished_at IS NOT' => null])
                ->count();

            $finishedWithScore = (clone $quizAttempts)
                ->where([
                    'TestAttempts.finished_at IS NOT' => null,
                    'TestAttempts.score IS NOT' => null,
                ]);

            $avgScoreRow = (clone $finishedWithScore)
                ->select(['avg_score' => $attemptsTable->find()->func()->avg('TestAttempts.score')])
                ->enableHydration(false)
                ->first();
            $bestScoreRow = (clone $finishedWithScore)
                ->select(['best_score' => $attemptsTable->find()->func()->max('TestAttempts.score')])
                ->enableHydration(false)
                ->first();

            $correctnessRow = (clone $quizAttempts)
                ->where([
                    'TestAttempts.finished_at IS NOT' => null,
                    'TestAttempts.correct_answers IS NOT' => null,
                    'TestAttempts.total_questions IS NOT' => null,
                    'TestAttempts.total_questions >' => 0,
                ])
                ->select([
                    'sum_correct' => $attemptsTable->find()->func()->sum('TestAttempts.correct_answers'),
                    'sum_total' => $attemptsTable->find()->func()->sum('TestAttempts.total_questions'),
                ])
                ->enableHydration(false)
                ->first();

            $sumCorrect = (float)($correctnessRow['sum_correct'] ?? 0);
            $sumTotal = (float)($correctnessRow['sum_total'] ?? 0);

            $stats = [
                'attempts' => $attemptsCount,
                'finished' => $finishedCount,
                'completionRate' => $attemptsCount > 0 ? $finishedCount / $attemptsCount * 100.0 : 0.0,
                'avgScore' => (float)($avgScoreRow['avg_score'] ?? 0.0),
                'bestScore' => (float)($bestScoreRow['best_score'] ?? 0.0),
                'avgCorrectRate' => $sumTotal > 0 ? $sumCorrect / $sumTotal * 100.0 : 0.0,
            ];

            $this->set(compact('test', 'stats'));
            $this->viewBuilder()->setTemplate('creator_view');

            return;
        }

        $test = $this->Tests->get($id, contain: [
            'Categories',
            'Difficulties',
            'AiRequests',
            'Questions',
            'TestAttempts',
            'TestTranslations',
            'UserFavoriteTests',
        ]);
        $this->set(compact('test'));
    }

    /**
     * Public/user-facing details page.
     *
     * Friendly URL: /{lang}/tests/{id}/details
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void
     */
    public function details(?string $id = null)
    {
        return $this->view($id);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $test = $this->Tests->newEmptyEntity();
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $userId = $this->Authentication->getIdentity()?->getIdentifier();
            $data['created_by'] = $userId;

            // Inject category_id into questions if present in test data
            if (!empty($data['category_id']) && !empty($data['questions'])) {
                $data['number_of_questions'] = count($data['questions']);
                foreach ($data['questions'] as &$question) {
                    $question['category_id'] = $data['category_id'];
                    // Propagate created_by as well
                    $question['created_by'] = $userId;
                }
            }

            $test = $this->Tests->patchEntity($test, $data, [
                'associated' => [
                    'Questions' => ['associated' => [
                        'Answers' => ['associated' => ['AnswerTranslations']],
                        'QuestionTranslations',
                    ]],
                    'TestTranslations',
                ],
            ]);

            if ($this->Tests->save($test)) {
                $this->Flash->success(__('The test has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            // Log validation errors
            // \Cake\Log\Log::error('Test save failed: ' . json_encode($test->getErrors()));
            $this->Flash->error(__('The test could not be saved. Please, try again.'));
            if (Configure::read('debug')) {
                 $this->Flash->error(json_encode($test->getErrors()));
            }
        }

        $langCode = $this->request->getParam('lang');
        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        // Fallback if exact/fuzzy match fails (prevention for null crash)
        if (!$language) {
             $language = $this->fetchTable('Languages')->find()->first();
        }

        $languageId = $language ? (int)$language->id : null;

        $categories = [];
        $difficulties = [];

        if ($languageId) {
            $categories = $this->Tests->Categories->CategoryTranslations->find('list', [
                'keyField' => 'category_id',
                'valueField' => 'name',
            ])
            ->where(['language_id' => $languageId])
            ->all();

            $difficulties = $this->Tests->Difficulties->DifficultyTranslations->find('list', [
                'keyField' => 'difficulty_id',
                'valueField' => 'name',
            ])
            ->where(['language_id' => $languageId])
            ->all();
        }

        $languages = $this->fetchTable('Languages')->find('list')->all();
        $languagesMeta = $this->fetchTable('Languages')->find()
            ->select(['id', 'code', 'name'])
            ->orderByAsc('id')
            ->enableHydration(false)
            ->toArray();
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $aiGenerationLimit = $this->getAiGenerationLimitInfo($userId);
        $currentLanguageId = $languageId;
        $this->set(compact(
            'test',
            'categories',
            'difficulties',
            'languages',
            'languagesMeta',
            'aiGenerationLimit',
            'currentLanguageId',
        ));
    }

    /**
     * Edit method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $test = $this->Tests->get($id, contain: [
            'TestTranslations',
            'Questions' => function ($q) {
                return $q->orderByAsc('position')
                    ->contain([
                        'QuestionTranslations',
                        'Answers' => function ($q) {
                            return $q->orderByAsc('id')
                                ->contain(['AnswerTranslations']);
                        },
                    ]);
            },
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            $userId = $this->Authentication->getIdentity()?->getIdentifier();

            if (!empty($data['questions'])) {
                $data['number_of_questions'] = count($data['questions']);
                foreach ($data['questions'] as &$question) {
                    if (!empty($data['category_id'])) {
                        $question['category_id'] = $data['category_id'];
                    }
                    if (empty($question['id'])) {
                        $question['created_by'] = $userId;
                    }
                }
            }

            // Set save strategy to replace to handle deletions
            $this->Tests->Questions->setSaveStrategy('replace');
            $this->Tests->Questions->getTarget()->Answers->setSaveStrategy('replace');

            $test = $this->Tests->patchEntity($test, $data, [
                'associated' => [
                    'Questions' => ['associated' => [
                        'Answers' => ['associated' => ['AnswerTranslations']],
                        'QuestionTranslations',
                    ]],
                    'TestTranslations',
                ],
            ]);

            if ($this->Tests->save($test)) {
                $this->Flash->success(__('The test has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The test could not be saved. Please, try again.'));
            if (Configure::read('debug')) {
                 $this->Flash->error(json_encode($test->getErrors()));
            }
        }

        $langCode = $this->request->getParam('lang');
        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        // Fallback if exact/fuzzy match fails
        if (!$language) {
             $language = $this->fetchTable('Languages')->find()->first();
        }

        $languageId = $language ? (int)$language->id : null;

        $categories = [];
        $difficulties = [];

        if ($languageId) {
            $categories = $this->Tests->Categories->CategoryTranslations->find('list', [
                'keyField' => 'category_id',
                'valueField' => 'name',
            ])
            ->where(['language_id' => $languageId])
            ->all();

            $difficulties = $this->Tests->Difficulties->DifficultyTranslations->find('list', [
                'keyField' => 'difficulty_id',
                'valueField' => 'name',
            ])
            ->where(['language_id' => $languageId])
            ->all();
        }

        $languages = $this->fetchTable('Languages')->find('list')->all();
        $languagesMeta = $this->fetchTable('Languages')->find()
            ->select(['id', 'code', 'name'])
            ->orderByAsc('id')
            ->enableHydration(false)
            ->toArray();

        // Re-index translations by language_id for the form helper
        $indexedTranslations = [];
        foreach ($test->test_translations as $translation) {
            $indexedTranslations[$translation->language_id] = $translation;
        }
        $test->test_translations = $indexedTranslations;

        // Prepare Questions Data for JS
        $questionsData = [];
        foreach ($test->questions as $question) {
            $qData = [
                'id' => $question->id, // keep ID to update existing
                'type' => $question->question_type,
                'translations' => [],
                'answers' => [],
            ];

            foreach ($question->question_translations as $qt) {
                $qData['translations'][$qt->language_id] = [
                    'id' => $qt->id,
                    'content' => $qt->content,
                ];
            }

            foreach ($question->answers as $answer) {
                $aData = [
                    'id' => $answer->id,
                    'is_correct' => $answer->is_correct,
                    'translations' => [],
                ];
                foreach ($answer->answer_translations as $at) {
                    $aData['translations'][$at->language_id] = [
                        'id' => $at->id,
                        'content' => $at->content,
                    ];
                }
                $qData['answers'][] = $aData;
            }
            $questionsData[] = $qData;
        }

        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $aiGenerationLimit = $this->getAiGenerationLimitInfo($userId);
        $currentLanguageId = $languageId;
        $this->set(compact(
            'test',
            'categories',
            'difficulties',
            'languages',
            'languagesMeta',
            'questionsData',
            'aiGenerationLimit',
            'currentLanguageId',
        ));
    }

    /**
     * Delete method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $test = $this->Tests->get($id);
        if ($this->Tests->delete($test)) {
            $this->Flash->success(__('The test has been deleted.'));
        } else {
            $this->Flash->error(__('The test could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }

    /**
     * Generate test content using AI
     *
     * @return \Cake\Http\Response|null|void
     */
    public function generateWithAi()
    {
        $this->request->allowMethod(['post']);
        $prompt = $this->request->getData('prompt');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;

        $aiGenerationLimit = $this->getAiGenerationLimitInfo($userId);
        if (!$aiGenerationLimit['allowed']) {
            return $this->response->withStatus(429)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'limit_reached' => true,
                    'message' => __('AI generation limit reached. Limit resets tomorrow.'),
                    'resets_at' => $aiGenerationLimit['resets_at_iso'],
                ]));
        }

        if (empty($prompt)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'message' => 'Empty prompt']));
        }

        try {
            // Resolve language context
            $langCode = $this->request->getParam('lang');
            $currentLanguage = $this->fetchTable('Languages')->find()
                ->where(['code LIKE' => $langCode . '%'])
                ->first();

            // Fallback to first language if not found, or leave null?
            if (!$currentLanguage) {
                 $currentLanguage = $this->fetchTable('Languages')->find()->first();
            }
            $currentLanguageId = $currentLanguage ? $currentLanguage->id : null;

            // Get languages to inform AI about required translations
            $languages = $this->fetchTable('Languages')->find('list')->toArray();
            $languagesList = implode(', ', $languages);
            $langIds = array_keys($languages);

            $systemMessage = "You are a professional test creator assistant.
            The user will provide a description of a test.
            You must return a valid JSON object representing the test questions, answers, and translations.

            The available languages are: $languagesList.
            You MUST provide translations for ALL these languages for every
             text field (title, description, question text, answer text).

            Expected JSON format:
            {
                \"translations\": {
                     \"[language_id_1]\": {
                        \"title\": \"Test Title in Language 1\",
                        \"description\": \"Test Description in Language 1\"
                     },
                     \"[language_id_2]\": {
                        \"title\": \"Test Title in Language 2\",
                        \"description\": \"Test Description in Language 2\"
                     }
                },
                \"questions\": [
                    {
                        \"type\": \"multiple_choice\" | \"true_false\" | \"text\",
                        \"translations\": {
                            \"[language_id_1]\": \"Question in Language 1\",
                            \"[language_id_2]\": \"Question in Language 2\"
                        },
                        \"answers\": [
                            {
                                \"is_correct\": true|false,
                                \"translations\": {
                                    \"[language_id_1]\": \"Answer in Language 1\",
                                    \"[language_id_2]\": \"Answer in Language 2\"
                                }
                            }
                        ]
                    }
                ]
            }

            Important rules:
            1. Use the Language IDs (integers) as keys in the
             'translations' objects. Keys: " . implode(', ', $langIds) . "
            2. For 'true_false' questions, provide 2 answers (True and False) with correct translations.
            3. For 'multiple_choice', provide 4 answers.
            There can be multiple correct answers (at least one must be correct).
            4. Make sure the JSON is valid and contains ONLY the JSON object, no markdown formatting.
            ";

            $aiService = new AiService();
            $responseContent = $aiService->generateContent(
                $prompt,
                $systemMessage,
                0.7,
                ['response_format' => ['type' => 'json_object']],
            );

            $json = json_decode($responseContent, true);
            // Save AI Request Log
            $aiRequestsTable = $this->fetchTable('AiRequests');
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $currentLanguageId,
                'source_medium' => 'user_prompt',
                'source_reference' => 'test_generator',
                'type' => 'test_generation',
                'input_payload' => json_encode(['prompt' => $prompt]),
                'output_payload' => $responseContent,
                'status' => 'success',
            ]);
            $aiRequestsTable->save($aiRequest);

            if (json_last_error() !== JSON_ERROR_NONE) {
                 return $this->response->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'Invalid JSON from AI',
                        'debug' => $responseContent,
                    ]));
            }

            // Save Activity Log
            $activityLogsTable = $this->fetchTable('ActivityLogs');
            $activityLog = $activityLogsTable->newEmptyEntity();
            $activityLog = $activityLogsTable->patchEntity($activityLog, [
                'user_id' => $userId,
                'action' => ActivityLog::TYPE_AI_GENERATED_TEST,
                'ip_address' => $this->request->clientIp(),
                'user_agent' => $this->request->getHeaderLine('User-Agent'),
            ]);
            $activityLogsTable->save($activityLog);

            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true, 'data' => $json]));
        } catch (Exception $e) {
             // Log failed attempt if needed? For now just return error.
             return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Translate the current test content into all configured languages using AI.
     *
     * Accepts the source-language content from the form to avoid losing unsaved edits.
     *
     * @param string|null $id Test id (optional, used for logging only).
     * @return \Cake\Http\Response
     */
    public function translateWithAi(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);

        $sourceLanguageId = (int)($this->request->getData('source_language_id') ?? 0);
        $testSource = (array)($this->request->getData('test') ?? []);
        $questionsSource = $this->request->getData('questions');
        $questionsSource = is_array($questionsSource) ? $questionsSource : [];

        $title = trim((string)($testSource['title'] ?? ''));
        $description = trim((string)($testSource['description'] ?? ''));
        if ($sourceLanguageId <= 0 || $title === '') {
            return $this->response
                ->withStatus(422)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'Missing source language or title.',
                ]));
        }

        try {
            $languagesQuery = $this->fetchTable('Languages')->find()->orderByAsc('Languages.id');
            $languages = [];
            $langIds = [];
            foreach ($languagesQuery->all() as $lang) {
                $languages[(int)$lang->id] = (string)($lang->name ?? $lang->code ?? 'Lang ' . $lang->id);
                $langIds[] = (int)$lang->id;
            }

            if (!$languages) {
                throw new Exception('No languages configured.');
            }

            $sourceLanguageName = $languages[$sourceLanguageId] ?? 'Language ' . $sourceLanguageId;
            $languagesList = [];
            foreach ($languages as $lid => $lname) {
                $languagesList[] = $lid . ':' . $lname;
            }

            $sourcePayload = [
                'source_language_id' => $sourceLanguageId,
                'test' => [
                    'title' => $title,
                    'description' => $description,
                ],
                'questions' => [],
            ];

            foreach ($questionsSource as $q) {
                if (!is_array($q)) {
                    continue;
                }

                $qId = isset($q['id']) ? (int)$q['id'] : null;
                $qType = (string)($q['type'] ?? $q['question_type'] ?? '');
                $qContent = trim((string)($q['content'] ?? ''));
                if ($qContent === '') {
                    continue;
                }

                $answersOut = [];
                $answers = $q['answers'] ?? [];
                if (is_array($answers)) {
                    foreach ($answers as $a) {
                        if (!is_array($a)) {
                            continue;
                        }
                        $aId = isset($a['id']) ? (int)$a['id'] : null;
                        $aContent = trim((string)($a['content'] ?? ''));
                        $isCorrect = (bool)($a['is_correct'] ?? false);
                        if ($aContent === '') {
                            continue;
                        }
                        $answersOut[] = [
                            'id' => $aId,
                            'is_correct' => $isCorrect,
                            'content' => $aContent,
                        ];
                    }
                }

                $sourcePayload['questions'][] = [
                    'id' => $qId,
                    'type' => $qType,
                    'content' => $qContent,
                    'answers' => $answersOut,
                ];
            }

            $systemMessage = "You are a professional translator for a quiz/test builder application.\n\n" .
                "Translate the provided source-language content into ALL configured languages.\n" .
                'Configured languages (use the integer language_id as keys): ' . implode(', ', $languagesList) . "\n" .
                "Source language: {$sourceLanguageId}:{$sourceLanguageName}\n\n" .
                "Return ONLY valid JSON, no markdown.\n\n" .
                "Expected JSON format:\n" .
                "{\n" .
                "  \"translations\": {\n" .
                "    \"[language_id]\": { \"title\": \"...\", \"description\": \"...\" }\n" .
                "  },\n" .
                "  \"questions\": [\n" .
                "    {\n" .
                "      \"id\": 123,\n" .
                "      \"type\": \"multiple_choice\"|\"true_false\"|\"text\",\n" .
                "      \"translations\": { \"[language_id]\": \"...\" },\n" .
                "      \"answers\": [\n" .
                '        { \"id\": 456, \"is_correct\": true|false, ' .
                '\"translations\": { \"[language_id]\": \"...\" } }\n' .
                "      ]\n" .
                "    }\n" .
                "  ]\n" .
                "}\n\n" .
                "Rules:\n" .
                "1) Include translations for ALL language_ids listed.\n" .
                "2) Preserve meaning and keep it suitable for a quiz.\n" .
                "3) For the source language_id, return the original text unchanged.\n" .
                "4) Keep ids as provided.\n";

            $prompt = json_encode($sourcePayload);
            if ($prompt === false) {
                throw new Exception('Failed to encode translation payload.');
            }

            $aiService = new AiService();
            $responseContent = $aiService->generateContent(
                $prompt,
                $systemMessage,
                0.2,
                ['response_format' => ['type' => 'json_object']],
            );

            $json = json_decode($responseContent, true);

            // Save AI Request Log
            $identity = $this->Authentication->getIdentity();
            $userId = $identity ? (int)$identity->getIdentifier() : null;
            $aiRequestsTable = $this->fetchTable('AiRequests');
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $sourceLanguageId,
                'source_medium' => 'test_payload',
                'source_reference' => $id ? 'test:' . $id : 'test:unsaved',
                'type' => 'test_translation',
                'input_payload' => $prompt,
                'output_payload' => $responseContent,
                'status' => 'success',
            ]);
            $aiRequestsTable->save($aiRequest);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
                return $this->response
                    ->withStatus(500)
                    ->withType('application/json')
                    ->withStringBody((string)json_encode([
                        'success' => false,
                        'message' => 'Invalid JSON from AI',
                        'debug' => $responseContent,
                    ]));
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'data' => $json,
                ]));
        } catch (Exception $e) {
            return $this->response
                ->withStatus(500)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    private function getAiGenerationLimitInfo(?int $userId): array
    {
        $dailyLimit = max(1, (int)env('AI_TEST_GENERATION_DAILY_LIMIT', '20'));
        if ($userId === null) {
            return [
                'allowed' => false,
                'used' => $dailyLimit,
                'limit' => $dailyLimit,
                'remaining' => 0,
                'resets_at_iso' => FrozenTime::tomorrow()->format('c'),
            ];
        }

        $todayStart = FrozenTime::today();
        $tomorrowStart = FrozenTime::tomorrow();

        $aiRequestsTable = $this->fetchTable('AiRequests');
        $used = (int)$aiRequestsTable->find()
            ->where([
                'user_id' => $userId,
                'type' => 'test_generation',
                'created_at >=' => $todayStart,
                'created_at <' => $tomorrowStart,
            ])
            ->count();

        $remaining = max(0, $dailyLimit - $used);

        return [
            'allowed' => $remaining > 0,
            'used' => $used,
            'limit' => $dailyLimit,
            'remaining' => $remaining,
            'resets_at_iso' => $tomorrowStart->format('c'),
        ];
    }
}
