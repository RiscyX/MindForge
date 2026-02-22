<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
use App\Service\ApiTestService;
use App\Service\LanguageResolverService;
use App\Service\TestAttemptService;
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
}
