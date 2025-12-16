<?php
/**
 * Navbar element
 *
 * @var \App\View\AppView $this
 */
use Cake\I18n\I18n;

$lang = $this->request->getParam('lang', 'en');
$currentAction = $this->request->getParam('action');
?>

<nav class="navbar navbar-expand-lg navbar-dark mf-navbar">
    <div class="container-fluid px-3 px-lg-5">
        <!-- Brand -->
        <a class="navbar-brand mf-brand" href="<?=env("BASE_URL").'/'.h($lang) ?>">
            <?= $this->Html->image('favicon-128x128.png', [
                'alt' => 'MindForge',
                'class' => 'mf-logo'
            ]) ?>
        </a>

        <!-- Hamburger Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="<?= __('Toggle navigation') ?>">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link<?= $currentAction === 'login' ? ' active' : '' ?>" href="<?=env("BASE_URL").'/'.h($lang) ?>/login">
                        <?= __('Log In') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= $currentAction === 'register' ? ' active' : '' ?>" href="<?=env("BASE_URL").'/'.h($lang) ?>/register">
                        <?= __('Sign Up') ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
