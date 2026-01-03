<?php
/**
 * @var \App\View\AppView $this
 */
use App\Model\Entity\Role;

$lang = $this->request->getParam('lang', 'en');
$identity = $this->request->getAttribute('identity');
$isLoggedIn = $identity !== null;
$isAdmin = $isLoggedIn && (int)$identity->get('role_id') === Role::ADMIN;

$displayName = $isLoggedIn ? ($identity->get('name') ?: $identity->get('email') ?: __('Admin')) : __('Admin');
$roleLabel = $isAdmin ? __('Super Admin') : __('Admin');
?>

<nav class="navbar navbar-dark mf-navbar">
    <div class="container-fluid px-3 px-lg-5">
        <div class="d-flex align-items-center gap-2">
            <a class="navbar-brand mf-brand d-flex align-items-center" href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index', 'lang' => $lang]) ?>">
                <?= $this->Html->image('favicon-128x128.png', [
                    'alt' => 'MindForge',
                    'class' => 'mf-logo',
                ]) ?>
            </a>
            <span class="badge text-bg-secondary"><?= __('Admin') ?></span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end d-none d-sm-block">
                <div class="fw-semibold" style="line-height:1.1;">
                    <?= h($displayName) ?>
                </div>
                <div class="mf-muted" style="font-size:0.85rem;">
                    <?= h($roleLabel) ?>
                </div>
            </div>

            <?php if ($isLoggedIn) : ?>
                <?= $this->Form->postLink(
                    __('Logout'),
                    ['controller' => 'Users', 'action' => 'logout', 'lang' => $lang],
                    ['class' => 'btn btn-sm btn-outline-light']
                ) ?>
            <?php endif; ?>
        </div>
    </div>
</nav>
