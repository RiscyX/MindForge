<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;

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
