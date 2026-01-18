<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\ActivityLog;
use App\Service\AiService;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\View\JsonView;
use Exception;

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

        $tests = $query->all();

        $this->set(compact('tests'));
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
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($v) => $v > 0)));

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
            } catch (\Throwable) {
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
     * View method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $test = $this->Tests->get($id, contain: [
                'Categories',
                'Difficulties',
                'AiRequests',
                'Questions',
                'TestAttempts',
                'TestTranslations',
                'UserFavoriteTests']);
        $this->set(compact('test'));
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

        $languageId = $language ? $language->id : null;

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
        $this->set(compact('test', 'categories', 'difficulties', 'languages'));
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

        $languageId = $language ? $language->id : null;

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
                $qData['translations'][$qt->language_id] = $qt->content;
            }

            foreach ($question->answers as $answer) {
                $aData = [
                    'id' => $answer->id,
                    'is_correct' => $answer->is_correct,
                    'translations' => [],
                ];
                foreach ($answer->answer_translations as $at) {
                    $aData['translations'][$at->language_id] = $at->content;
                }
                $qData['answers'][] = $aData;
            }
            $questionsData[] = $qData;
        }

        $this->set(compact('test', 'categories', 'difficulties', 'languages', 'questionsData'));
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
            $userId = $this->Authentication->getIdentity()?->getIdentifier();

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
}
