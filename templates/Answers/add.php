<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Answer $answer
 * @var \Cake\Collection\CollectionInterface|string[] $questions
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Add Answer'));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-white"><?= __('Add Answer') ?></h1>
        <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
    </div>

    <div class="mf-admin-card p-3">
        <?= $this->Form->create($answer) ?>

        <div class="row g-3">
            <div class="col-12">
                <?= $this->Form->control('question_id', [
                    'options' => $questions,
                    'class' => 'form-select',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Question')],
                ]) ?>
            </div>

            <div class="col-12 col-lg-6">
                <?= $this->Form->control('source_type', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Source Type')],
                ]) ?>
            </div>

            <div class="col-12 col-lg-6 d-flex align-items-end">
                <div class="form-check form-switch">
                    <?= $this->Form->checkbox('is_correct', ['class' => 'form-check-input', 'id' => 'isCorrect']) ?>
                    <label class="form-check-label text-white" for="isCorrect"><?= __('Is Correct') ?></label>
                </div>
            </div>

            <div class="col-12">
                <?= $this->Form->control('source_text', [
                    'type' => 'textarea',
                    'rows' => 4,
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Source Text')],
                ]) ?>
            </div>

            <div class="col-12 col-lg-4">
                <?= $this->Form->control('position', [
                    'class' => 'form-control',
                    'label' => ['class' => 'form-label text-white', 'text' => __('Position')],
                    'empty' => true,
                ]) ?>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
