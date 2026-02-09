<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\I18n\FrozenTime;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tests', description: 'Operations about tests')]
class TestsController extends AppController
{
    #[OA\Get(
        path: '/api/v1/tests',
        summary: 'List all tests',
        tags: ['Tests'],
        parameters: [
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                description: 'Language code (e.g. en, hu)',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
            new OA\Parameter(
                name: 'category_id',
                in: 'query',
                description: 'Filter by category id',
                required: false,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'difficulty_id',
                in: 'query',
                description: 'Filter by difficulty id',
                required: false,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'tests',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'title', type: 'string'),
                                    new OA\Property(property: 'description', type: 'string'),
                                    new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                                    new OA\Property(property: 'difficulty', type: 'string'),
                                    new OA\Property(property: 'difficulty_id', type: 'integer', nullable: true),
                                    new OA\Property(property: 'category', type: 'string'),
                                    new OA\Property(property: 'number_of_questions', type: 'integer', nullable: true),
                                    new OA\Property(property: 'created', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'modified', type: 'string', format: 'date-time'),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function index(): void
    {
        $testsTable = $this->fetchTable('Tests');
        $languagesTable = $this->fetchTable('Languages');

        $langCode = $this->request->getQuery('lang', 'en');

        $language = $languagesTable->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        if (!$language) {
             $language = $languagesTable->find()->first();
        }

        $langId = $language->id ?? null;
        $categoryId = $this->request->getQuery('category_id');
        $difficultyId = $this->request->getQuery('difficulty_id');

        $conditions = [
            'Tests.is_public' => true,
        ];
        if (is_numeric($categoryId)) {
            $conditions['Tests.category_id'] = (int)$categoryId;
        }
        if (is_numeric($difficultyId)) {
            $conditions['Tests.difficulty_id'] = (int)$difficultyId;
        }

        $query = $testsTable
            ->find()
            ->where($conditions)
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($langId) {
                    return $q->where(['CategoryTranslations.language_id' => $langId]);
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($langId) {
                    return $q->where(['DifficultyTranslations.language_id' => $langId]);
                },
                'TestTranslations' => function ($q) use ($langId) {
                    return $q->where(['TestTranslations.language_id' => $langId]);
                },
            ]);

        $results = $query->all();

        $tests = [];
        foreach ($results as $test) {
            $translation = $test->test_translations[0] ?? null;
            // Use null-safe operator ?-> to avoid errors if difficulty or category is missing
            $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
            $catTrans = $test->category?->category_translations[0] ?? null;

            $tests[] = [
                'id' => $test->id,
                // Fallback to English/Default or a placeholder if translation is missing
                'title' => $translation?->title ?? 'Untitled Test (ID: ' . $test->id . ')',
                'description' => $translation?->description ?? '',
                'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
                'difficulty' => $diffTrans?->name ?? 'Unknown',
                'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
                'category' => $catTrans?->name ?? 'Uncategorized',
                'number_of_questions' => $test->number_of_questions !== null ? (int)$test->number_of_questions : null,
                'created' => $test->created_at?->format('c'),
                'modified' => $test->updated_at?->format('c'),
            ];
        }

        $this->jsonSuccess(['tests' => $tests]);
    }

