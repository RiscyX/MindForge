<?php
declare(strict_types=1);

namespace App\Controller\QuizCreator;

class DashboardController extends AppController
{
    public function index(): void
    {
        $this->set('title', __('Quiz Creator'));
    }
}
