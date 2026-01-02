<?php
$this->assign('title', __('Login'));

$this->Html->script('login.js?v=1', ['block' => 'script']);
?>

<div class="container-fluid p-0 flex-grow-1 d-flex">
    <div class="row g-0 flex-grow-1 w-100 align-items-stretch">
        <div class="col-12 col-lg-6 mf-right d-flex align-items-start align-items-lg-center justify-content-center p-3 p-sm-4 p-lg-5">
            <div class="mf-card p-4 p-sm-5">
                <h2 class="mb-1"><?= __('Welcome Back') ?></h2>
                <p class="mf-muted mb-4"><?= __('Log in to continue your journey.') ?></p>

                <?= $this->Form->create(null, [
                    'url' => ['controller' => 'Users', 'action' => 'login', 'lang' => $lang ?? 'en'],
                    'class' => 'needs-validation',
                    'novalidate' => true,
                    'data-mf-login-form' => true,
                ]) ?>
                    <div class="mb-3">
                        <label class="form-label" for="email"><?= __('Email Address') ?></label>
                        <input
                            id="email"
                            data-mf-email
                            name="email"
                            type="email"
                            class="form-control"
                            placeholder="you@example.com"
                            autocomplete="email"
                            required
                        />
                        <div class="invalid-feedback">
                            <?= __('Please enter a valid email address.') ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="form-label mb-0" for="password"><?= __('Password') ?></label>
                            <a class="link-primary small" href="<?= $this->Url->build(['lang' => $lang ?? 'en', 'controller' => 'Users', 'action' => 'forgotPassword']) ?>">
                                <?= __('Forgot password?') ?>
                            </a>
                        </div>
                        <input
                            id="password"
                            data-mf-password
                            name="password"
                            type="password"
                            class="form-control"
                            placeholder="<?= h(__('Enter your password')) ?>"
                            autocomplete="current-password"
                            required
                        />
                        <div class="invalid-feedback">
                            <?= __('Please enter your password.') ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><?= __('Log In') ?></button>

                    <div class="text-center mt-3 mf-muted">
                        <?= __('Don\'t have an account?') ?>
                        <a href="<?= $this->Url->build(['lang' => $lang ?? 'en', 'controller' => 'Users', 'action' => 'register']) ?>" class="link-primary"><?= __('Sign Up') ?></a>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        </div>

        <div class="col-12 col-lg-6 d-none d-lg-flex flex-column mf-left p-5">
            <div class="flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center">
                <div class="mf-orb" aria-hidden="true"></div>

                <div class="w-100" style="max-width: 36rem;">
                    <h1 class="display-5 mf-tagline mb-3"><?= __('Unlock Your Potential.') ?></h1>
                    <p class="mf-subtitle mb-0">
                        <?= __('AI-powered learning and testing, designed to help you master any subject faster and smarter.') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
