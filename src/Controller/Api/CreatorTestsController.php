<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
use App\Service\CreatorMetadataService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'CreatorTests', description: 'Creator test metadata')]
class CreatorTestsController extends AppController
{
    /**
     * Return metadata needed by creator test flows.
     *
     * @return void
     */
    #[OA\Get(
        path: '/api/v1/creator/tests/metadata',
        summary: 'Get active categories and difficulties for creator flows',
        tags: ['CreatorTests'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Metadata'),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function metadata(): void
    {
        $this->request->allowMethod(['get']);

        $apiUser = $this->request->getAttribute('apiUser');
        $userId = $apiUser ? (int)$apiUser->id : null;
        $roleId = $apiUser ? (int)($apiUser->role_id ?? 0) : 0;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }
        if (!in_array($roleId, [Role::ADMIN, Role::CREATOR], true)) {
            $this->jsonError(403, 'FORBIDDEN', 'Only creators can access creator metadata.');

            return;
        }

        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $metadataService = new CreatorMetadataService();
        $langId = $metadataService->resolveLanguageId($langCode);

        $this->jsonSuccess([
            'categories' => $metadataService->getActiveCategories($langId),
            'difficulties' => $metadataService->getActiveDifficulties($langId),
        ]);
    }
}
