<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\ResultSet;

/**
 * Loads supplementary context data for the admin user edit page.
 *
 * Extracts the three sidebar queries (test attempts, activity logs, device logs)
 * previously inlined in Admin\UsersController::edit().
 */
class AdminUserDetailService
{
    use LocatorAwareTrait;

    /**
     * Load recent test attempts for a user (with test title translation).
     *
     * @param string|int $userId User id.
     * @param int $limit Max rows.
     * @return \Cake\ORM\ResultSet
     */
    public function recentTestAttempts(int|string $userId, int $limit = 5): ResultSet
    {
        return $this->fetchTable('TestAttempts')
            ->find()
            ->contain([
                'Tests' => static fn($q) => $q->contain([
                    'TestTranslations' => static fn($tq) => $tq
                        ->select(['TestTranslations.test_id', 'TestTranslations.title'])
                        ->limit(1),
                ]),
            ])
            ->where(['TestAttempts.user_id' => $userId])
            ->orderByDesc('TestAttempts.created_at')
            ->limit($limit)
            ->all();
    }

    /**
     * Load recent activity logs for a user.
     *
     * @param string|int $userId User id.
     * @param int $limit Max rows.
     * @return \Cake\ORM\ResultSet
     */
    public function recentActivityLogs(int|string $userId, int $limit = 5): ResultSet
    {
        return $this->fetchTable('ActivityLogs')
            ->find()
            ->where(['ActivityLogs.user_id' => $userId])
            ->orderByDesc('ActivityLogs.created_at')
            ->limit($limit)
            ->all();
    }

    /**
     * Load recent device logs for a user.
     *
     * @param string|int $userId User id.
     * @param int $limit Max rows.
     * @return \Cake\ORM\ResultSet
     */
    public function recentDeviceLogs(int|string $userId, int $limit = 5): ResultSet
    {
        return $this->fetchTable('DeviceLogs')
            ->find()
            ->where(['DeviceLogs.user_id' => $userId])
            ->orderByDesc('DeviceLogs.created_at')
            ->limit($limit)
            ->all();
    }
}
