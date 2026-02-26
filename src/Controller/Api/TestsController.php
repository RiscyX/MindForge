<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
use App\Service\ApiTestService;
use App\Service\LanguageResolverService;
use App\Service\TestAttemptService;
use App\Service\TestPersistenceService;
use Cake\Datasource\Exception\RecordNotFoundException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tests', description: 'Operations about tests')]
class TestsController extends AppController
{
    /**
     * List public tests for the catalog.
     *
     * @return void
     */
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
        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $langId = (new LanguageResolverService())->resolveId($langCode);

        $categoryId = $this->request->getQuery('category_id');
        $difficultyId = $this->request->getQuery('difficulty_id');

        $testService = new ApiTestService();
        $tests = $testService->listPublicTests(
            $langId,
            is_numeric($categoryId) ? (int)$categoryId : null,
            is_numeric($difficultyId) ? (int)$difficultyId : null,
        );

        $this->jsonSuccess(['tests' => $tests]);
    }

    /**
     * Update an existing test (all fields, nested questions/answers included).
     *
     * Accessible by ADMIN (any test) and CREATOR (own tests only).
     *
     * @param string|null $id Test id.
     * @return void
     */
    #[OA\Put(
        path: '/api/v1/tests/{id}',
        summary: 'Update a test (full replace)',
        tags: ['Tests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID of the test to update',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                description: 'Language code used to resolve translations (e.g. en, hu)',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'difficulty_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'is_public', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'test_translations',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'language_id', type: 'integer', example: 1),
                                new OA\Property(property: 'title', type: 'string', example: 'My Quiz'),
                                new OA\Property(
                                    property: 'description',
                                    type: 'string',
                                    nullable: true,
                                    example: 'A short description',
                                ),
                            ],
                        ),
                    ),
                    new OA\Property(
                        property: 'questions',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(
                                    property: 'id',
                                    type: 'integer',
                                    nullable: true,
                                    description: 'Omit for new questions',
                                ),
                                new OA\Property(property: 'question_type', type: 'string', example: 'single_choice'),
                                new OA\Property(property: 'position', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'question_translations',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'language_id', type: 'integer'),
                                            new OA\Property(property: 'content', type: 'string'),
                                            new OA\Property(
                                                property: 'explanation',
                                                type: 'string',
                                                nullable: true,
                                            ),
                                        ],
                                    ),
                                ),
                                new OA\Property(
                                    property: 'answers',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(
                                                property: 'id',
                                                type: 'integer',
                                                nullable: true,
                                                description: 'Omit for new answers',
                                            ),
                                            new OA\Property(property: 'is_correct', type: 'boolean'),
                                            new OA\Property(
                                                property: 'answer_translations',
                                                type: 'array',
                                                items: new OA\Items(
                                                    properties: [
                                                        new OA\Property(property: 'language_id', type: 'integer'),
                                                        new OA\Property(property: 'content', type: 'string'),
                                                    ],
                                                ),
                                            ),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Test updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Test updated successfully.'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – not owner or insufficient role'),
            new OA\Response(response: 404, description: 'Test not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    #[OA\Patch(
        path: '/api/v1/tests/{id}',
        summary: 'Update a test (partial update)',
        tags: ['Tests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID of the test to update',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                description: 'Language code used to resolve translations (e.g. en, hu)',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'difficulty_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'is_public', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'test_translations',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'language_id', type: 'integer', example: 1),
                                new OA\Property(property: 'title', type: 'string', example: 'My Quiz'),
                                new OA\Property(
                                    property: 'description',
                                    type: 'string',
                                    nullable: true,
                                    example: 'A short description',
                                ),
                            ],
                        ),
                    ),
                    new OA\Property(
                        property: 'questions',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(
                                    property: 'id',
                                    type: 'integer',
                                    nullable: true,
                                    description: 'Omit for new questions',
                                ),
                                new OA\Property(property: 'question_type', type: 'string', example: 'single_choice'),
                                new OA\Property(property: 'position', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'question_translations',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'language_id', type: 'integer'),
                                            new OA\Property(property: 'content', type: 'string'),
                                            new OA\Property(
                                                property: 'explanation',
                                                type: 'string',
                                                nullable: true,
                                            ),
                                        ],
                                    ),
                                ),
                                new OA\Property(
                                    property: 'answers',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(
                                                property: 'id',
                                                type: 'integer',
                                                nullable: true,
                                                description: 'Omit for new answers',
                                            ),
                                            new OA\Property(property: 'is_correct', type: 'boolean'),
                                            new OA\Property(
                                                property: 'answer_translations',
                                                type: 'array',
                                                items: new OA\Items(
                                                    properties: [
                                                        new OA\Property(property: 'language_id', type: 'integer'),
                                                        new OA\Property(property: 'content', type: 'string'),
                                                    ],
                                                ),
                                            ),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Test updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Test updated successfully.'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – not owner or insufficient role'),
            new OA\Response(response: 404, description: 'Test not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function edit(?string $id = null): void
    {
        $this->request->allowMethod(['patch', 'put']);

        // 1. Authentication
        $apiUser = $this->request->getAttribute('apiUser');
        if ($apiUser === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        // 2. Role check – only ADMIN and CREATOR may edit tests via API
        $roleId = (int)$apiUser->role_id;
        if (!in_array($roleId, [Role::ADMIN, Role::CREATOR], true)) {
            $this->jsonError(403, 'FORBIDDEN', 'You do not have permission to edit tests.');

            return;
        }

        // 3. Load test – 404 if not found
        $testsTable = $this->fetchTable('Tests');
        try {
            $test = $testsTable->get((int)$id);
        } catch (RecordNotFoundException) {
            $this->jsonError(404, 'TEST_NOT_FOUND', 'Test not found.');

            return;
        }

        // 4. Ownership check – CREATORs may only edit their own tests
        if ($roleId === Role::CREATOR && (int)$test->created_by !== (int)$apiUser->id) {
            $this->jsonError(403, 'FORBIDDEN', 'You do not have permission to edit this test.');

            return;
        }

        // 5. Language resolution (for translation enrichment)
        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $langId = (new LanguageResolverService())->resolveId($langCode);

        // 6. Delegate to TestPersistenceService::update()
        $data = (array)$this->request->getData();
        $result = (new TestPersistenceService())->update($id, $data, (int)$apiUser->id, $langId);

        // 7. Response
        if (!$result['ok']) {
            $details = is_array($result['errors'] ?? null) ? $result['errors'] : [];
            $this->response = $this->response->withStatus(422);
            $this->set([
                'ok' => false,
                'error' => [
                    'code' => 'TEST_UPDATE_FAILED',
                    'message' => 'Unable to update test.',
                    'details' => $details,
                    'detail_paths' => $this->flattenValidationErrorPaths($details),
                ],
            ]);
            $this->viewBuilder()->setOption('serialize', ['ok', 'error']);

            return;
        }

        $this->jsonSuccess(['message' => 'Test updated successfully.']);
    }

    /**
     * Start a public test and create attempt.
     *
     * @param string|null $id Test id.
     * @return void
     */
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
                    new OA\Property(
                        property: 'lang',
                        type: 'string',
                        description: 'Language code (en, hu)',
                        example: 'en',
                    ),
                    new OA\Property(
                        property: 'language_id',
                        type: 'integer',
                        description: 'Language id override',
                        example: 2,
                    ),
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

        $langCode = strtolower(trim((string)$this->request->getData('lang', $this->request->getQuery('lang', 'en'))));
        $languageId = $this->request->getData('language_id');
        $langId = (new LanguageResolverService())->resolveIdWithFallback(
            is_numeric($languageId) ? (int)$languageId : 0,
            $langCode,
        );

        $attemptService = new TestAttemptService();
        $result = $attemptService->start(
            testId: (int)$id,
            userId: $userId,
            roleId: Role::USER,
            languageId: $langId,
        );

        if (!$result['ok']) {
            $errorMap = [
                'TEST_NOT_FOUND' => [404, 'TEST_NOT_FOUND', 'Test not found.'],
                'NO_ACTIVE_QUESTIONS' => [422, 'NO_ACTIVE_QUESTIONS', 'This test has no active questions.'],
            ];
            $err = $errorMap[$result['error']] ?? [500, 'ATTEMPT_CREATE_FAILED', 'Could not start the test.'];
            $this->jsonError($err[0], $err[1], $err[2]);

            return;
        }

        // Reload the attempt to get full data for the response
        $attemptsTable = $this->fetchTable('TestAttempts');
        $attempt = $attemptsTable->get($result['attempt_id']);

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

    /**
     * Flattens nested Cake validation errors into dot-path keys.
     *
     * @param array<string|int, mixed> $errors Validation errors.
     * @return list<string>
     */
    private function flattenValidationErrorPaths(array $errors): array
    {
        $paths = [];
        $this->collectErrorPaths($errors, '', $paths);

        return array_values(array_unique($paths));
    }

    /**
     * @param array<string|int, mixed> $node
     * @param string $prefix
     * @param array<int, string> $paths
     * @return void
     */
    private function collectErrorPaths(array $node, string $prefix, array &$paths): void
    {
        foreach ($node as $key => $value) {
            $segment = is_int($key) ? (string)$key : $key;
            $path = $prefix === '' ? $segment : $prefix . '.' . $segment;

            if (is_string($value) && $value !== '') {
                $paths[] = $path;

                continue;
            }

            if (is_array($value)) {
                if (array_key_exists('_empty', $value) || array_key_exists('_required', $value)) {
                    $paths[] = $path;
                }

                $this->collectErrorPaths($value, $path, $paths);
            }
        }
    }

    /**
     * View a single public test with questions.
     *
     * @param string|null $id Test id.
     * @return void
     */
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
        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $langId = (new LanguageResolverService())->resolveId($langCode);

        $testService = new ApiTestService();
        $testResult = $testService->getPublicTestDetail((int)$id, $langId);

        if ($testResult === null) {
            $this->jsonError(404, 'TEST_NOT_FOUND', 'Test not found.');

            return;
        }

        $this->jsonSuccess(['test' => $testResult]);
    }

    /**
     * Load full test data for the edit screen (creator/admin only).
     *
     * Returns all translations for all languages, is_public flag, and
     * full question/answer data including is_correct and match fields.
     *
     * @param string|null $id Test id.
     * @return void
     */
    #[OA\Get(
        path: '/api/v1/tests/{id}/edit-detail',
        summary: 'Get full test data for editing (creator/admin only)',
        tags: ['Tests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID of the test to load for editing',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Full test data including all translations, questions, and answers',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'test',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'is_public', type: 'boolean'),
                                new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                                new OA\Property(property: 'difficulty_id', type: 'integer', nullable: true),
                                new OA\Property(property: 'created_by', type: 'integer'),
                                new OA\Property(
                                    property: 'test_translations',
                                    type: 'object',
                                    description: 'Keyed by language_id (1=HU, 2=EN)',
                                    example: [
                                        '1' => ['title' => 'Cím', 'description' => null],
                                        '2' => ['title' => 'Title', 'description' => null],
                                    ],
                                ),
                                new OA\Property(
                                    property: 'questions',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(
                                                property: 'question_type',
                                                type: 'string',
                                                example: 'multiple_choice',
                                            ),
                                            new OA\Property(property: 'position', type: 'integer'),
                                            new OA\Property(property: 'is_active', type: 'boolean'),
                                            new OA\Property(property: 'source_type', type: 'string', example: 'human'),
                                            new OA\Property(
                                                property: 'question_translations',
                                                type: 'object',
                                                description: 'Keyed by language_id',
                                                example: [
                                                    '1' => ['content' => 'Kérdés?', 'explanation' => null],
                                                    '2' => ['content' => 'Question?', 'explanation' => null],
                                                ],
                                            ),
                                            new OA\Property(
                                                property: 'answers',
                                                type: 'array',
                                                items: new OA\Items(
                                                    properties: [
                                                        new OA\Property(property: 'id', type: 'integer'),
                                                        new OA\Property(property: 'is_correct', type: 'boolean'),
                                                        new OA\Property(property: 'position', type: 'integer'),
                                                        new OA\Property(
                                                            property: 'match_side',
                                                            type: 'string',
                                                            nullable: true,
                                                            example: 'left',
                                                        ),
                                                        new OA\Property(
                                                            property: 'match_group',
                                                            type: 'integer',
                                                            nullable: true,
                                                            example: 1,
                                                        ),
                                                        new OA\Property(
                                                            property: 'answer_translations',
                                                            type: 'object',
                                                            description: 'Keyed by language_id',
                                                            example: [
                                                                '1' => ['content' => 'Válasz'],
                                                                '2' => ['content' => 'Answer'],
                                                            ],
                                                        ),
                                                    ],
                                                ),
                                            ),
                                        ],
                                    ),
                                ),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden – not owner or insufficient role'),
            new OA\Response(response: 404, description: 'Test not found'),
        ],
    )]
    public function viewForEdit(?string $id = null): void
    {
        $this->request->allowMethod(['get']);

        // 1. Authentication
        $apiUser = $this->request->getAttribute('apiUser');
        if ($apiUser === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        // 2. Role check
        $roleId = (int)$apiUser->role_id;
        if (!in_array($roleId, [Role::ADMIN, Role::CREATOR], true)) {
            $this->jsonError(403, 'FORBIDDEN', 'You do not have permission to view test edit data.');

            return;
        }

        // 3. Load test with full nested data
        $testPersistence = new TestPersistenceService();
        try {
            $test = $testPersistence->loadForEdit((int)$id);
        } catch (RecordNotFoundException) {
            $this->jsonError(404, 'TEST_NOT_FOUND', 'Test not found.');

            return;
        }

        // 4. Ownership check for CREATORs
        if ($roleId === Role::CREATOR && (int)$test->created_by !== (int)$apiUser->id) {
            $this->jsonError(403, 'FORBIDDEN', 'You do not have permission to edit this test.');

            return;
        }

        // 5. Serialize
        $testService = new ApiTestService();
        $this->jsonSuccess(['test' => $testService->serializeTestForEdit($test)]);
    }
}
