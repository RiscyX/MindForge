<?php
$this->assign('title', __('Resend Activation Email'));

$this->Html->script('login.js?v=1', ['block' => 'script']);
?>

<div class="container-fluid p-0 flex-grow-1 d-flex">
    <div class="flex-grow-1 d-flex align-items-center justify-content-center p-3 p-sm-4 p-lg-5">
        <div class="mf-card p-4 p-sm-5">
            <h2 class="mb-1"><?= __('Resend Activation Email') ?></h2>
            <p class="mf-muted mb-4"><?= __('Enter your email address and we\'ll send you a new activation link.') ?></p>

            <?= $this->Form->create(null, [
                'url' => ['controller' => 'Users', 'action' => 'resendActivation', 'lang' => $lang ?? 'en'],
                'class' => 'needs-validation',
                'novalidate' => true,
                'data-mf-login-form' => true,
            ]) ?>
                <div class="mb-4">
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

                <button type="submit" class="btn btn-primary w-100"><?= __('Send Activation Link') ?></button>

                <div class="text-center mt-3 mf-muted">
                    <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login', 'lang' => $lang ?? 'en']) ?>" class="link-primary">
                        <?= __('Back to login') ?>
                    </a>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
