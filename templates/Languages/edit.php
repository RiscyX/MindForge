<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Language $language
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Edit Language'));
?>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-translate me-2 text-primary" aria-hidden="true"></i><?= __('Edit Language') ?>
        </h1>
        <p class="mf-muted mb-0"><?= __('Update language code and display name.') ?></p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?= $this->Form->postLink(
            '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
            ['action' => 'delete', $language->id, 'lang' => $lang],
            [
                'confirm' => __('Are you sure you want to delete # {0}?', $language->id),
                'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                'escape' => false,
            ],
        ) ?>
        <?= $this->Html->link(
            '<i class="bi bi-arrow-left me-1" aria-hidden="true"></i>' . h(__('Back')),
            ['action' => 'index', 'lang' => $lang],
            ['class' => 'btn btn-sm btn-outline-light mf-admin-btn', 'escape' => false],
        ) ?>
    </div>
</div>

<div class="mf-admin-form-center">
    <div class="mf-admin-card p-4 w-100" style="max-width: 480px;">
        <?= $this->Form->create($language) ?>

        <div class="mb-3">
            <?= $this->Form->control('code', [
                'class' => 'form-control mf-admin-input',
                'label' => __('Code'),
            ]) ?>
        </div>
        <div class="mb-4">
            <?= $this->Form->control('name', [
                'class' => 'form-control mf-admin-input',
                'label' => __('Name'),
            ]) ?>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary mf-admin-btn', 'data-loading-text' => __('Saving…')]) ?>
            <?= $this->Html->link(
                __('Cancel'),
                ['action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-outline-light mf-admin-btn'],
            ) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
