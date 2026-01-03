<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('My Profile'));
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            <div class="mb-4 position-relative d-inline-block">
                <?php if ($user->avatar_url) : ?>
                    <?= $this->Html->image($user->avatar_url, [
                        'alt' => $user->email,
                        'class' => 'rounded-circle img-thumbnail shadow-sm',
                        'style' => 'width: 150px; height: 150px; object-fit: cover;'
                    ]) ?>
                <?php else : ?>
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 150px; height: 150px;">
                        <span class="text-white display-4 text-uppercase"><?= substr($user->email, 0, 1) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <h2 class="fw-bold mb-1 text-white">
                <?= $user->username ? h($user->username) : h($user->email) ?>
            </h2>
            <?php if ($user->username) : ?>
                <p class="text-white mb-2"><?= h($user->email) ?></p>
            <?php endif; ?>
            
            <p class="mb-4">
                <span class="badge bg-secondary text-white border border-light">
                    <?= $user->hasValue('role') ? h($user->role->name) : __('User') ?>
                </span>
            </p>

            <div class="row g-3 text-center mb-4 justify-content-center">
                <div class="col-6 col-sm-auto">
                    <div class="p-2">
                        <small class="text-white d-block text-uppercase fw-bold" style="font-size: 0.7rem;"><?= __('Joined') ?></small>
                        <span class="fw-bold fs-5 text-white"><?= $user->created_at ? $user->created_at->i18nFormat('yyyy. MMM d.') : '-' ?></span>
                    </div>
                </div>
                <div class="col-6 col-sm-auto">
                    <div class="p-2">
                        <small class="text-white d-block text-uppercase fw-bold" style="font-size: 0.7rem;"><?= __('Last Login') ?></small>
                        <span class="fw-bold fs-5 text-white"><?= $user->last_login_at ? $user->last_login_at->i18nFormat('yyyy. MMM d. HH:mm') : '-' ?></span>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 col-sm-8 mx-auto">
                <?= $this->Html->link(
                    __('Edit Profile'),
                    ['action' => 'profileEdit', 'lang' => $lang],
                    ['class' => 'btn btn-primary btn-lg rounded-pill']
                ) ?>
            </div>
        </div>
    </div>
</div>
