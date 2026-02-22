<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Aggregates AI request statistics for the admin index dashboard.
 */
class AiRequestStatsService
{
    use LocatorAwareTrait;

    /**
     * Compute all AI request statistics for the admin index page.
     *
     * @return array{total: int, last24h: int, successTotal: int, success24h: int, uniqueUsers24h: int, topTypes24h: list<array{type: string, count: int}>, topSources24h: list<array{source_reference: string, count: int}>, totalTokens: int, totalCostUsd: float}
     */
    public function getStats(): array
    {
        $table = $this->fetchTable('AiRequests');
        $since = FrozenTime::now()->subHours(24);

        $total = (int)$table->find()->count();
        $last24h = (int)$table->find()->where(['AiRequests.created_at >=' => $since])->count();
        $successTotal = (int)$table->find()->where(['AiRequests.status' => 'success'])->count();
        $success24h = (int)$table->find()->where([
            'AiRequests.created_at >=' => $since,
            'AiRequests.status' => 'success',
        ])->count();
        $uniqueUsers24h = (int)$table->find()
            ->select(['user_id'])
            ->distinct(['user_id'])
            ->where([
                'AiRequests.created_at >=' => $since,
                'AiRequests.user_id IS NOT' => null,
            ])
            ->count();

        $topTypes24h = $table->find()
            ->select([
                'type' => 'AiRequests.type',
                'count' => $table->find()->func()->count('*'),
            ])
            ->where(['AiRequests.created_at >=' => $since])
            ->groupBy(['AiRequests.type'])
            ->orderByDesc('count')
            ->limit(5)
            ->enableHydration(false)
            ->all()
            ->toList();

        $topSources24h = $table->find()
            ->select([
                'source_reference' => 'AiRequests.source_reference',
                'count' => $table->find()->func()->count('*'),
            ])
            ->where([
                'AiRequests.created_at >=' => $since,
                'AiRequests.source_reference IS NOT' => null,
                'AiRequests.source_reference !=' => '',
            ])
            ->groupBy(['AiRequests.source_reference'])
            ->orderByDesc('count')
            ->limit(5)
            ->enableHydration(false)
            ->all()
            ->toList();

        $totalTokensRow = $table->find()
            ->select(['s' => $table->find()->func()->sum('AiRequests.total_tokens')])
            ->enableHydration(false)
            ->first();
        $totalTokens = $totalTokensRow ? (int)($totalTokensRow['s'] ?? 0) : 0;

        $totalCostRow = $table->find()
            ->select(['s' => $table->find()->func()->sum('AiRequests.cost_usd')])
            ->enableHydration(false)
            ->first();
        $totalCostUsd = $totalCostRow ? round((float)($totalCostRow['s'] ?? 0), 6) : 0.0;

        return [
            'total' => $total,
            'last24h' => $last24h,
            'successTotal' => $successTotal,
            'success24h' => $success24h,
            'uniqueUsers24h' => $uniqueUsers24h,
            'topTypes24h' => $topTypes24h,
            'topSources24h' => $topSources24h,
            'totalTokens' => $totalTokens,
            'totalCostUsd' => $totalCostUsd,
        ];
    }

    /**
     * Build user options for the AI request filter dropdown.
     *
     * Returns users who have at least one ai_request, keyed by id with email as value.
     *
     * @return array<int, string>
     */
    public function getUserOptions(): array
    {
        $table = $this->fetchTable('AiRequests');

        return $table->Users->find('list', [
            'keyField' => 'id',
            'valueField' => 'email',
        ])
            ->innerJoin(
                ['AR' => 'ai_requests'],
                ['AR.user_id = Users.id'],
            )
            ->distinct(['Users.id'])
            ->orderByAsc('Users.email')
            ->toArray();
    }
}
