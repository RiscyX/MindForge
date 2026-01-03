<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Category $category
 */
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-white"><?= __('Add Category') ?></h1>
        <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?= $this->Form->create($category) ?>
    <div class="mb-3">
        <div class="form-check form-switch">
            <?= $this->Form->checkbox('is_active', ['class' => 'form-check-input', 'id' => 'isActive']) ?>
            <label class="form-check-label text-white" for="isActive"><?= __('Active') ?></label>
        </div>
    </div>
    
    <div class="mb-4">
        <h5 class="h5 text-white mb-3"><?= __('Translations') ?></h5>
        <?php 
            // Let's iterate languages and assume the translation at same index matches
            // because we built it that way.
            $translations = $category->category_translations;
        ?>
        <?php foreach ($languages as $i => $language): ?>
            <div class="mb-4 border-bottom pb-3">
                <h6 class="m-0 font-weight-bold text-primary mb-2"><?= h($language->name) ?> (<?= h($language->code) ?>)</h6>
                <?= $this->Form->hidden("category_translations.$i.language_id", ['value' => $language->id]) ?>
                <div class="mb-3">
                    <?= $this->Form->control("category_translations.$i.name", [
                        'class' => 'form-control', 
                        'label' => ['class' => 'form-label text-white', 'text' => __('Name')],
                        'required' => true,
                        'value' => $translations[$i]->name ?? ''
                    ]) ?>
                </div>
                <div class="mb-3">
                    <?= $this->Form->control("category_translations.$i.description", [
                        'type' => 'textarea',
                        'class' => 'form-control', 
                        'label' => ['class' => 'form-label text-white', 'text' => __('Description')],
                        'value' => $translations[$i]->description ?? ''
                    ]) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
