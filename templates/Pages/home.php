<?php
/**
 * Home / Landing page
 *
 * @var \App\View\AppView $this
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('MindForge'));
?>

<div class="container-fluid min-vh-100">
    <div class="row g-0 min-vh-100 align-items-stretch">

        <!-- LEFT / CONTENT -->
        <div class="col-12 col-lg-6 mf-right d-flex align-items-start align-items-lg-center justify-content-center p-3 p-sm-4 p-lg-5">
            <div class="mf-card p-4 p-sm-5 w-100" style="max-width: 36rem;">
                <h1 class="mb-2"><?= __('Welcome to MindForge') ?></h1>
                <p class="mf-muted mb-4">
                    <?= __('An AI-powered platform built to help you learn smarter, test faster, and improve continuously.') ?>
                </p>

                <ul class="list-unstyled mb-4">
                    <li class="mb-2">• <?= __('Create and take intelligent tests') ?></li>
                    <li class="mb-2">• <?= __('Track your progress over time') ?></li>
                    <li class="mb-2">• <?= __('Learn with AI-assisted feedback') ?></li>
                </ul>

                <div class="d-flex gap-3 flex-column flex-sm-row">
                    <a
                        href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]) ?>"
                        class="btn btn-primary w-100"
                    >
                        <?= __('Log In') ?>
                    </a>

                    <a
                        href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'register', 'lang' => $lang]) ?>"
                        class="btn btn-outline-light w-100"
                    >
                        <?= __('Create Account') ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- RIGHT / VISUAL -->
        <div class="col-12 col-lg-6 d-none d-lg-flex flex-column mf-left p-5">
            <div class="flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center">
                <div class="mf-orb" aria-hidden="true"></div>

                <div class="w-100" style="max-width: 36rem;">
                    <h2 class="display-6 mf-tagline mb-3">
                        <?= __('Build knowledge. Test understanding. Improve.') ?>
                    </h2>
                    <p class="mf-subtitle mb-0">
                        <?= __('MindForge combines structured testing with AI to help you focus on what actually matters.') ?>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
