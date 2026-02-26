<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Role;
use App\Service\AiAnswerEvaluationService;
use App\Service\AiExplanationService;
use App\Service\AiTestGenerationService;
use App\Service\AiTranslationService;
use App\Service\AttemptOrderingService;
use App\Service\AttemptQuestionService;
use App\Service\AttemptSubmissionService;
use App\Service\BulkActionService;
use App\Service\LanguageResolverService;
use App\Service\TestAttemptService;
use App\Service\TestCatalogQueryService;
use App\Service\TestDetailsPageService;
use App\Service\TestEditorFormDataService;
use App\Service\TestPersistenceService;
use App\Service\TestStatsService;
use App\Service\UserFavoriteTestsService;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Routing\Router;
use Cake\View\JsonView;
use RuntimeException;

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

        if (!$this->request->getParam('prefix')) {
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
        $prefix = (string)$this->request->getParam('prefix', '');

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $langId = (new LanguageResolverService())->resolveId($langCode);

        $filters = [
            'q' => trim((string)$this->request->getQuery('q', '')),
            'category' => (string)$this->request->getQuery('category', ''),
            'difficulty' => (string)$this->request->getQuery('difficulty', ''),
            'visibility' => (string)$this->request->getQuery('visibility', ''),
            'sort' => (string)$this->request->getQuery('sort', 'latest'),
        ];

        $page = max(1, (int)$this->request->getQuery('page', 1));
        $perPage = (int)$this->request->getQuery('per_page', 12);

        $indexData = (new TestCatalogQueryService())->buildIndexData(
            $roleId,
            $userId,
            $prefix,
            $langId,
            $filters,
            $page,
            $perPage,
        );

        $this->set(['roleId' => $roleId] + $indexData);
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
        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);

        $testId = is_numeric($id) ? (int)$id : 0;
        $service = new TestAttemptService();
        $result = $service->start($testId, $userId, $roleId, $languageId);

        if (!$result['ok']) {
            $messages = [
                'TEST_NOT_FOUND' => __('Quiz not found.'),
                'NO_ACTIVE_QUESTIONS' => __('This quiz has no active questions yet.'),
                'SAVE_FAILED' => __('Could not start the quiz. Please try again.'),
            ];
            $this->Flash->error($messages[$result['error']] ?? __('Could not start the quiz. Please try again.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        return $this->redirect(['action' => 'take', $result['attempt_id'], 'lang' => $lang]);
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

        $attemptService = new TestAttemptService();
        $loaded = $attemptService->loadOwned((int)$id, $userId, ['Tests']);
        if (!$loaded['ok']) {
            return $this->response->withStatus(403);
        }
        $attempt = $loaded['attempt'];

        if ($attempt->finished_at !== null) {
            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);

        $attemptService->syncLanguage($attempt, $languageId);

        $test = $attemptService->loadTestWithTranslations((int)$attempt->test_id, $languageId);

        $questionService = new AttemptQuestionService();
        $questions = $questionService->listForTest((int)$attempt->test_id, $languageId, includeCorrect: true);

        $orderingService = new AttemptOrderingService();
        $questions = $orderingService->orderQuestions($questions, (int)$attempt->id);

        $this->set(compact('attempt', 'test', 'questions'));
    }

    /**
     * Abort an in-progress quiz attempt.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|null
     */
    public function abort(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to manage attempts.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $service = new TestAttemptService();
        $result = $service->abort((int)$id, $userId);

        if (!$result['ok'] && ($result['error'] ?? '') === 'FORBIDDEN') {
            return $this->response->withStatus(403);
        }

        if (!$result['ok'] && ($result['error'] ?? '') === 'ALREADY_FINISHED') {
            $this->Flash->error(__('This attempt is already finished and cannot be aborted.'));

            return $this->redirect(['action' => 'result', $id, 'lang' => $lang]);
        }

        if ($result['ok']) {
            $this->Flash->success(__('Attempt aborted.'));
        } else {
            $this->Flash->error(__('Could not abort this attempt. Please try again.'));
        }

        $roleId = (int)($identity->get('role_id') ?? 0);
        $testId = $result['test_id'] ?? 0;
        if ($testId > 0 && $roleId !== Role::CREATOR) {
            return $this->redirect(['action' => 'details', $testId, 'lang' => $lang]);
        }

        return $this->redirect(['action' => 'index', 'lang' => $lang]);
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

        $attemptService = new TestAttemptService();
        $loaded = $attemptService->loadOwned((int)$id, $userId);
        if (!$loaded['ok']) {
            return $this->response->withStatus(403);
        }
        $attempt = $loaded['attempt'];

        if ($attempt->finished_at !== null) {
            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        if ($attemptService->hasExistingAnswers((int)$attempt->id)) {
            $this->Flash->error(__('This attempt has already been submitted.'));

            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);
        $attemptService->syncLanguage($attempt, $languageId);

        $questionService = new AttemptQuestionService();
        $questions = $questionService->listForTest((int)$attempt->test_id, $languageId, includeCorrect: true);

        if (!$questions) {
            $this->Flash->error(__('This quiz has no active questions.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $answersInput = $this->request->getData('answers');
        $answersInput = is_array($answersInput) ? $answersInput : [];

        $submissionService = new AttemptSubmissionService();
        $result = $submissionService->submit(
            $attempt,
            $questions,
            $answersInput,
            function (object $question, string $userAnswerText, array $correctTextsRaw) use ($userId, $lang): bool {
                if ($userId === null) {
                    return false;
                }

                return (new AiAnswerEvaluationService())->evaluate(
                    $userId,
                    $question,
                    $userAnswerText,
                    $correctTextsRaw,
                    'quiz_submit',
                    null,
                    $lang,
                );
            },
        );
        if (!$result['ok']) {
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

        $attemptService = new TestAttemptService();
        $loaded = $attemptService->loadOwned((int)$id, $userId);
        if (!$loaded['ok']) {
            return $this->response->withStatus(403);
        }
        $attempt = $loaded['attempt'];

        if ($attempt->finished_at === null) {
            return $this->redirect(['action' => 'take', $attempt->id, 'lang' => $lang]);
        }

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);

        $test = $attemptService->loadTestWithTranslations((int)$attempt->test_id, $languageId);

        $questionService = new AttemptQuestionService();
        $questions = $questionService->listForTest((int)$attempt->test_id, $languageId, includeCorrect: true);

        $orderingService = new AttemptOrderingService();
        $questions = $orderingService->orderQuestions($questions, (int)$attempt->id);

        $attemptAnswers = $questionService->answersByQuestionId((int)$attempt->id);

        $attemptAnswerIds = [];
        foreach ($attemptAnswers as $attemptAnswer) {
            $idValue = (int)($attemptAnswer->id ?? 0);
            if ($idValue > 0) {
                $attemptAnswerIds[] = $idValue;
            }
        }

        $explanationsByAttemptAnswer = $attemptService->loadExplanations($attemptAnswerIds, $languageId);

        $csrfToken = (string)($this->request->getAttribute('csrfToken') ?? '');
        $this->set(compact(
            'attempt',
            'test',
            'questions',
            'attemptAnswers',
            'explanationsByAttemptAnswer',
            'csrfToken',
        ));
    }

    /**
     * Generate or retrieve AI explanation for a reviewed answer.
     *
     * @param string|null $attemptId Attempt id.
     * @param string|null $questionId Question id.
     * @return \Cake\Http\Response
     */
    public function explainAnswer(?string $attemptId = null, ?string $questionId = null): Response
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->disableAutoLayout();

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            return $this->response
                ->withStatus(401)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Please log in.'),
                ]));
        }

        $attemptService = new TestAttemptService();
        $loaded = $attemptService->loadOwned((int)$attemptId, $userId);
        if (!$loaded['ok']) {
            return $this->response->withStatus(403);
        }
        $attempt = $loaded['attempt'];

        if ($attempt->finished_at === null) {
            return $this->response
                ->withStatus(409)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Explanation is available after submission.'),
                ]));
        }

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);
        $attemptAnswersTable = $this->fetchTable('TestAttemptAnswers');
        $attemptAnswer = $attemptAnswersTable->find()
            ->where([
                'test_attempt_id' => (int)$attempt->id,
                'question_id' => (int)$questionId,
            ])
            ->first();
        if (!$attemptAnswer) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Answer record not found.'),
                ]));
        }

        $force = (bool)$this->request->getData('force', false);

        $service = new AiExplanationService();
        $result = $service->getOrGenerate($userId, $attempt, $attemptAnswer, $languageId, $lang, $force);

        if (!$result['success'] && !empty($result['limit_reached'])) {
            return $this->response
                ->withStatus(429)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'limit_reached' => true,
                    'message' => __('AI explanation limit reached for today. Please try again tomorrow.'),
                    'resets_at' => $result['resets_at'] ?? '',
                    'used' => $result['used'] ?? 0,
                    'limit' => $result['limit'] ?? 0,
                    'remaining' => $result['remaining'] ?? 0,
                ]));
        }

        if (!$result['success'] && !empty($result['error_code'])) {
            $httpStatus = $result['error_code'] === 'QUESTION_NOT_FOUND' ? 404 : 500;

            return $this->response
                ->withStatus($httpStatus)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => $result['message'] ?? __('An error occurred.'),
                ]));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode($result));
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
        $bulkService = new BulkActionService();
        $ids = $bulkService->sanitizeIds($this->request->getData('ids'));

        if (!$ids) {
            $this->Flash->error(__('Select at least one item.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        if ($action !== 'delete') {
            $this->Flash->error(__('Invalid bulk action.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        $result = $bulkService->bulkDelete($this->Tests, $ids);

        if ($result['deleted'] > 0) {
            $this->Flash->success(__('Deleted {0} item(s).', $result['deleted']));
        }
        if ($result['failed'] > 0) {
            $this->Flash->error(__('Could not delete {0} item(s).', $result['failed']));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
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

        // Public/user-facing details page for non-creator users on non-prefixed routes.
        if (!$this->request->getParam('prefix') && $roleId !== Role::CREATOR) {
            $lang = (string)$this->request->getParam('lang', 'en');
            $langCode = strtolower(trim($lang));
            $languageId = (new LanguageResolverService())->resolveId($langCode);

            $details = (new TestDetailsPageService())->buildPublicDetails(
                is_numeric($id) ? (int)$id : 0,
                $languageId,
                $identity ? (int)$identity->getIdentifier() : null,
            );

            if (($details['test'] ?? null) === null) {
                $this->Flash->error(__('Quiz not found.'));

                return $this->redirect(['action' => 'index', 'lang' => $lang]);
            }

            $this->set($details);
            $this->viewBuilder()->setTemplate('catalog_view');

            return;
        }

        if (!$this->request->getParam('prefix') && $roleId === Role::CREATOR) {
            $lang = (string)$this->request->getParam('lang', 'en');
            $langCode = strtolower(trim($lang));
            $languageId = (new LanguageResolverService())->resolveId($langCode);
            $userId = $identity ? (int)$identity->getIdentifier() : null;

            $details = (new TestDetailsPageService())->buildCreatorDetails(
                is_numeric($id) ? (int)$id : 0,
                (int)$userId,
                $languageId,
            );

            if (($details['test'] ?? null) === null) {
                $this->Flash->error(__('Quiz not found.'));

                return $this->redirect(['action' => 'index', 'lang' => $lang]);
            }

            $this->set($details);
            $this->viewBuilder()->setTemplate('creator_view');

            return;
        }

        // Quiz creator prefix: show the creator details view.
        if ($this->request->getParam('prefix') === 'QuizCreator') {
            $lang = (string)$this->request->getParam('lang', 'en');
            $langCode = strtolower(trim($lang));
            $languageId = (new LanguageResolverService())->resolveId($langCode);
            $userId = $identity ? (int)$identity->getIdentifier() : null;

            $details = (new TestDetailsPageService())->buildCreatorDetails(
                is_numeric($id) ? (int)$id : 0,
                (int)$userId,
                $languageId,
            );

            if (($details['test'] ?? null) === null) {
                $this->Flash->error(__('Quiz not found.'));

                return $this->redirect(['action' => 'index', 'lang' => $lang]);
            }

            $this->set($details);
            $this->viewBuilder()
                ->setTemplatePath('Tests')
                ->setTemplate('creator_view');

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
     * List current user's favorite public quizzes.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function favorites()
    {
        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to view your favorites.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);
        $page = max(1, (int)$this->request->getQuery('page', 1));
        $limit = 12;

        $service = new UserFavoriteTestsService();
        $result = $service->listPublicFavorites($userId, $languageId, $page, $limit);

        $this->set([
            'favoriteItems' => $result['items'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total_pages' => $result['total_pages'],
            ],
        ]);
    }

    /**
     * Add a public quiz to current user's favorites.
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null
     */
    public function favorite(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to save favorites.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $testId = is_numeric($id) ? (int)$id : 0;
        $service = new UserFavoriteTestsService();

        try {
            $result = $service->addPublicTest($userId, $testId);
            if ($result['already_favorited']) {
                $this->Flash->success(__('Quiz is already in your favorites.'));
            } else {
                $this->Flash->success(__('Quiz saved to favorites.'));
            }
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'TEST_NOT_FAVORITABLE') {
                $this->Flash->error(__('Only public quizzes can be favorited.'));
            } else {
                $this->Flash->error(__('Quiz not found.'));
            }
        }

        return $this->redirect(['action' => 'details', $testId, 'lang' => $lang]);
    }

    /**
     * Remove a quiz from current user's favorites.
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null
     */
    public function unfavorite(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to manage favorites.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $testId = is_numeric($id) ? (int)$id : 0;
        $service = new UserFavoriteTestsService();

        try {
            $result = $service->removeTest($userId, $testId);
            if ($result['already_removed']) {
                $this->Flash->success(__('Quiz is not in your favorites.'));
            } else {
                $this->Flash->success(__('Quiz removed from favorites.'));
            }
        } catch (RuntimeException) {
            $this->Flash->error(__('Could not remove quiz from favorites.'));
        }

        return $this->redirect(['action' => 'details', $testId, 'lang' => $lang]);
    }

    /**
     * Quiz statistics page for admins/creators.
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void
     */
    public function stats(?string $id = null)
    {
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $roleId = $identity ? (int)$identity->get('role_id') : null;
        $lang = (string)$this->request->getParam('lang', 'en');

        if ($userId === null || !in_array($roleId, [Role::ADMIN, Role::CREATOR], true)) {
            $this->Flash->error(__('You do not have access to quiz statistics.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);

        $attemptService = new TestAttemptService();
        $test = $attemptService->loadTestForStats(
            is_numeric($id) ? (int)$id : 0,
            $userId,
            $roleId,
            $languageId,
        );

        if (!$test) {
            $this->Flash->error(__('Quiz not found.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $stats = (new TestStatsService())->buildQuizStats((int)$test->id);
        $this->set(compact('test', 'stats'));
        $this->viewBuilder()->setTemplate('creator_view');
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);
        $test = $this->Tests->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $identityUserId = $this->Authentication->getIdentity()?->getIdentifier();
            $userId = is_numeric($identityUserId) ? (int)$identityUserId : null;

            $persistenceService = new TestPersistenceService();
            $result = $persistenceService->create(is_array($data) ? $data : [], $userId, $languageId);
            $test = $result['test'];

            if ($result['ok']) {
                $this->Flash->success(__('The test has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The test could not be saved. Please, try again.'));
            if (Configure::read('debug') && !empty($result['errors'])) {
                 $this->Flash->error(json_encode($result['errors']));
            }
        }

        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $formMeta = (new TestEditorFormDataService())->buildFormMeta($languageId, $userId);
        $this->set(['test' => $test] + $formMeta);
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
        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);

        $persistenceService = new TestPersistenceService();
        $test = $persistenceService->loadForEdit($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            $identityUserId = $this->Authentication->getIdentity()?->getIdentifier();
            $userId = is_numeric($identityUserId) ? (int)$identityUserId : null;

            $result = $persistenceService->update($id, is_array($data) ? $data : [], $userId, $languageId);
            $test = $result['test'];

            if ($result['ok']) {
                $this->Flash->success(__('The test has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The test could not be saved. Please, try again.'));
            if (Configure::read('debug') && !empty($result['errors'])) {
                 $this->Flash->error(json_encode($result['errors']));
            }
        }

        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $editorService = new TestEditorFormDataService();
        $formMeta = $editorService->buildFormMeta($languageId, $userId);
        $editPayload = $editorService->prepareEditPayload($test);

        $this->set([
            'test' => $test,
            'questionsData' => $editPayload['questionsData'],
        ] + $formMeta);
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
        $prompt = (string)$this->request->getData('prompt');
        $requestedCount = $this->request->getData('question_count');
        $questionCount = is_numeric($requestedCount) ? (int)$requestedCount : null;
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $result = (new AiTestGenerationService())->generate(
            $userId,
            $prompt,
            $questionCount,
            $langCode,
            $this->request->getUploadedFiles(),
            (string)$this->request->clientIp(),
            (string)$this->request->getHeaderLine('User-Agent'),
        );

        $httpStatus = (int)($result['http_status'] ?? ($result['success'] ? 200 : 500));

        return $this->response
            ->withStatus($httpStatus)
            ->withType('application/json')
            ->withStringBody((string)json_encode(
                array_diff_key($result, ['http_status' => true]),
            ));
    }

    /**
     * Return the current status of an AI generation request owned by the logged-in user.
     *
     * Used by the frontend to poll while AI is running.
     * GET /{lang}/tests/ai-request-status/{id}
     *
     * @param string|null $id AI request id.
     * @return \Cake\Http\Response
     */
    public function aiRequestStatus(?string $id = null): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if (!$userId) {
            return $this->response->withStatus(401)->withType('application/json')
                ->withStringBody((string)json_encode(['ok' => false, 'error' => 'Unauthorized']));
        }

        $aiRequestsTable = $this->fetchTable('AiRequests');
        $req = $aiRequestsTable->find()
            ->where(['AiRequests.id' => (int)$id, 'AiRequests.user_id' => $userId])
            ->first();
        if (!$req) {
            return $this->response->withStatus(404)->withType('application/json')
                ->withStringBody((string)json_encode(['ok' => false, 'error' => 'Not found']));
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'ok' => true,
                'ai_request' => [
                    'id' => (int)$req->id,
                    'status' => (string)$req->status,
                    'error_code' => $req->error_code,
                    'error_message' => $req->error_message,
                    'started_at' => $req->started_at?->format('c'),
                    'finished_at' => $req->finished_at?->format('c'),
                    'duration_ms' => $req->duration_ms !== null ? (int)$req->duration_ms : null,
                    'poll_interval_ms' => 2500,
                ],
            ]));
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
        if ($sourceLanguageId <= 0 || $title === '') {
            return $this->response
                ->withStatus(422)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'Missing source language or title.',
                ]));
        }

        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;

        $service = new AiTranslationService();
        $result = $service->translate($sourceLanguageId, $testSource, $questionsSource, $userId, $id);

        $httpStatus = $result['http_status'] ?? ($result['success'] ? 200 : 500);

        return $this->response
            ->withStatus($httpStatus)
            ->withType('application/json')
            ->withStringBody((string)json_encode(
                array_diff_key($result, ['http_status' => true]),
            ));
    }
}
