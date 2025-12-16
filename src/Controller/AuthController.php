<?php
declare(strict_types=1);

namespace App\Controller;

class AuthController extends AppController
{
    /**
     * @return void
     */
    public function login(): void
    {
        $this->viewBuilder()->setLayout('default');
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->viewBuilder()->setLayout('default');
    }
}
