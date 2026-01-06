<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var \Cake\Collection\CollectionInterface<string> $roles
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Edit User'));
?>

<div class="mf-admin-form-center">
    <div class="mf-admin-card p-4 mt-4 w-100" style="max-width: 720px;">
        <?= $this->Form->create($user, ['type' => 'file', 'novalidate' => false]) ?>

    <div class="row g-3">
        <div class="col-12">
            <?= $this->Form->control('email', [
                'label' => __('Email'),
                'class' => 'form-control mf-admin-input',
                'required' => true,
            ]) ?>
        </div>

        <div class="col-12">
            <?= $this->Form->control('username', [
                'label' => __('Username (optional)'),
                'class' => 'form-control mf-admin-input',
                'required' => false,
            ]) ?>
        </div>

        <div class="col-12">
            <?= $this->Form->control('password', [
                'label' => __('New password (leave blank to keep current)'),
                'type' => 'password',
                'class' => 'form-control mf-admin-input',
                'required' => false,
                'minlength' => 8,
                'value' => '',
            ]) ?>
        </div>

        <div class="col-12 col-md-6">
            <?= $this->Form->control('role_id', [
                'label' => __('Role'),
                'options' => $roles,
                'class' => 'form-select mf-admin-select',
                'required' => true,
            ]) ?>
        </div>

        <div class="col-12 col-md-6">
            <?= $this->Form->control('avatar_file', [
                'label' => __('Avatar (optional)'),
                'type' => 'file',
                'accept' => 'image/*',
                'class' => 'form-control mf-admin-input',
                'required' => false,
            ]) ?>
        </div>
    </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-4">
            <div class="d-flex gap-2">
                <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary mf-admin-btn']) ?>
                <?= $this->Html->link(
                    __('Cancel'),
                    [
                        'prefix' => 'Admin',
                        'controller' => 'Users',
                        'action' => 'index',
                        'lang' => $lang,
                    ],
                    ['class' => 'btn btn-outline-light mf-admin-btn'],
                ) ?>
            </div>

            <div class="d-flex gap-2">
                <?php if ($user->is_blocked) : ?>
                    <button class="btn btn-danger mf-admin-btn" type="button" disabled aria-disabled="true">
                        <?= __('Ban') ?>
                    </button>
                    <?= $this->Form->postLink(
                        __('Unban'),
                        [
                            'prefix' => 'Admin',
                            'controller' => 'Users',
                            'action' => 'unban',
                            $user->id,
                            'lang' => $lang,
                        ],
                        ['class' => 'btn btn-success mf-admin-btn'],
                    ) ?>
                <?php else : ?>
                    <?= $this->Form->postLink(
                        __('Ban'),
                        [
                            'prefix' => 'Admin',
                            'controller' => 'Users',
                            'action' => 'ban',
                            $user->id,
                            'lang' => $lang,
                        ],
                        [
                            'class' => 'btn btn-danger mf-admin-btn',
                            'confirm' => __('Are you sure you want to ban this user?'),
                        ],
                    ) ?>
                    <button class="btn btn-success mf-admin-btn" type="button" disabled aria-disabled="true">
                        <?= __('Unban') ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
