<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Difficulty $difficulty
 */
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-white"><?= __('Add Difficulty') ?></h1>
        <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?= $this->Form->create($difficulty) ?>
    <div class="mb-3">
        <?= $this->Form->control('level', ['class' => 'form-control', 'label' => ['class' => 'form-label text-white']]) ?>
    </div>
    
    <div class="mb-4">
        <h5 class="h5 text-white mb-3"><?= __('Translations') ?></h5>
        <?php 
            $translations = $difficulty->difficulty_translations;
        ?>
        <?php foreach ($languages as $i => $language): ?>
            <div class="mb-4 border-bottom pb-3">
                <h6 class="m-0 font-weight-bold text-primary mb-2"><?= h($language->name) ?> (<?= h($language->code) ?>)</h6>
                <?= $this->Form->hidden("difficulty_translations.$i.language_id", ['value' => $language->id]) ?>
                <div class="mb-3">
                    <?= $this->Form->control("difficulty_translations.$i.name", [
                        'class' => 'form-control', 
                        'label' => ['class' => 'form-label text-white', 'text' => __('Name')],
                        'required' => true,
                        'value' => $translations[$i]->name ?? ''
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
