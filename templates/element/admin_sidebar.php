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
            <a class="mf-admin-nav__link" href="<?= $this->Url->build([
                'prefix' => 'Admin',
                'controller' => 'Users',
                'action' => 'index',
                'lang' => $lang,
            ]) ?>"><?= __('Users') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Categories') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Users') ?></a>
            <?= $this->Html->link(__('Categories'), ['prefix' => false, 'controller' => 'Categories', 'action' => 'index', 'lang' => $lang], ['class' => 'mf-admin-nav__link']) ?>
            <?= $this->Html->link(__('Difficulties'), ['prefix' => false, 'controller' => 'Difficulties', 'action' => 'index', 'lang' => $lang], ['class' => 'mf-admin-nav__link']) ?>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Tests') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Questions') ?></a>
            <a class="mf-admin-nav__link" href="#" onclick="return false;"><?= __('Answers') ?></a>
            <?= $this->Html->link(__('Languages'), ['prefix' => false, 'controller' => 'Languages', 'action' => 'index', 'lang' => $lang], ['class' => 'mf-admin-nav__link']) ?>
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
</aside>
