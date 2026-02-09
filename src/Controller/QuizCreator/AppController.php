<?php
declare(strict_types=1);

namespace App\Controller\QuizCreator;

use App\Controller\AppController as BaseAppController;
use App\Model\Entity\Role;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;

class AppController extends BaseAppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->setLayout('admin');
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return;
        }

        if ((int)$identity->get('role_id') !== Role::CREATOR) {
            throw new ForbiddenException();
        }
    }
}
