<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Throwable;

/**
 * Catalog listing, filtering, top quizzes/categories, and caching logic.
 *
 * Extracted from TestsController::getTopQuizzesForCatalog(),
 * getTopCategoriesForCatalog(), readTimedCatalogCache(), writeTimedCatalogCache().
 */
class TestCatalogService
{
    /**
     * Get top quizzes for the catalog page by attempt count.
     *
     * @param int|null $languageId
     * @param bool $publicOnly
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getTopQuizzes(?int $languageId, bool $publicOnly, int $limit): array
    {
        $limit = max(1, $limit);
        $cacheKey = sprintf('catalog_top_quizzes_l%d_p%d_n%d', (int)($languageId ?? 0), $publicOnly ? 1 : 0, $limit);
        $cached = $this->readTimedCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $testsTable = TableRegistry::getTableLocator()->get('Tests');
        $attemptsTable = TableRegistry::getTableLocator()->get('TestAttempts');

        $attemptCountSubquery = $attemptsTable->find()
            ->select([
                'cnt' => $attemptsTable->find()->func()->count('*'),
            ])
            ->where(function ($exp) {
                return $exp->equalFields('TestAttempts.test_id', 'Tests.id');
            });

        $query = $testsTable
            ->find()
            ->select([
                'id' => 'Tests.id',
                'attempt_count' => $attemptCountSubquery,
                'title' => 'TestTranslations.title',
                'category_name' => 'CategoryTranslations.name',
                'difficulty_name' => 'DifficultyTranslations.name',
            ])
            ->leftJoinWith('TestTranslations', function ($q) use ($languageId) {
                if ($languageId === null || $languageId <= 0) {
                    return $q;
                }

                return $q->where(['TestTranslations.language_id' => $languageId]);
            })
            ->leftJoinWith('Categories.CategoryTranslations', function ($q) use ($languageId) {
                if ($languageId === null || $languageId <= 0) {
                    return $q;
                }

                return $q->where(['CategoryTranslations.language_id' => $languageId]);
            })
            ->leftJoinWith('Difficulties.DifficultyTranslations', function ($q) use ($languageId) {
                if ($languageId === null || $languageId <= 0) {
                    return $q;
                }

                return $q->where(['DifficultyTranslations.language_id' => $languageId]);
            })
            ->orderByDesc('attempt_count')
            ->orderByDesc('Tests.id')
            ->limit($limit)
            ->enableHydration(false);

        if ($publicOnly) {
            $query->where(['Tests.is_public' => true]);
        }

        $rows = [];
        foreach ($query->all() as $row) {
            $rows[] = [
                'id' => (int)($row['id'] ?? 0),
                'title' => trim((string)($row['title'] ?? '')) ?: __('Untitled quiz'),
                'category_name' => trim((string)($row['category_name'] ?? '')) ?: __('Uncategorized'),
                'difficulty_name' => trim((string)($row['difficulty_name'] ?? '')),
                'attempt_count' => (int)($row['attempt_count'] ?? 0),
            ];
        }

        $this->writeTimedCache($cacheKey, $rows);

        return $rows;
    }

    /**
     * Get top categories for the catalog page by attempt count.
     *
     * @param int|null $languageId
     * @param bool $publicOnly
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getTopCategories(?int $languageId, bool $publicOnly, int $limit): array
    {
        $limit = max(1, $limit);
        $cacheKey = sprintf('catalog_top_categories_l%d_p%d_n%d', (int)($languageId ?? 0), $publicOnly ? 1 : 0, $limit);
        $cached = $this->readTimedCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $testsTable = TableRegistry::getTableLocator()->get('Tests');

        $query = $testsTable->find()
            ->select([
                'category_id' => 'Tests.category_id',
                'attempt_count' => $testsTable->find()->func()->count('TestAttempts.id'),
            ])
            ->innerJoinWith('TestAttempts')
            ->where(['Tests.category_id IS NOT' => null])
            ->groupBy(['Tests.category_id'])
            ->orderByDesc('attempt_count')
            ->orderByDesc('Tests.category_id')
            ->limit($limit)
            ->enableHydration(false);

        if ($publicOnly) {
            $query->where(['Tests.is_public' => true]);
        }

        $rows = $query->all()->toList();
        $categoryIds = [];
        foreach ($rows as $row) {
            $cid = (int)($row['category_id'] ?? 0);
            if ($cid > 0) {
                $categoryIds[] = $cid;
            }
        }

        $names = [];
        if ($categoryIds && $languageId !== null && $languageId > 0) {
            $translations = TableRegistry::getTableLocator()->get('CategoryTranslations')->find()
                ->select(['category_id', 'name'])
                ->where([
                    'CategoryTranslations.category_id IN' => $categoryIds,
                    'CategoryTranslations.language_id' => $languageId,
                ])
                ->enableHydration(false)
                ->all();

            foreach ($translations as $row) {
                $cid = (int)($row['category_id'] ?? 0);
                $name = trim((string)($row['name'] ?? ''));
                if ($cid > 0 && $name !== '') {
                    $names[$cid] = $name;
                }
            }
        }

        if ($categoryIds) {
            $missing = array_values(array_filter($categoryIds, static fn($id) => !isset($names[$id])));
            if ($missing) {
                $fallbackTranslations = TableRegistry::getTableLocator()->get('CategoryTranslations')->find()
                    ->select(['category_id', 'name'])
                    ->where(['CategoryTranslations.category_id IN' => $missing])
                    ->orderByAsc('CategoryTranslations.language_id')
                    ->enableHydration(false)
                    ->all();

                foreach ($fallbackTranslations as $row) {
                    $cid = (int)($row['category_id'] ?? 0);
                    if ($cid <= 0 || isset($names[$cid])) {
                        continue;
                    }
                    $name = trim((string)($row['name'] ?? ''));
                    if ($name !== '') {
                        $names[$cid] = $name;
                    }
                }
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $cid = (int)($row['category_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $result[] = [
                'category_id' => $cid,
                'name' => $names[$cid] ?? __('Uncategorized'),
                'attempt_count' => (int)($row['attempt_count'] ?? 0),
            ];
        }

        $this->writeTimedCache($cacheKey, $result);

        return $result;
    }

    /**
     * Get difficulty ranks for UI color coding.
     *
     * @return array{ranks: array<int, int>, count: int}
     */
    public function getDifficultyRanks(): array
    {
        $difficultyRanks = [];
        $difficultyCount = 0;
        try {
            $difficultyIds = TableRegistry::getTableLocator()->get('Difficulties')->find()
                ->select(['id'])
                ->orderByAsc('id')
                ->enableHydration(false)
                ->all()
                ->toList();

            $difficultyCount = count($difficultyIds);
            $rank = 0;
            foreach ($difficultyIds as $row) {
                $did = (int)($row['id'] ?? 0);
                if ($did > 0) {
                    $difficultyRanks[$did] = $rank;
                    $rank += 1;
                }
            }
        } catch (Throwable) {
            // Keep UI working if difficulties table is unavailable.
        }

        return ['ranks' => $difficultyRanks, 'count' => $difficultyCount];
    }

    /**
     * Read from timed catalog cache.
     *
     * @param string $cacheKey
     * @return array<int, array<string, mixed>>|null
     */
    private function readTimedCache(string $cacheKey): ?array
    {
        $cached = Cache::read($cacheKey, 'default');
        if (!is_array($cached)) {
            return null;
        }

        $generatedAt = (int)($cached['generated_at'] ?? 0);
        if ($generatedAt <= 0 || (time() - $generatedAt) > 600) {
            return null;
        }

        $data = $cached['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    /**
     * Write to timed catalog cache.
     *
     * @param string $cacheKey
     * @param array<int, array<string, mixed>> $data
     * @return void
     */
    private function writeTimedCache(string $cacheKey, array $data): void
    {
        Cache::write($cacheKey, [
            'generated_at' => time(),
            'data' => $data,
        ], 'default');
    }
}
