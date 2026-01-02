<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Role;
use Psr\Http\Message\ResponseInterface;

class DashboardController extends AppController
{
    public function index(): ?ResponseInterface
    {
        $identity = $this->request->getAttribute('identity');
        if ($identity !== null && (int)$identity->get('role_id') === Role::ADMIN) {
            return $this->redirect([
                'prefix' => 'Admin',
                'controller' => 'Dashboard',
                'action' => 'index',
                'lang' => $this->request->getParam('lang', 'en'),
            ]);
        }

        $this->viewBuilder()->setLayout('default');

        return null;
    }
}
