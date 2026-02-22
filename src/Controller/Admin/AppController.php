<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseAppController;
use App\Model\Entity\Role;
use App\Service\AdminActivityLogService;
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
        (new AdminActivityLogService())->log($this->request, $action, $extra);
    }
}
