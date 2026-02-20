<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Difficulty $difficulty
 * @var iterable<\App\Model\Entity\Language> $languages
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Add Difficulty'));
?>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-bar-chart-steps me-2 text-primary" aria-hidden="true"></i><?= __('Add Difficulty') ?>
        </h1>
        <p class="mf-muted mb-0"><?= __('Define a new difficulty level with translations.') ?></p>
    </div>
    <?= $this->Html->link(
        '<i class="bi bi-arrow-left me-1" aria-hidden="true"></i>' . h(__('Back')),
        ['action' => 'index', 'lang' => $lang],
        ['class' => 'btn btn-sm btn-outline-light mf-admin-btn', 'escape' => false],
    ) ?>
</div>

<div class="mf-admin-form-center">
    <div class="mf-admin-card p-4 w-100" style="max-width: 760px;">
        <?= $this->Form->create($difficulty) ?>

        <div class="mb-4">
            <?= $this->Form->control('level', [
                'class' => 'form-control mf-admin-input',
                'label' => __('Level'),
                'type' => 'number',
                'min' => 1,
            ]) ?>
        </div>

        <h5 class="mb-3">
            <i class="bi bi-translate me-2 text-primary" aria-hidden="true"></i><?= __('Translations') ?>
        </h5>

        <div class="row g-3">
            <?php foreach ($languages as $i => $language) : ?>
                <div class="col-12 col-md-6">
                    <div class="mf-admin-card p-3 h-100">
                        <div class="mb-3 d-flex align-items-center gap-2">
                            <span class="badge bg-primary"><?= h($language->code) ?></span>
                            <span class="fw-semibold"><?= h($language->name) ?></span>
                        </div>
                        <?= $this->Form->hidden("difficulty_translations.$i.language_id", ['value' => $language->id]) ?>
                        <?= $this->Form->control("difficulty_translations.$i.name", [
                            'class' => 'form-control mf-admin-input',
                            'label' => __('Name'),
                        ]) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex align-items-center gap-2 mt-4">
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
