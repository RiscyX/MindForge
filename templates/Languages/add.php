<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Language $language
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Add Language'));
?>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-translate me-2 text-primary" aria-hidden="true"></i><?= __('Add Language') ?>
        </h1>
        <p class="mf-muted mb-0"><?= __('Register a new supported language for the platform.') ?></p>
    </div>
    <?= $this->Html->link(
        '<i class="bi bi-arrow-left me-1" aria-hidden="true"></i>' . h(__('Back')),
        ['action' => 'index', 'lang' => $lang],
        ['class' => 'btn btn-sm btn-outline-light mf-admin-btn', 'escape' => false],
    ) ?>
</div>

<div class="mf-admin-form-center">
    <div class="mf-admin-card p-4 w-100" style="max-width: 480px;">
        <?= $this->Form->create($language) ?>

        <div class="mb-3">
            <?= $this->Form->control('code', [
                'class' => 'form-control mf-admin-input',
                'label' => __('Code'),
                'placeholder' => 'en',
            ]) ?>
        </div>
        <div class="mb-4">
            <?= $this->Form->control('name', [
                'class' => 'form-control mf-admin-input',
                'label' => __('Name'),
                'placeholder' => 'English',
            ]) ?>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?= $this->Form->button(__('Create'), ['class' => 'btn btn-primary mf-admin-btn', 'data-loading-text' => __('Saving…')]) ?>
            <?= $this->Html->link(
                __('Cancel'),
                ['action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-outline-light mf-admin-btn'],
            ) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
