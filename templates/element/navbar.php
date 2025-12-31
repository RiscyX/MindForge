<?php
/**
 * Navbar element
 *
 * @var \App\View\AppView $this
 */
use App\Model\Entity\Role;

$lang = $this->request->getParam('lang', 'en');
$currentAction = $this->request->getParam('action');
$identity = $this->request->getAttribute('identity');
$isLoggedIn = $identity !== null;
$isAdmin = $isLoggedIn
    && (int)$identity->get('role_id') === Role::ADMIN;

?>

<nav class="navbar navbar-expand-lg navbar-dark mf-navbar">
    <div class="container-fluid px-3 px-lg-5">
        <!-- Brand -->
        <a class="navbar-brand mf-brand" href="<?=env('BASE_URL') . '/' . h($lang) ?>">
            <?= $this->Html->image('favicon-128x128.png', [
                'alt' => 'MindForge',
                'class' => 'mf-logo',
            ]) ?>
        </a>

        <!-- Hamburger Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="<?= __('Toggle navigation') ?>">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (!$isLoggedIn) : ?>
                    <!-- Guest -->
                    <li class="nav-item">
                        <a class="nav-link<?= $currentAction === 'login' ? ' active' : '' ?>"
                           href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]) ?>">
                            <?= __('Log In') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentAction === 'register' ? ' active' : '' ?>"
                           href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'register', 'lang' => $lang]) ?>">
                            <?= __('Sign Up') ?>
                        </a>
                    </li>

                <?php else : ?>
                    <!-- Logged in -->
                    <?php if ($isAdmin) : ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $this->request->getParam('prefix') === 'Admin' ? ' active' : '' ?>"
                               href="<?= $this->Url->build([
                                   'prefix' => 'Admin',
                                   'controller' => 'Dashboard',
                                   'action' => 'index',
                                   'lang' => $lang,
                               ]) ?>">
                                <?= __('Admin') ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link<?= $currentAction === 'index' && $this->request->getParam('controller') === 'Dashboard' ? ' active' : '' ?>"
                           href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index', 'lang' => $lang]) ?>">
                            <?= __('Dashboard') ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <?= $this->Form->postLink(
                            __('Logout'),
                            ['controller' => 'Users', 'action' => 'logout', 'lang' => $lang],
                            [
                                'class' => 'nav-link',
                                'confirm' => __('Are you sure you want to log out?'),
                            ],
                        ) ?>
                    </li>
                <?php endif; ?>
            </ul>

        </div>
    </div>
</nav>
