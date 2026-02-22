<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Role;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * Builds catalog/index data for TestsController.
 */
class TestCatalogQueryService
{
    /**
     * Build all data needed by TestsController::index().
     *
     * @param int|null $roleId Authenticated role id.
     * @param int|null $userId Authenticated user id.
     * @param string $prefix Current route prefix.
     * @param int|null $langId Resolved language id.
     * @param array<string, string> $filters Catalog filters.
     * @param int $page Requested page.
     * @param int $perPage Requested page size.
     * @return array<string, mixed>
     */
    public function buildIndexData(
        ?int $roleId,
        ?int $userId,
        string $prefix,
        ?int $langId,
        array $filters,
        int $page,
        int $perPage,
    ): array {
        $isCreatorCatalog = $roleId === Role::CREATOR && $prefix === 'QuizCreator';
        $isCatalog = $prefix === '' || $prefix === 'QuizCreator';

        $testsTable = TableRegistry::getTableLocator()->get('Tests');
        $testAttemptsTable = TableRegistry::getTableLocator()->get('TestAttempts');
        $categoryTranslationsTable = TableRegistry::getTableLocator()->get('CategoryTranslations');
        $difficultyTranslationsTable = TableRegistry::getTableLocator()->get('DifficultyTranslations');

        $query = $testsTable
            ->find()
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
            ])
            ->orderByAsc('Tests.id');

        $canSeeAllCatalogTests = in_array($roleId, [Role::ADMIN, Role::CREATOR], true);
        if ($isCatalog && !$isCreatorCatalog && !$canSeeAllCatalogTests) {
            $query->where(['Tests.is_public' => true]);
        }

        $categoryOptions = [];
        $difficultyOptions = [];
        $catalogPagination = null;
        $recentAttempts = [];
        $topQuizzes = [];
        $topCategories = [];

        if ($isCatalog && !$isCreatorCatalog && $userId !== null) {
            $recentAttempts = $testAttemptsTable->find()
                ->where([
                    'TestAttempts.user_id' => $userId,
                    'TestAttempts.finished_at IS NOT' => null,
                ])
                ->contain([
                    'Categories.CategoryTranslations' => function ($q) use ($langId) {
                        return $q->where(['CategoryTranslations.language_id' => $langId]);
                    },
                    'Tests.TestTranslations' => function ($q) use ($langId) {
                        return $q->where(['TestTranslations.language_id' => $langId]);
                    },
                ])
                ->orderByDesc('TestAttempts.finished_at')
                ->limit(3)
                ->all()
                ->toList();
        }

        if ($isCatalog && !$isCreatorCatalog) {
            $publicOnly = !$canSeeAllCatalogTests;
            $catalogService = new TestCatalogService();
            $topQuizzes = $catalogService->getTopQuizzes($langId, $publicOnly, 3);
            $topCategories = $catalogService->getTopCategories($langId, $publicOnly, 5);
        }

        if ($isCreatorCatalog && $userId !== null) {
            $query->where(['Tests.created_by' => $userId]);

            $creatorBase = $testsTable->find()
                ->where(['Tests.created_by' => $userId]);

            $this->loadCatalogOptionsFromBaseQuery(
                $creatorBase,
                $langId,
                $categoryTranslationsTable,
                $difficultyTranslationsTable,
                $categoryOptions,
                $difficultyOptions,
            );

            $this->applyCatalogFiltersAndSorting($query, $filters, $langId, true);
        } elseif ($isCatalog) {
            $catalogBase = $testsTable->find();
            if (!$canSeeAllCatalogTests) {
                $catalogBase->where(['Tests.is_public' => true]);
            }

            $this->loadCatalogOptionsFromBaseQuery(
                $catalogBase,
                $langId,
                $categoryTranslationsTable,
                $difficultyTranslationsTable,
                $categoryOptions,
                $difficultyOptions,
            );

            $this->applyCatalogFiltersAndSorting($query, $filters, $langId, false);
        }

        if ($isCatalog) {
            $perPageOptions = [12, 24, 48];
            $defaultPerPage = 12;
            if (!in_array($perPage, $perPageOptions, true)) {
                $perPage = $defaultPerPage;
            }

            $page = max(1, $page);
            $totalItems = (int)(clone $query)->count();
            $totalPages = max(1, (int)ceil($totalItems / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $query
                ->limit($perPage)
                ->offset(($page - 1) * $perPage);

            $catalogPagination = [
                'page' => $page,
                'perPage' => $perPage,
                'perPageOptions' => $perPageOptions,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ];
        }

        $tests = $query->all();
        $difficultyRankData = (new TestCatalogService())->getDifficultyRanks();

        return [
            'tests' => $tests,
            'difficultyRanks' => $difficultyRankData['ranks'],
            'difficultyCount' => $difficultyRankData['count'],
            'isCreatorCatalog' => $isCreatorCatalog,
            'filters' => $filters,
            'categoryOptions' => $categoryOptions,
            'difficultyOptions' => $difficultyOptions,
            'catalogPagination' => $catalogPagination,
            'recentAttempts' => $recentAttempts,
            'topQuizzes' => $topQuizzes,
            'topCategories' => $topCategories,
        ];
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery $baseQuery
     * @param int|null $langId
     * @param object $categoryTranslationsTable
     * @param object $difficultyTranslationsTable
     * @param array<int, string> $categoryOptions
     * @param array<int, string> $difficultyOptions
     * @return void
     */
    private function loadCatalogOptionsFromBaseQuery(
        SelectQuery $baseQuery,
        ?int $langId,
        object $categoryTranslationsTable,
        object $difficultyTranslationsTable,
        array &$categoryOptions,
        array &$difficultyOptions,
    ): void {
        $categoryRows = (clone $baseQuery)
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

        $difficultyRows = (clone $baseQuery)
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
            $categoryTranslations = $categoryTranslationsTable->find()
                ->select(['category_id', 'name'])
                ->where([
                    'CategoryTranslations.category_id IN' => $categoryIds,
                    'CategoryTranslations.language_id' => $langId,
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
            $difficultyTranslations = $difficultyTranslationsTable->find()
                ->select(['difficulty_id', 'name'])
                ->where([
                    'DifficultyTranslations.difficulty_id IN' => $difficultyIds,
                    'DifficultyTranslations.language_id' => $langId,
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
    }

    /**
     * @param \Cake\ORM\Query\SelectQuery $query
     * @param array<string, string> $filters
     * @param int|null $langId
     * @param bool $applyVisibilityFilter
     * @return void
     */
    private function applyCatalogFiltersAndSorting(
        SelectQuery $query,
        array &$filters,
        ?int $langId,
        bool $applyVisibilityFilter,
    ): void {
        if (($filters['category'] ?? '') !== '' && ctype_digit((string)$filters['category'])) {
            $query->where(['Tests.category_id' => (int)$filters['category']]);
        }

        if (($filters['difficulty'] ?? '') !== '' && ctype_digit((string)$filters['difficulty'])) {
            $query->where(['Tests.difficulty_id' => (int)$filters['difficulty']]);
        }

        if ($applyVisibilityFilter) {
            if (($filters['visibility'] ?? '') === 'public') {
                $query->where(['Tests.is_public' => true]);
            } elseif (($filters['visibility'] ?? '') === 'private') {
                $query->where(['Tests.is_public' => false]);
            }
        }

        if (($filters['q'] ?? '') !== '') {
            $qLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], (string)$filters['q']) . '%';
            $query
                ->matching('TestTranslations', function ($q) use ($langId, $qLike) {
                    $translationQuery = $q->where([
                        'OR' => [
                            'TestTranslations.title LIKE' => $qLike,
                            'TestTranslations.description LIKE' => $qLike,
                        ],
                    ]);
                    if ($langId !== null) {
                        $translationQuery = $translationQuery->where(['TestTranslations.language_id' => $langId]);
                    }

                    return $translationQuery;
                })
                ->distinct(['Tests.id']);
        }

        $sort = (string)($filters['sort'] ?? 'latest');
        if ($sort === 'oldest') {
            $query->orderByAsc('Tests.id');
        } elseif ($sort === 'updated') {
            $query->orderByDesc('Tests.updated_at')->orderByDesc('Tests.id');
        } else {
            $query->orderByDesc('Tests.id');
            $filters['sort'] = 'latest';
        }
    }
}
