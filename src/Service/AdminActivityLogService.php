<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Records admin actions to the activity_logs table.
 *
 * Extracts the audit logging concern previously implemented as
 * Admin\AppController::logAdminAction(). Can be used from any
 * controller (admin or non-admin) without inheritance constraints.
 */
class AdminActivityLogService
{
    use LocatorAwareTrait;

    /**
     * Log an admin action.
     *
     * @param \Cake\Http\ServerRequest $request Current request (for identity, IP, UA).
     * @param string $action Action label, e.g. 'admin_delete_user'.
     * @param array<string, mixed> $extra Additional context merged into the action string.
     * @return void
     */
    public function log(ServerRequest $request, string $action, array $extra = []): void
    {
        $identity = $request->getAttribute('identity');
        if ($identity === null) {
            return;
        }

        $userId = (int)$identity->get('id');
        $ip = (string)($request->clientIp() ?? '');
        $userAgent = (string)($request->getHeaderLine('User-Agent'));

        if ($extra !== []) {
            $parts = [];
            foreach ($extra as $k => $v) {
                $parts[] = $k . '=' . $v;
            }
            $action = $action . ' [' . implode(', ', $parts) . ']';
        }

        /** @var \App\Model\Table\ActivityLogsTable $logs */
        $logs = $this->fetchTable('ActivityLogs');
        $entity = $logs->newEntity([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
        $logs->save($entity);
    }
}
