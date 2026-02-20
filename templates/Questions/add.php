<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Question $question
 * @var \Cake\Collection\CollectionInterface|string[] $tests
 * @var \Cake\Collection\CollectionInterface|string[] $categories
 * @var \Cake\Collection\CollectionInterface|string[] $difficulties
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Add Question'));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-white"><?= __('Add Question') ?></h1>
        <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
    </div>

    <div class="mf-admin-card p-3">
        <?= $this->Form->create($question) ?>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <?= $this->Form->control('test_id', [
                    'options' => $tests,
                    'empty' => true,
                    'class' => 'form-select',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Test')],
                ]) ?>
            </div>

            <div class="col-12 col-lg-6">
                <?= $this->Form->control('category_id', [
                    'options' => $categories,
                    'class' => 'form-select',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Category')],
                ]) ?>
            </div>

            <div class="col-12 col-lg-6">
                <?= $this->Form->control('difficulty_id', [
                    'options' => $difficulties,
                    'empty' => true,
                    'class' => 'form-select',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Difficulty')],
                ]) ?>
            </div>

            <div class="col-12 col-lg-6">
                <?= $this->Form->control('source_type', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Source Type')],
                ]) ?>
            </div>

            <div class="col-12 col-lg-4">
                <?= $this->Form->control('created_by', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Created By')],
                    'empty' => true,
                ]) ?>
            </div>

            <div class="col-12 col-lg-4">
                <?= $this->Form->control('position', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Position')],
                    'empty' => true,
                ]) ?>
            </div>

            <div class="col-12 col-lg-4 d-flex align-items-end">
                <div class="form-check form-switch">
                    <?= $this->Form->checkbox('is_active', ['class' => 'form-check-input', 'id' => 'isActive']) ?>
                    <label class="form-check-label text-white" for="isActive"><?= __('Is Active') ?></label>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary mf-admin-btn', 'data-loading-text' => __('Submitting…')]) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
