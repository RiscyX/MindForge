<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\LanguageResolverService;
use App\Service\UserFavoriteTestsService;
use OpenApi\Attributes as OA;
use RuntimeException;

#[OA\Tag(name: 'Favorites', description: 'Favorite quizzes for authenticated user')]
class FavoritesController extends AppController
{
    /**
     * List favorite tests for the authenticated user.
     *
     * @return void
     */
    #[OA\Get(
        path: '/api/v1/me/favorites/tests',
        summary: 'List favorite public tests for current user',
        tags: ['Favorites'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1),
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Favorites list'),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
        ],
    )]
    public function tests(): void
    {
        $this->request->allowMethod(['get']);

        $apiUser = $this->request->getAttribute('apiUser');
        $userId = $apiUser ? (int)$apiUser->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $langId = (new LanguageResolverService())->resolveId($langCode);
        $page = max(1, (int)$this->request->getQuery('page', 1));
        $limit = max(1, min(100, (int)$this->request->getQuery('limit', 20)));

        $service = new UserFavoriteTestsService();
        $result = $service->listPublicFavorites($userId, $langId, $page, $limit);

        $this->jsonSuccess([
            'favorites' => $result['items'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total_pages' => $result['total_pages'],
            ],
        ]);
    }

    /**
     * Add a public test to the user's favorites.
     *
     * @param string|null $id Test ID.
     * @return void
     */
    #[OA\Post(
        path: '/api/v1/me/favorites/tests/{id}',
        summary: 'Add a public test to favorites',
        tags: ['Favorites'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Favorited'),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 404, description: 'Test not found'),
            new OA\Response(response: 422, description: 'Test cannot be favorited or favorites limit reached (max 10)'),
        ],
    )]
    public function addTest(?string $id = null): void
    {
        $this->request->allowMethod(['post']);

        $apiUser = $this->request->getAttribute('apiUser');
        $userId = $apiUser ? (int)$apiUser->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $testId = is_numeric($id) ? (int)$id : 0;
        $service = new UserFavoriteTestsService();
        try {
            $result = $service->addPublicTest($userId, $testId);
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            if ($message === 'TEST_NOT_FOUND') {
                $this->jsonError(404, 'TEST_NOT_FOUND', 'Test not found.');

                return;
            }
            if ($message === 'TEST_NOT_FAVORITABLE') {
                $this->jsonError(422, 'TEST_NOT_FAVORITABLE', 'Only public tests can be favorited.');

                return;
            }
            if ($message === 'FAVORITES_LIMIT_REACHED') {
                $this->jsonError(422, 'FAVORITES_LIMIT_REACHED', 'Favorites limit reached (max 10).');

                return;
            }

            $this->jsonError(500, 'FAVORITE_SAVE_FAILED', 'Could not save favorite.');

            return;
        }

        $this->jsonSuccess([
            'test_id' => $testId,
            'is_favorited' => $result['is_favorited'],
            'already_favorited' => $result['already_favorited'],
        ]);
    }

    /**
     * Remove a test from the user's favorites.
     *
     * @param string|null $id Test ID.
     * @return void
     */
    #[OA\Delete(
        path: '/api/v1/me/favorites/tests/{id}',
        summary: 'Remove a test from favorites',
        tags: ['Favorites'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Removed'),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
        ],
    )]
    public function removeTest(?string $id = null): void
    {
        $this->request->allowMethod(['delete']);

        $apiUser = $this->request->getAttribute('apiUser');
        $userId = $apiUser ? (int)$apiUser->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $testId = is_numeric($id) ? (int)$id : 0;
        $service = new UserFavoriteTestsService();
        try {
            $result = $service->removeTest($userId, $testId);
        } catch (RuntimeException) {
            $this->jsonError(500, 'FAVORITE_DELETE_FAILED', 'Could not remove favorite.');

            return;
        }

        $this->jsonSuccess([
            'test_id' => $testId,
            'is_favorited' => $result['is_favorited'],
            'already_removed' => $result['already_removed'],
        ]);
    }
}
