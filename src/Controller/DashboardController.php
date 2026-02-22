<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Role;
use App\Service\DashboardMetricsService;
use Psr\Http\Message\ResponseInterface;

class DashboardController extends AppController
{
    /**
     * Dashboard index.
     *
     * Admin users are redirected to the admin dashboard.
     *
     * @return \Psr\Http\Message\ResponseInterface|null Redirects for admins, renders view otherwise.
     */
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

        $userId = $identity ? (int)$identity->getIdentifier() : 0;
        $langCode = (string)$this->request->getParam('lang', 'en');

        $dashboardService = new DashboardMetricsService();
        $recentAttempts = $dashboardService->getRecentAttempts($userId, $langCode);

        $this->set(compact('recentAttempts'));

        return null;
    }
}
