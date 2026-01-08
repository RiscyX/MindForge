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

        <?php
            $avatarSrc = $user->avatar_url ?: '/img/avatars/stockpfp.jpg';
        ?>

        <div class="row g-4">
            <div class="col-12 col-md-6 d-flex flex-column">
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
                            'label' => __('Username'),
                            'placeholder' => __('Optional'),
                            'class' => 'form-control mf-admin-input',
                            'required' => false,
                        ]) ?>
                    </div>

                    <div class="col-12">
                        <?= $this->Form->control('password', [
                            'label' => __('New password'),
                            'placeholder' => __('Leave blank to keep current'),
                            'type' => 'password',
                            'class' => 'form-control mf-admin-input',
                            'required' => false,
                            'minlength' => 8,
                            'value' => '',
                        ]) ?>
                    </div>
                </div>

                <div class="mt-auto pt-3">
                    <?= $this->Form->control('role_id', [
                        'label' => __('Role'),
                        'options' => $roles,
                        'class' => 'form-select mf-admin-select',
                        'required' => true,
                    ]) ?>
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

                <?= $this->Form->postLink(
                    __('Delete'),
                    [
                        'prefix' => 'Admin',
                        'controller' => 'Users',
                        'action' => 'delete',
                        $user->id,
                        'lang' => $lang,
                    ],
                    [
                        'class' => 'btn btn-outline-danger mf-admin-btn',
                        'confirm' => __('Are you sure you want to delete this user?'),
                    ],
                ) ?>
            </div>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
