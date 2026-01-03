<?php
/**
 * @var \App\View\AppView $this
 */

$lang = $this->request->getParam('lang', 'en');
?>

<aside class="mf-admin-sidebar d-none d-lg-flex flex-column">
    <div class="mf-admin-sidebar__section">
        <div class="mf-admin-sidebar__label"><?= __('Management') ?></div>
        <nav class="mf-admin-nav">
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Users') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Categories') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Tests') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Questions') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Answers') ?></a>
        </nav>
    </div>

    <div class="mf-admin-sidebar__section mt-2">
        <div class="mf-admin-sidebar__label"><?= __('System') ?></div>
        <nav class="mf-admin-nav">
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Logs') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Device Logs') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('AI Requests') ?></a>
        </nav>
    </div>

    <div class="mt-auto mf-admin-sidebar__footer">
        <?= $this->Form->postLink(
            __('Logout'),
            ['controller' => 'Users', 'action' => 'logout', 'lang' => $lang],
            ['class' => 'mf-admin-nav__link']
        ) ?>
    </div>
</aside>
