<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Question $question
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Question'));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-white"><?= __('Question') ?> #<?= h((string)$question->id) ?></h1>
            <div class="mf-muted"><?= h((string)$question->source_type) ?></div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $question->id, 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $question->id, 'lang' => $lang],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $question->id),
                    'class' => 'btn btn-outline-danger',
                ],
            ) ?>
            <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="mf-admin-card p-3">
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Test') ?></div>
                <div class="text-white">
                    <?php if ($question->hasValue('test')) : ?>
                        <?= $this->Html->link(
                            '#' . h((string)$question->test->id),
                            ['controller' => 'Tests', 'action' => 'view', $question->test->id, 'lang' => $lang],
                            ['class' => 'link-light'],
                        ) ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Category') ?></div>
                <div class="text-white">
                    <?php if ($question->hasValue('category')) : ?>
                        <?= $this->Html->link(
                            '#' . h((string)$question->category->id),
                            ['controller' => 'Categories', 'action' => 'view', $question->category->id, 'lang' => $lang],
                            ['class' => 'link-light'],
                        ) ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Difficulty') ?></div>
                <div class="text-white">
                    <?php if ($question->hasValue('difficulty')) : ?>
                        <?= $this->Html->link(
                            h((string)$question->difficulty->name),
                            ['controller' => 'Difficulties', 'action' => 'view', $question->difficulty->id, 'lang' => $lang],
                            ['class' => 'link-light'],
                        ) ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Is Active') ?></div>
                <div>
                    <?php if ($question->is_active) : ?>
                        <span class="badge bg-success"><?= __('Active') ?></span>
                    <?php else : ?>
                        <span class="badge bg-secondary"><?= __('Inactive') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Position') ?></div>
                <div class="text-white"><?= $question->position === null ? '—' : h((string)$question->position) ?></div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Created') ?></div>
                <div class="text-white"><?= $question->created_at ? h($question->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Updated') ?></div>
                <div class="text-white"><?= $question->updated_at ? h($question->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></div>
            </div>
        </div>
    </div>
</div>
