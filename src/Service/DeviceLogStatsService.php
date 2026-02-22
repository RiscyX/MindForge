<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\ResultSet;

/**
 * Builds the filtered device log list and aggregated statistics for the admin index.
 */
class DeviceLogStatsService
{
    use LocatorAwareTrait;

    /**
     * Build a filtered device log result set for the admin index.
     *
     * @param array<string, string> $filters Filter values (user_id, device_type, from, to).
     * @return \Cake\ORM\ResultSet
     */
    public function getFilteredLogs(array $filters): ResultSet
    {
        $table = $this->fetchTable('DeviceLogs');

        $query = $table->find()
            ->select([
                'DeviceLogs.id',
                'DeviceLogs.user_id',
                'DeviceLogs.ip_address',
                'DeviceLogs.user_agent',
                'DeviceLogs.device_type',
                'DeviceLogs.country',
                'DeviceLogs.city',
                'DeviceLogs.created_at',
            ])
            ->contain([
                'Users' => function (SelectQuery $q): SelectQuery {
                    return $q->select(['Users.id', 'Users.email']);
                },
            ])
            ->orderByDesc('DeviceLogs.created_at');

        if (in_array($filters['device_type'] ?? '', ['0', '1', '2'], true)) {
            $query->where(['DeviceLogs.device_type' => (int)$filters['device_type']]);
        }

        if (($filters['user_id'] ?? '') !== '' && ctype_digit($filters['user_id'])) {
            $query->where(['DeviceLogs.user_id' => (int)$filters['user_id']]);
        }

        if (($filters['from'] ?? '') !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['from']) === 1) {
            $query->where(['DeviceLogs.created_at >=' => $filters['from'] . ' 00:00:00']);
        }
        if (($filters['to'] ?? '') !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['to']) === 1) {
            $query->where(['DeviceLogs.created_at <=' => $filters['to'] . ' 23:59:59']);
        }

        return $query->all();
    }

    /**
     * Compute aggregate statistics for the device log admin dashboard.
     *
     * @return array{total: int, last24h: int, uniqueUsers: int, uniqueIps24h: int}
     */
    public function getStats(): array
    {
        $table = $this->fetchTable('DeviceLogs');
        $since = FrozenTime::now()->subHours(24);

        $total = (int)$table->find()->count();
        $last24h = (int)$table->find()->where(['DeviceLogs.created_at >=' => $since])->count();
        $uniqueUsers = (int)$table->find()
            ->select(['user_id'])
            ->distinct(['user_id'])
            ->where(['DeviceLogs.user_id IS NOT' => null])
            ->count();
        $uniqueIps24h = (int)$table->find()
            ->select(['ip_address'])
            ->distinct(['ip_address'])
            ->where([
                'DeviceLogs.created_at >=' => $since,
                'DeviceLogs.ip_address IS NOT' => null,
            ])
            ->count();

        return [
            'total' => $total,
            'last24h' => $last24h,
            'uniqueUsers' => $uniqueUsers,
            'uniqueIps24h' => $uniqueIps24h,
        ];
    }
}
