<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

class UserFavoriteTestsService
{
    use LocatorAwareTrait;

    /**
     * @param int $userId
     * @param int $testId
     * @return array{is_favorited: bool, already_favorited: bool}
     */
    public function addPublicTest(int $userId, int $testId): array
    {
        if ($userId <= 0 || $testId <= 0) {
            throw new RuntimeException('TEST_NOT_FOUND');
        }

        $tests = $this->fetchTable('Tests');
        $favoriteTests = $this->fetchTable('UserFavoriteTests');

        $test = $tests->find()
            ->select(['id', 'is_public'])
            ->where(['Tests.id' => $testId])
            ->first();
        if ($test === null) {
            throw new RuntimeException('TEST_NOT_FOUND');
        }
        if (!(bool)$test->is_public) {
            throw new RuntimeException('TEST_NOT_FAVORITABLE');
        }

        $existing = $favoriteTests->find()
            ->where([
                'UserFavoriteTests.user_id' => $userId,
                'UserFavoriteTests.test_id' => $testId,
            ])
            ->first();
        if ($existing) {
            return [
                'is_favorited' => true,
                'already_favorited' => true,
            ];
        }

        $entity = $favoriteTests->newEntity([
            'user_id' => $userId,
            'test_id' => $testId,
            'created_at' => DateTime::now(),
        ]);
        if (!$favoriteTests->save($entity)) {
            throw new RuntimeException('FAVORITE_SAVE_FAILED');
        }

        return [
            'is_favorited' => true,
            'already_favorited' => false,
        ];
    }

    /**
     * @param int $userId
     * @param int $testId
     * @return array{is_favorited: bool, already_removed: bool}
     */
    public function removeTest(int $userId, int $testId): array
    {
        if ($userId <= 0 || $testId <= 0) {
            return [
                'is_favorited' => false,
                'already_removed' => true,
            ];
        }

        $favoriteTests = $this->fetchTable('UserFavoriteTests');
        $existing = $favoriteTests->find()
            ->where([
                'UserFavoriteTests.user_id' => $userId,
                'UserFavoriteTests.test_id' => $testId,
            ])
            ->first();
        if (!$existing) {
            return [
                'is_favorited' => false,
                'already_removed' => true,
            ];
        }

        if (!$favoriteTests->delete($existing)) {
            throw new RuntimeException('FAVORITE_DELETE_FAILED');
        }

        return [
            'is_favorited' => false,
            'already_removed' => false,
        ];
    }

    /**
     * Check if a test is favorited by the user.
     *
     * @param int $userId User ID.
     * @param int $testId Test ID.
     * @return bool
     */
    public function isFavorited(int $userId, int $testId): bool
    {
        if ($userId <= 0 || $testId <= 0) {
            return false;
        }

        $favoriteTests = $this->fetchTable('UserFavoriteTests');

        return $favoriteTests->find()
            ->where([
                'UserFavoriteTests.user_id' => $userId,
                'UserFavoriteTests.test_id' => $testId,
            ])
            ->count() > 0;
    }

    /**
     * @param int $userId
     * @param int|null $langId
     * @param int $page
     * @param int $limit
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, limit: int, total_pages: int}
     */
    public function listPublicFavorites(int $userId, ?int $langId, int $page = 1, int $limit = 20): array
    {
        $favoriteTests = $this->fetchTable('UserFavoriteTests');

        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $countQuery = $favoriteTests->find()
            ->where(['UserFavoriteTests.user_id' => $userId])
            ->innerJoinWith('Tests', function ($q) {
                return $q->where(['Tests.is_public' => true]);
            })
            ->distinct(['UserFavoriteTests.id']);
        $total = (int)$countQuery->count();
        $totalPages = max(1, (int)ceil($total / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $rows = $favoriteTests->find()
            ->where(['UserFavoriteTests.user_id' => $userId])
            ->innerJoinWith('Tests', function ($q) {
                return $q->where(['Tests.is_public' => true]);
            })
            ->contain([
                'Tests' => function ($q) use ($langId) {
                    return $q->contain([
                        'Categories.CategoryTranslations' => function ($tq) use ($langId) {
                            return $langId ? $tq->where(['CategoryTranslations.language_id' => $langId]) : $tq;
                        },
                        'Difficulties.DifficultyTranslations' => function ($tq) use ($langId) {
                            return $langId ? $tq->where(['DifficultyTranslations.language_id' => $langId]) : $tq;
                        },
                        'TestTranslations' => function ($tq) use ($langId) {
                            return $langId ? $tq->where(['TestTranslations.language_id' => $langId]) : $tq;
                        },
                    ]);
                },
            ])
            ->orderByDesc('UserFavoriteTests.created_at')
            ->orderByDesc('UserFavoriteTests.id')
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->distinct(['UserFavoriteTests.id'])
            ->all()
            ->toList();

        $items = [];
        foreach ($rows as $row) {
            $test = $row->test ?? null;
            if ($test === null) {
                continue;
            }

            $translation = $test->test_translations[0] ?? null;
            $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
            $catTrans = $test->category?->category_translations[0] ?? null;

            $items[] = [
                'test' => [
                    'id' => (int)$test->id,
                    'title' => $translation?->title ?? 'Untitled Test',
                    'description' => $translation?->description ?? '',
                    'category' => $catTrans?->name ?? null,
                    'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
                    'difficulty' => $diffTrans?->name ?? null,
                    'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
                    'number_of_questions' => $test->number_of_questions !== null
                        ? (int)$test->number_of_questions
                        : null,
                    'created' => $test->created_at?->format('c'),
                    'modified' => $test->updated_at?->format('c'),
                ],
                'favorited_at' => $row->created_at?->format('c'),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
        ];
    }
}
