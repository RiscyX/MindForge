<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\DeviceLogsController as BaseDeviceLogsController;
use App\Model\Entity\Role;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

class DeviceLogsController extends BaseDeviceLogsController
{
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
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeRender(EventInterface $event)
    {
        $response = parent::beforeRender($event);
        $this->viewBuilder()->setTemplatePath('DeviceLogs');

        return $response;
    }
}
