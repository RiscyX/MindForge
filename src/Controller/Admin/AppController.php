<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseAppController;
use App\Model\Entity\Role;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

class AppController extends BaseAppController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setLayout('admin');
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return;
        }

        if ((int)$identity->get('role_id') !== Role::ADMIN) {
            throw new ForbiddenException();
        }
    }

    /**
     * Log an admin action to the activity_logs table.
     *
     * @param string $action  e.g. 'admin_delete_user', 'admin_delete_category'
     * @param array<string,mixed> $extra  Additional context merged into the action string.
     * @return void
     */
    protected function logAdminAction(string $action, array $extra = []): void
    {
        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return;
        }

        $userId = (int)$identity->get('id');
        $ip = (string)($this->request->clientIp() ?? '');
        $userAgent = (string)($this->request->getHeaderLine('User-Agent'));

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