    #[OA\Post(
        path: '/api/v1/tests/{id}/start',
        summary: 'Start a test (creates an attempt)',
        tags: ['Tests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID of test to start',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'lang', type: 'string', description: 'Language code (en, hu)', example: 'en'),
                    new OA\Property(property: 'language_id', type: 'integer', description: 'Language id override', example: 2),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Attempt created'),
            new OA\Response(response: 404, description: 'Test not found'),
            new OA\Response(response: 422, description: 'No active questions'),
        ],
    )]
    public function start(?string $id = null): void
    {
        $this->request->allowMethod(['post']);

        $apiUser = $this->request->getAttribute('apiUser');
        $userId = $apiUser ? (int)$apiUser->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $testsTable = $this->fetchTable('Tests');
        $languagesTable = $this->fetchTable('Languages');
        $questionsTable = $this->fetchTable('Questions');
        $attemptsTable = $this->fetchTable('TestAttempts');

        $test = $testsTable->find()
            ->where(['Tests.id' => (int)$id, 'Tests.is_public' => true])
            ->first();
        if (!$test) {
            $this->jsonError(404, 'TEST_NOT_FOUND', 'Test not found.');

            return;
        }

        $langCode = strtolower(trim((string)$this->request->getData('lang', $this->request->getQuery('lang', 'en'))));
        $languageId = $this->request->getData('language_id');
        $langId = null;
        if (is_numeric($languageId)) {
            $lang = $languagesTable->find()->where(['Languages.id' => (int)$languageId])->first();
            $langId = $lang?->id;
        } else {
            $lang = $languagesTable->find()->where(['code LIKE' => $langCode . '%'])->first();
            if (!$lang) {
                $lang = $languagesTable->find()->first();
            }
            $langId = $lang?->id;
        }

        $activeCount = (int)$questionsTable->find()
            ->where(['Questions.test_id' => (int)$test->id, 'Questions.is_active' => true])
            ->count();
        if ($activeCount <= 0) {
            $this->jsonError(422, 'NO_ACTIVE_QUESTIONS', 'This test has no active questions.');

            return;
        }

        $now = FrozenTime::now();
        $attempt = $attemptsTable->newEntity([
            'user_id' => $userId,
            'test_id' => (int)$test->id,
            'category_id' => $test->category_id,
            'difficulty_id' => $test->difficulty_id,
            'language_id' => $langId,
            'started_at' => $now,
            'created_at' => $now,
            'total_questions' => $activeCount,
            'correct_answers' => 0,
        ]);

        if (!$attemptsTable->save($attempt)) {
            $this->jsonError(500, 'ATTEMPT_CREATE_FAILED', 'Could not start the test.');

            return;
        }

        $this->response = $this->response->withStatus(201);
        $this->jsonSuccess([
            'attempt' => [
                'id' => $attempt->id,
                'test_id' => $attempt->test_id,
                'language_id' => $attempt->language_id,
                'started_at' => $attempt->started_at?->format('c'),
                'total_questions' => $attempt->total_questions,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/tests/{id}',
        summary: 'Get details of a specific test',
        tags: ['Tests'],
        parameters: [
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                description: 'Language code (e.g. en, hu)',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID of test to fetch',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'test',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'title', type: 'string'),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(property: 'category', type: 'string'),
                                new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                                new OA\Property(property: 'difficulty', type: 'string'),
                                new OA\Property(property: 'difficulty_id', type: 'integer', nullable: true),
                                new OA\Property(property: 'number_of_questions', type: 'integer', nullable: true),
                                new OA\Property(property: 'questions', type: 'array', items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'content', type: 'string'),
                                        new OA\Property(property: 'type', type: 'string'),
                                        new OA\Property(property: 'explanation', type: 'string', nullable: true),
                                        new OA\Property(property: 'answers', type: 'array', items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer'),
                                                new OA\Property(property: 'content', type: 'string'),
                                            ],
                                        )),
                                    ],
                                )),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 404, description: 'Test not found'),
        ],
    )]
    public function view(?string $id = null): void
    {
        $testsTable = $this->fetchTable('Tests');
        $languagesTable = $this->fetchTable('Languages');

        $langCode = $this->request->getQuery('lang', 'en');

        $language = $languagesTable->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        if (!$language) {
             $language = $languagesTable->find()->first();
        }

        $langId = $language->id ?? null;

        $test = $testsTable->find()
            ->where(['Tests.id' => (int)$id, 'Tests.is_public' => true])
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($langId) {
                    return $q->where(['CategoryTranslations.language_id' => $langId]);
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($langId) {
                    return $q->where(['DifficultyTranslations.language_id' => $langId]);
                },
                'TestTranslations' => function ($q) use ($langId) {
                    return $q->where(['TestTranslations.language_id' => $langId]);
                },
                'Questions' => function ($q) {
                    return $q->orderBy(['Questions.position' => 'ASC', 'Questions.id' => 'ASC']);
                },
                'Questions.QuestionTranslations' => function ($q) use ($langId) {
                    return $q->where(['QuestionTranslations.language_id' => $langId]);
                },
                'Questions.Answers',
                'Questions.Answers.AnswerTranslations' => function ($q) use ($langId) {
                    return $q->where(['AnswerTranslations.language_id' => $langId]);
                },
            ])
            ->first();

        if (!$test) {
            $this->jsonError(404, 'TEST_NOT_FOUND', 'Test not found.');

            return;
        }

        $translation = $test->test_translations[0] ?? null;
        $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
        $catTrans = $test->category?->category_translations[0] ?? null;

        $testResult = [
            'id' => $test->id,
            'title' => $translation?->title ?? 'Untitled Test',
            'description' => $translation?->description ?? '',
            'difficulty' => $diffTrans?->name ?? 'Unknown',
            'category' => $catTrans?->name ?? 'Uncategorized',
            'category_id' => $test->category_id,
            'difficulty_id' => $test->difficulty_id,
            'number_of_questions' => $test->number_of_questions,
            'questions' => [],
            'created' => $test->created_at?->format('c'),
            'modified' => $test->updated_at?->format('c'),
        ];

        foreach ($test->questions as $question) {
            $qTrans = $question->question_translations[0] ?? null;

            $answers = [];
            foreach ($question->answers as $answer) {
                $aTrans = $answer->answer_translations[0] ?? null;
                    $answers[] = [
                        'id' => $answer->id,
                        'content' => $aTrans?->content ?? 'No content',
                    ];
            }

            $testResult['questions'][] = [
                'id' => $question->id,
                'content' => $qTrans?->content ?? 'Untitled Question',
                'explanation' => $qTrans?->explanation,
                'type' => $question->question_type,
                'position' => $question->position,
                'answers' => $answers,
            ];
        }

        $this->jsonSuccess(['test' => $testResult]);
    }
}
