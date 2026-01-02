<?php
$this->assign('title', __('Reset Password'));

$this->Html->script('reset_password.js?v=1', ['block' => 'script']);
?>

<div class="container-fluid p-0 flex-grow-1 d-flex">
    <div class="row g-0 flex-grow-1 w-100 align-items-stretch">
        <div class="col-12 col-lg-6 mf-right d-flex align-items-start align-items-lg-center justify-content-center p-3 p-sm-4 p-lg-5">
            <div class="mf-card p-4 p-sm-5">
                <h2 class="mb-1"><?= __('Set a New Password') ?></h2>
                <p class="mf-muted mb-4"><?= __('Choose a strong password for your account.') ?></p>

                <?= $this->Form->create(null, [
                    'url' => [
                        'controller' => 'Users',
                        'action' => 'resetPassword',
                        'lang' => $lang ?? 'en',
                        '?' => ['token' => $token ?? ''],
                    ],
                    'class' => 'needs-validation',
                    'novalidate' => true,
                    'data-mf-reset-form' => true,
                ]) ?>
                    <div class="mb-3">
                        <label class="form-label" for="password"><?= __('New Password') ?></label>
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

                    <button type="submit" class="btn btn-primary w-100"><?= __('Update Password') ?></button>

                    <div class="text-center mt-3 mf-muted">
                        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login', 'lang' => $lang ?? 'en']) ?>" class="link-primary">
                            <?= __('Back to login') ?>
                        </a>
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
