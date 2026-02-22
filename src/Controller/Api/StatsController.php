<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\LanguageResolverService;
use App\Service\UserQuizStatsService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Stats', description: 'Statistics for the authenticated user')]
class StatsController extends AppController
{
    /**
     * Get quiz statistics for the authenticated user.
     *
     * @return void
     */
    #[OA\Get(
        path: '/api/v1/me/stats/quizzes',
        summary: 'Get quiz stats for the current user (best attempt per quiz)',
        tags: ['Stats'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                required: false,
                description: 'Language code (en, hu)',
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Quiz stats',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'quizzes', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
        ],
    )]
    public function quizzes(): void
    {
        $this->request->allowMethod(['get']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $langId = (new LanguageResolverService())->resolveId($langCode);

        $statsService = new UserQuizStatsService();
        $quizzes = $statsService->getQuizStats($userId, $langId);

        $this->jsonSuccess(['quizzes' => $quizzes]);
    }
}
