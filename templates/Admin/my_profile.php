<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string $lang
 */

$this->assign('title', __('My Profile'));

$avatarSrc = $user->avatar_url ?: '/img/avatars/stockpfp.jpg';
?>

<div class="mf-admin-form-center">
    <div class="mf-admin-card p-4 mt-4 w-100" style="max-width: 720px;">
        <?= $this->Form->create($user, ['type' => 'file', 'novalidate' => false]) ?>

        <div class="row g-4">
            <div class="col-12 col-md-6 d-flex flex-column">
                <div class="row g-3">
                    <div class="col-12">
                        <?= $this->Form->control('email', [
                            'label' => __('Email'),
                            'class' => 'form-control mf-admin-input',
                            'disabled' => true,
                        ]) ?>
                    </div>

                    <div class="col-12">
                        <?= $this->Form->control('username', [
                            'label' => __('Username'),
                            'placeholder' => __('Optional'),
                            'class' => 'form-control mf-admin-input',
                            'required' => false,
                        ]) ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="roleDisplay"><?= __('Role') ?></label>
                        <input
                            id="roleDisplay"
                            type="text"
                            class="form-control mf-admin-input"
                            value="<?= h($user->role->name ?? (string)$user->role_id) ?>"
                            disabled
                        />
                    </div>

                    <div class="col-12">
                        <div class="mf-muted" style="font-size: 0.95rem;">
                            <div>
                                <span class="fw-semibold text-white"><?= __('Last login') ?></span>
                                <span> - </span>
                                <span><?= h($user->last_login_at ? $user->last_login_at->i18nFormat('yyyy-MM-dd HH:mm') : '-') ?></span>
                            </div>
                            <div class="mt-1">
                                <span class="fw-semibold text-white"><?= __('Created') ?></span>
                                <span> - </span>
                                <span><?= h($user->created_at ? $user->created_at->i18nFormat('yyyy-MM-dd HH:mm') : '-') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-3">
                    <div class="d-grid gap-2">
                        <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary mf-admin-btn', 'data-loading-text' => __('Saving…')]) ?>

                        <?= $this->Form->postLink(
                            __('Send password reset email'),
                            [
                                'prefix' => 'Admin',
                                'controller' => 'Users',
                                'action' => 'requestPasswordReset',
                                'lang' => $lang,
                            ],
                            [
                                'class' => 'btn btn-outline-light mf-admin-btn',
                                'confirm' => __('Send a password reset link to your email address?'),
                            ],
                        ) ?>

                        <?= $this->Html->link(
                            __('Back to dashboard'),
                            ['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index', 'lang' => $lang],
                            ['class' => 'btn btn-outline-light mf-admin-btn'],
                        ) ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 d-flex flex-column align-items-center">
                <div class="flex-grow-1 d-flex align-items-center justify-content-center w-100">
                    <?= $this->Html->image(
                        $avatarSrc,
                        [
                            'alt' => __('Avatar'),
                            'class' => 'rounded-circle border',
                            'style' => 'width:160px;height:160px;object-fit:cover;',
                        ],
                    ) ?>
                </div>

                <div class="w-100 mt-auto pt-3">
                    <?= $this->Form->control('avatar_file', [
                        'label' => __('Avatar'),
                        'type' => 'file',
                        'accept' => 'image/*',
                        'class' => 'form-control mf-admin-input',
                        'required' => false,
                    ]) ?>
                </div>
            </div>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
