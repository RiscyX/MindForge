<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * Builds user statistics data (attempts, scores, category breakdown, recent activity).
 *
 * Extracted from UsersController::buildUserStatsData().
 */
class UserStatsService
{
    /**
     * Build comprehensive stats for a user.
     *
     * @param int $userId Authenticated user id.
     * @param int|null $languageId Language id for translations (null = any language).
     * @return array<string, mixed>
     */
    public function buildUserStatsData(int $userId, ?int $languageId = null): array
    {
        $attemptsTable = TableRegistry::getTableLocator()->get('TestAttempts');

        $base = $attemptsTable->find()
            ->where([
                'TestAttempts.user_id' => $userId,
                'TestAttempts.test_id IS NOT' => null,
            ])
            ->innerJoinWith('Tests', function (SelectQuery $q) use ($userId): SelectQuery {
                return $q->where([
                    'OR' => [
                        ['Tests.is_public' => true],
                        ['Tests.created_by' => $userId],
                    ],
                ]);
            });

        $totalAttempts = (int)(clone $base)->count();

        $finishedBase = (clone $base)->where(['TestAttempts.finished_at IS NOT' => null]);
        $finishedAttempts = (int)(clone $finishedBase)->count();

        $uniqueQuizzes = (int)(clone $finishedBase)
            ->select(['test_id' => 'TestAttempts.test_id'])
            ->distinct(['TestAttempts.test_id'])
            ->count();

        $avgScoreRow = (clone $finishedBase)
            ->select(['avg_score' => $attemptsTable->find()->func()->avg('TestAttempts.score')])
            ->where(['TestAttempts.score IS NOT' => null])
            ->enableHydration(false)
            ->first();
        $avgScore = $avgScoreRow ? (float)($avgScoreRow['avg_score'] ?? 0) : 0.0;

        $bestScoreRow = (clone $finishedBase)
            ->select(['best_score' => $attemptsTable->find()->func()->max('TestAttempts.score')])
            ->where(['TestAttempts.score IS NOT' => null])
            ->enableHydration(false)
            ->first();
        $bestScore = $bestScoreRow ? (float)($bestScoreRow['best_score'] ?? 0) : 0.0;

        // Last 7 days finished attempts
        $sevenDaysAgo = FrozenTime::now()->subDays(7);
        $last7DaysCount = (int)(clone $finishedBase)
            ->where(['TestAttempts.finished_at >=' => $sevenDaysAgo])
            ->count();

        $categoryBreakdown = $this->buildCategoryBreakdown($attemptsTable, $finishedBase, $languageId);

        $recentAttempts = (clone $finishedBase)
            ->contain([
                'Tests' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $q->contain([
                        'Categories.CategoryTranslations' => function (SelectQuery $q) use ($languageId) {
                            if ($languageId === null) {
                                return $q;
                            }

                            return $q->where(['CategoryTranslations.language_id' => $languageId]);
                        },
                        'Difficulties.DifficultyTranslations' => function (SelectQuery $q) use ($languageId) {
                            if ($languageId === null) {
                                return $q;
                            }

                            return $q->where(['DifficultyTranslations.language_id' => $languageId]);
                        },
                        'TestTranslations' => function (SelectQuery $q) use ($languageId) {
                            if ($languageId === null) {
                                return $q;
                            }

                            return $q->where(['TestTranslations.language_id' => $languageId]);
                        },
                    ]);
                },
            ])
            ->orderByDesc('TestAttempts.finished_at')
            ->orderByDesc('TestAttempts.id')
            ->limit(20)
            ->all();

        return compact(
            'totalAttempts',
            'finishedAttempts',
            'uniqueQuizzes',
            'avgScore',
            'bestScore',
            'last7DaysCount',
            'categoryBreakdown',
            'recentAttempts',
        );
    }

    /**
     * Build category breakdown with attempts count, avg score, and best score per category.
     *
     * @param \Cake\ORM\Table $attemptsTable TestAttempts table instance.
     * @param \Cake\ORM\Query\SelectQuery $finishedBase Base query for finished attempts.
     * @param int|null $languageId Language id for translations.
     * @return array<int, array<string, mixed>>
     */
    private function buildCategoryBreakdown(object $attemptsTable, SelectQuery $finishedBase, ?int $languageId): array
    {
        $breakdownRaw = (clone $finishedBase)
            ->select([
                'category_id' => 'TestAttempts.category_id',
                'attempts' => $attemptsTable->find()->func()->count('TestAttempts.id'),
                'avg_score' => $attemptsTable->find()->func()->avg('TestAttempts.score'),
                'best_score' => $attemptsTable->find()->func()->max('TestAttempts.score'),
            ])
            ->groupBy(['TestAttempts.category_id'])
            ->enableHydration(false)
            ->all()
            ->toList();

        // Sort by attempts desc in PHP to avoid aggregate-alias ORDER BY dialect issues
        usort($breakdownRaw, static function (array $a, array $b): int {
            return (int)($b['attempts'] ?? 0) <=> (int)($a['attempts'] ?? 0);
        });

        // Load category names for the breakdown
        $categoryIds = array_filter(array_unique(array_column($breakdownRaw, 'category_id')));
        $categoryNames = [];
        if ($categoryIds) {
            $catTranslations = TableRegistry::getTableLocator()->get('CategoryTranslations')
                ->find()
                ->where([
                    'CategoryTranslations.category_id IN' => array_values($categoryIds),
                    'CategoryTranslations.language_id' => $languageId ?? 0,
                ])
                ->enableHydration(false)
                ->all()
                ->toList();

            foreach ($catTranslations as $ct) {
                $cid = (int)($ct['category_id'] ?? 0);
                if ($cid > 0 && !isset($categoryNames[$cid])) {
                    $categoryNames[$cid] = (string)($ct['name'] ?? '');
                }
            }

            // Fallback: load any translation if the language-specific one is missing
            $missingIds = array_values(array_filter($categoryIds, fn($id) => !isset($categoryNames[(int)$id])));
            if ($missingIds) {
                $fallbackTranslations = TableRegistry::getTableLocator()->get('CategoryTranslations')
                    ->find()
                    ->where(['CategoryTranslations.category_id IN' => $missingIds])
                    ->enableHydration(false)
                    ->all()
                    ->toList();
                foreach ($fallbackTranslations as $ct) {
                    $cid = (int)($ct['category_id'] ?? 0);
                    if ($cid > 0 && !isset($categoryNames[$cid]) && ($ct['name'] ?? '') !== '') {
                        $categoryNames[$cid] = (string)$ct['name'];
                    }
                }
            }
        }

        $categoryBreakdown = [];
        foreach ($breakdownRaw as $row) {
            $cid = (int)($row['category_id'] ?? 0);
            $avgRaw = $row['avg_score'] !== null ? (float)$row['avg_score'] : null;
            $bestRaw = $row['best_score'] !== null ? (float)$row['best_score'] : null;
            $categoryBreakdown[] = [
                'category_id' => $cid,
                'name' => $cid > 0 ? ($categoryNames[$cid] ?? __('Category #{0}', $cid)) : __('Uncategorized'),
                'attempts' => (int)($row['attempts'] ?? 0),
                'avg_score' => $avgRaw,
                'best_score' => $bestRaw,
            ];
        }

        return $categoryBreakdown;
    }
}
