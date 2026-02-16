<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Question $question
 * @var string[]|\Cake\Collection\CollectionInterface $tests
 * @var string[]|\Cake\Collection\CollectionInterface $categories
 * @var string[]|\Cake\Collection\CollectionInterface $difficulties
 */

$lang = $this->request->getParam('lang', 'en');
$answerRows = is_array($question->answers ?? null) ? $question->answers : [];
if (!$answerRows) {
    $answerRows = [(object)[
        'id' => null,
        'source_type' => 'human',
        'source_text' => '',
        'position' => null,
        'is_correct' => false,
    ]];
}

$this->assign('title', __('Edit Question'));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0 text-white"><?= __('Edit Question') ?></h1>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $question->id, 'lang' => $lang],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $question->id),
                    'class' => 'btn btn-danger',
                ],
            ) ?>
            <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
        </div>
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
                    'options' => ['human' => __('Human'), 'ai' => __('AI')],
                    'class' => 'form-select',
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

        <hr class="my-4">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1 text-white"><?= __('Answers') ?></h2>
                <p class="mb-0 mf-muted"><?= __('At least one answer must be marked as correct.') ?></p>
            </div>
            <button type="button" class="btn btn-outline-light" id="addAnswerRow"><?= __('Add Answer') ?></button>
        </div>

        <?php if ($question->getError('answers')) : ?>
            <div class="alert alert-danger" role="alert">
                <?= h((string)current((array)$question->getError('answers'))) ?>
            </div>
        <?php endif; ?>

        <div id="answersEditor" class="d-flex flex-column gap-3">
            <?php foreach ($answerRows as $index => $answer) : ?>
                <div class="border border-secondary-subtle rounded p-3 answer-row" data-index="<?= (int)$index ?>">
                    <input type="hidden" name="answers[<?= (int)$index ?>][id]" value="<?= h((string)($answer->id ?? '')) ?>">

                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-4">
                            <label class="form-label text-white" for="answer-source-type-<?= (int)$index ?>"><?= __('Source Type') ?></label>
                            <select
                                id="answer-source-type-<?= (int)$index ?>"
                                class="form-select"
                                name="answers[<?= (int)$index ?>][source_type]"
                            >
                                <option value="human" <?= ($answer->source_type ?? 'human') === 'human' ? 'selected' : '' ?>><?= __('Human') ?></option>
                                <option value="ai" <?= ($answer->source_type ?? '') === 'ai' ? 'selected' : '' ?>><?= __('AI') ?></option>
                            </select>
                        </div>

                        <div class="col-12 col-lg-2">
                            <label class="form-label text-white" for="answer-position-<?= (int)$index ?>"><?= __('Position') ?></label>
                            <input
                                type="number"
                                min="0"
                                step="1"
                                id="answer-position-<?= (int)$index ?>"
                                class="form-control"
                                name="answers[<?= (int)$index ?>][position]"
                                value="<?= h((string)($answer->position ?? '')) ?>"
                            >
                        </div>

                        <div class="col-12 col-lg-2">
                            <div class="form-check form-switch mt-4">
                                <input type="hidden" name="answers[<?= (int)$index ?>][is_correct]" value="0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="answer-correct-<?= (int)$index ?>"
                                    name="answers[<?= (int)$index ?>][is_correct]"
                                    value="1"
                                    <?= !empty($answer->is_correct) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label text-white" for="answer-correct-<?= (int)$index ?>">
                                    <?= __('Correct') ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-12 col-lg-4 text-lg-end">
                            <button type="button" class="btn btn-outline-danger remove-answer-row"><?= __('Remove') ?></button>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-white" for="answer-source-text-<?= (int)$index ?>"><?= __('Answer Text') ?></label>
                            <textarea
                                id="answer-source-text-<?= (int)$index ?>"
                                class="form-control"
                                name="answers[<?= (int)$index ?>][source_text]"
                                rows="2"
                            ><?= h((string)($answer->source_text ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4 d-flex gap-2">
            <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<template id="answerRowTemplate">
    <div class="border border-secondary-subtle rounded p-3 answer-row" data-index="__INDEX__">
        <input type="hidden" name="answers[__INDEX__][id]" value="">

        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label class="form-label text-white" for="answer-source-type-__INDEX__"><?= __('Source Type') ?></label>
                <select
                    id="answer-source-type-__INDEX__"
                    class="form-select"
                    name="answers[__INDEX__][source_type]"
                >
                    <option value="human" selected><?= __('Human') ?></option>
                    <option value="ai"><?= __('AI') ?></option>
                </select>
            </div>

            <div class="col-12 col-lg-2">
                <label class="form-label text-white" for="answer-position-__INDEX__"><?= __('Position') ?></label>
                <input
                    type="number"
                    min="0"
                    step="1"
                    id="answer-position-__INDEX__"
                    class="form-control"
                    name="answers[__INDEX__][position]"
                    value=""
                >
            </div>

            <div class="col-12 col-lg-2">
                <div class="form-check form-switch mt-4">
                    <input type="hidden" name="answers[__INDEX__][is_correct]" value="0">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="answer-correct-__INDEX__"
                        name="answers[__INDEX__][is_correct]"
                        value="1"
                    >
                    <label class="form-check-label text-white" for="answer-correct-__INDEX__">
                        <?= __('Correct') ?>
                    </label>
                </div>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <button type="button" class="btn btn-outline-danger remove-answer-row"><?= __('Remove') ?></button>
            </div>

            <div class="col-12">
                <label class="form-label text-white" for="answer-source-text-__INDEX__"><?= __('Answer Text') ?></label>
                <textarea
                    id="answer-source-text-__INDEX__"
                    class="form-control"
                    name="answers[__INDEX__][source_text]"
                    rows="2"
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script>
(() => {
    const container = document.getElementById('answersEditor');
    const template = document.getElementById('answerRowTemplate');
    const addButton = document.getElementById('addAnswerRow');
    if (!container || !template || !addButton) {
        return;
    }

    let index = container.querySelectorAll('.answer-row').length;

    const updateRemoveButtons = () => {
        const rows = container.querySelectorAll('.answer-row');
        rows.forEach((row) => {
            const button = row.querySelector('.remove-answer-row');
            if (button) {
                button.disabled = rows.length <= 1;
            }
        });
    };

    addButton.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        container.insertAdjacentHTML('beforeend', html);
        index += 1;
        updateRemoveButtons();
    });

    container.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement) || !target.classList.contains('remove-answer-row')) {
            return;
        }

        const row = target.closest('.answer-row');
        if (!row) {
            return;
        }

        if (container.querySelectorAll('.answer-row').length <= 1) {
            return;
        }

        row.remove();
        updateRemoveButtons();
    });

    updateRemoveButtons();
})();
</script>
