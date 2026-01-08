<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Difficulty $difficulty
 */

$lang = $this->request->getParam('lang', 'en');
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-white"><?= __('Add Difficulty') ?></h1>
        <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?= $this->Form->create($difficulty) ?>
    <div class="mb-3">
        <?= $this->Form->control('name', ['class' => 'form-control', 'label' => ['class' => 'form-label text-white']]) ?>
    </div>
    <div class="mb-3">
        <?= $this->Form->control('level', ['class' => 'form-control', 'label' => ['class' => 'form-label text-white']]) ?>
    </div>

    <div class="mt-4">
        <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
