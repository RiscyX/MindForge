<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Difficulty $difficulty
 */

$lang = $this->request->getParam('lang', 'en');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-white"><?= __('Edit Difficulty') ?></h1>
        <div>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $difficulty->id, 'lang' => $lang],
                ['confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id), 'class' => 'btn btn-danger me-2']
            ) ?>
            <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <?= $this->Form->create($difficulty) ?>
    <div class="mb-3">
        <?= $this->Form->control('level', ['class' => 'form-control', 'label' => ['class' => 'form-label text-white']]) ?>
    </div>

    <h4 class="mt-4 text-white"><?= __('Translations') ?></h4>
    <div class="row">
        <?php foreach ($languages as $i => $language): ?>
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
        <div class="col-md-6 mb-3">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h6 class="card-title text-light"><?= h($language->name) ?></h6>
                    <?= $this->Form->hidden("difficulty_translations.$i.language_id", ['value' => $language->id]) ?>
                    <?php if ($existing): ?>
                        <?= $this->Form->hidden("difficulty_translations.$i.id", ['value' => $existing->id]) ?>
                        <?= $this->Form->control("difficulty_translations.$i.name", [
                            'class' => 'form-control', 
                            'label' => ['class' => 'form-label text-secondary', 'text' => __('Name')],
                            'value' => $existing->name
                        ]) ?>
                    <?php else: ?>
                        <?= $this->Form->control("difficulty_translations.$i.name", [
                            'class' => 'form-control', 
                            'label' => ['class' => 'form-label text-secondary', 'text' => __('Name')]
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
