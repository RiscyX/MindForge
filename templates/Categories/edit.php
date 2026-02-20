<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Category $category
 * @var iterable<\App\Model\Entity\Language> $languages
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Edit Category'));
?>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-tag-fill me-2 text-primary" aria-hidden="true"></i><?= __('Edit Category') ?>
        </h1>
        <p class="mf-muted mb-0"><?= __('Update category details and translations.') ?></p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?= $this->Form->postLink(
            '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
            ['action' => 'delete', $category->id, 'lang' => $lang],
            [
                'confirm' => __('Are you sure you want to delete # {0}?', $category->id),
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
    <div class="mf-admin-card p-4 w-100" style="max-width: 760px;">
        <?= $this->Form->create($category) ?>

        <div class="mb-4">
            <div class="form-check form-switch">
                <?= $this->Form->checkbox('is_active', ['class' => 'form-check-input', 'id' => 'isActive']) ?>
                <label class="form-check-label" for="isActive"><?= __('Active') ?></label>
            </div>
        </div>

        <h5 class="mb-3">
            <i class="bi bi-translate me-2 text-primary" aria-hidden="true"></i><?= __('Translations') ?>
        </h5>

        <?php $translations = $category->category_translations; ?>
        <?php foreach ($languages as $i => $language) : ?>
            <div class="mf-admin-card p-3 mb-3">
                <div class="mb-3 d-flex align-items-center gap-2">
                    <span class="badge bg-primary"><?= h($language->code) ?></span>
                    <span class="fw-semibold"><?= h($language->name) ?></span>
                </div>
                <?= $this->Form->hidden("category_translations.$i.id", ['value' => $translations[$i]->id ?? '']) ?>
                <?= $this->Form->hidden("category_translations.$i.language_id", ['value' => $language->id]) ?>
                <div class="mb-3">
                    <?= $this->Form->control("category_translations.$i.name", [
                        'class' => 'form-control mf-admin-input',
                        'label' => __('Name'),
                        'required' => true,
                        'value' => $translations[$i]->name ?? '',
                    ]) ?>
                </div>
                <div class="mb-0">
                    <?= $this->Form->control("category_translations.$i.description", [
                        'type' => 'textarea',
                        'class' => 'form-control mf-admin-input',
                        'label' => __('Description'),
                        'rows' => 3,
                        'value' => $translations[$i]->description ?? '',
                    ]) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-flex align-items-center gap-2 mt-4">
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
