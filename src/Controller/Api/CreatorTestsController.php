<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Role;
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
        $languages = $this->fetchTable('Languages');
        $language = $languages->find()->where(['Languages.code LIKE' => $langCode . '%'])->first();
        if (!$language) {
            $language = $languages->find()->first();
        }
        $langId = $language?->id;

        $categoriesTable = $this->fetchTable('Categories');
        $categoriesQuery = $categoriesTable->find()
            ->select(['Categories.id'])
            ->contain([
                'CategoryTranslations' => function ($q) use ($langId) {
                    return $langId ? $q->where(['CategoryTranslations.language_id' => $langId]) : $q;
                },
            ])
            ->orderByAsc('Categories.id');
        if ($categoriesTable->getSchema()->hasColumn('is_active')) {
            $categoriesQuery->where(['Categories.is_active' => true]);
        }

        $categories = [];
        foreach ($categoriesQuery->all() as $category) {
            $name = (string)($category->category_translations[0]->name ?? '#' . (int)$category->id);
            $categories[] = [
                'id' => (int)$category->id,
                'name' => $name,
            ];
        }

        $difficultiesTable = $this->fetchTable('Difficulties');
        $difficultiesQuery = $difficultiesTable->find()
            ->select(['Difficulties.id', 'Difficulties.level'])
            ->contain([
                'DifficultyTranslations' => function ($q) use ($langId) {
                    return $langId ? $q->where(['DifficultyTranslations.language_id' => $langId]) : $q;
                },
            ])
            ->orderByAsc('Difficulties.level')
            ->orderByAsc('Difficulties.id');
        if ($difficultiesTable->getSchema()->hasColumn('is_active')) {
            $difficultiesQuery->where(['Difficulties.is_active' => true]);
        }

        $difficulties = [];
        foreach ($difficultiesQuery->all() as $difficulty) {
            $name = (string)($difficulty->difficulty_translations[0]->name ?? 'Level ' . (int)$difficulty->level);
            $difficulties[] = [
                'id' => (int)$difficulty->id,
                'name' => $name,
            ];
        }

        $this->jsonSuccess([
            'categories' => $categories,
            'difficulties' => $difficulties,
        ]);
    }
}
