<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Difficulty $difficulty
 * @var iterable<\App\Model\Entity\Language> $languages
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Edit Difficulty'));
?>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
    <div>
        <h1 class="h3 mb-1">
            <i class="bi bi-bar-chart-steps me-2 text-primary" aria-hidden="true"></i><?= __('Edit Difficulty') ?>
        </h1>
        <p class="mf-muted mb-0"><?= __('Update difficulty level and translations.') ?></p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?= $this->Form->postLink(
            '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
            ['action' => 'delete', $difficulty->id, 'lang' => $lang],
            [
                'confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id),
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
                <?php
                    $existing = null;
                    if (!empty($difficulty->difficulty_translations)) {
                        foreach ($difficulty->difficulty_translations as $trans) {
                            if ($trans->language_id === $language->id) {
                                $existing = $trans;
                                break;
                            }
                        }
                    }
                ?>
                <div class="col-12 col-md-6">
                    <div class="mf-admin-card p-3 h-100">
                        <div class="mb-3 d-flex align-items-center gap-2">
                            <span class="badge bg-primary"><?= h($language->code) ?></span>
                            <span class="fw-semibold"><?= h($language->name) ?></span>
                        </div>
                        <?= $this->Form->hidden("difficulty_translations.$i.language_id", ['value' => $language->id]) ?>
                        <?php if ($existing) : ?>
                            <?= $this->Form->hidden("difficulty_translations.$i.id", ['value' => $existing->id]) ?>
                            <?= $this->Form->control("difficulty_translations.$i.name", [
                                'class' => 'form-control mf-admin-input',
                                'label' => __('Name'),
                                'value' => $existing->name,
                            ]) ?>
                        <?php else : ?>
                            <?= $this->Form->control("difficulty_translations.$i.name", [
                                'class' => 'form-control mf-admin-input',
                                'label' => __('Name'),
                            ]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

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
