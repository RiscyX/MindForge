<?php
/**
 * @var \App\View\AppView $this
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Quiz Creator'));
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Quiz Creator') ?></h1>
        <div class="mf-muted"><?= __('Create and manage quizzes, questions and answers.') ?></div>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="mf-admin-card p-3 h-100">
            <div class="fw-semibold mb-1"><?= __('Quizzes') ?></div>
            <div class="mf-muted mb-3"><?= __('Manage quizzes and generate content with AI.') ?></div>
            <?= $this->Html->link(__('Open'), ['prefix' => false, 'controller' => 'Tests', 'action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light']) ?>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="mf-admin-card p-3 h-100">
            <div class="fw-semibold mb-1"><?= __('Questions') ?></div>
            <div class="mf-muted mb-3"><?= __('Review and edit the question bank.') ?></div>
            <?= $this->Html->link(__('Open'), ['prefix' => false, 'controller' => 'Questions', 'action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light']) ?>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="mf-admin-card p-3 h-100">
            <div class="fw-semibold mb-1"><?= __('Answers') ?></div>
            <div class="mf-muted mb-3"><?= __('Maintain answer choices and translations.') ?></div>
            <?= $this->Html->link(__('Open'), ['prefix' => false, 'controller' => 'Answers', 'action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light']) ?>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="mf-admin-card p-3 h-100">
            <div class="fw-semibold mb-1"><?= __('Categories') ?></div>
            <div class="mf-muted mb-3"><?= __('Manage category taxonomy for filtering.') ?></div>
            <?= $this->Html->link(__('Open'), ['prefix' => false, 'controller' => 'Categories', 'action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light']) ?>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-4">
        <div class="mf-admin-card p-3 h-100">
            <div class="fw-semibold mb-1"><?= __('Difficulties') ?></div>
            <div class="mf-muted mb-3"><?= __('Manage difficulty levels used by quizzes.') ?></div>
            <?= $this->Html->link(__('Open'), ['prefix' => false, 'controller' => 'Difficulties', 'action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light']) ?>
        </div>
    </div>
</div>
