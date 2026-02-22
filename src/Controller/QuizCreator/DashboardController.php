<?php
declare(strict_types=1);

namespace App\Controller\QuizCreator;

use App\Service\CreatorDashboardService;

class DashboardController extends AppController
{
    /**
     * Render creator dashboard metrics.
     *
     * @return void
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : 0;

        $dashboardService = new CreatorDashboardService();
        $metrics = $dashboardService->getMetrics($userId);

        $this->set('title', __('Quiz Creator'));
        $this->set($metrics);
    }
}
