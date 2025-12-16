<?php
declare(strict_types=1);

namespace App\Controller;

class DashboardController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $this->viewBuilder()->setLayout('default');
    }
}
