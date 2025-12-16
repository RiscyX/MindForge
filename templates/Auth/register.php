<?php
$this->assign('title', __('Register'));

$this->Html->script('register.js?v=2', ['block' => 'script']);
?>

<div class="container-fluid min-vh-100">
    <div class="row g-0 min-vh-100 align-items-stretch">
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

        <div class="col-12 col-lg-6 mf-right d-flex align-items-start align-items-lg-center justify-content-center p-3 p-sm-4 p-lg-5">
            <div class="mf-card p-4 p-sm-5">
                <h2 class="mb-1"><?= __('Create an Account') ?></h2>
                <p class="mf-muted mb-4"><?= __('Start your journey with MindForge today.') ?></p>

                <?= $this->Form->create($user, [
                    'url' => ['controller' => 'Auth', 'action' => 'register'],
                    'class' => 'needs-validation',
                    'novalidate' => true,
                    'data-mf-register-form' => true,
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
                            value="<?= h($user->email ?? '') ?>"
                            required
                        />
                        <div class="invalid-feedback">
                            <?= __('Please enter a valid email address.') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password"><?= __('Password') ?></label>
                        <input
                            id="password"
                            data-mf-password
                            name="password"
                            type="password"
                            class="form-control"
                            placeholder="<?= h(__('Create a strong password')) ?>"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        />

                        <div data-mf-strength-wrap class="mt-2 d-none" aria-hidden="true">
                            <div class="mf-strength">
                                <div data-mf-strength-bar></div>
                            </div>
                        </div>

                        <div class="invalid-feedback">
                            <?= __('Password must be at least 8 characters.') ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="confirmPassword"><?= __('Confirm Password') ?></label>
                        <input
                            id="confirmPassword"
                            data-mf-confirm
                            name="password_confirm"
                            type="password"
                            class="form-control"
                            placeholder="<?= h(__('Confirm your password')) ?>"
                            autocomplete="new-password"
                            required
                        />
                        <div class="invalid-feedback">
                            <?= __('Passwords do not match.') ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><?= __('Sign Up') ?></button>

                    <div class="text-center mt-3 mf-muted">
                        <?= __('Already have an account?') ?>
                        <a href="<?= $this->Url->build('/login') ?>" class="link-primary"><?= __('Log In') ?></a>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>
