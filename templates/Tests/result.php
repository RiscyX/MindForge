<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttempt $attempt
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Quiz results'));

$total = (int)($attempt->total_questions ?? 0);
$correct = (int)($attempt->correct_answers ?? 0);
$score = $attempt->score !== null ? (string)$attempt->score : null;
?>

<div class="container py-3 py-lg-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div>
            <h1 class="h3 mb-1 text-white"><?= __('Quiz results') ?></h1>
            <div class="mf-muted"><?= __('Attempt') ?> #<?= h((string)$attempt->id) ?></div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= $this->Html->link(
                __('Back to quizzes'),
                ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-sm btn-outline-light'],
            ) ?>
        </div>
    </div>

    <div class="mf-admin-card p-3 mt-3">
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Correct answers') ?></div>
                <div class="text-white" style="font-size: 1.5rem; font-weight: 700;">
                    <?= h((string)$correct) ?> / <?= h((string)$total) ?>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Score') ?></div>
                <div class="text-white" style="font-size: 1.5rem; font-weight: 700;">
                    <?= $score !== null ? h($score) . '%' : '—' ?>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Finished') ?></div>
                <div class="text-white">
                    <?= $attempt->finished_at ? h($attempt->finished_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap mt-3">
            <?php if ($attempt->test_id !== null) : ?>
                <?= $this->Form->postLink(
                    __('Try again'),
                    ['controller' => 'Tests', 'action' => 'start', $attempt->test_id, 'lang' => $lang],
                    ['class' => 'btn btn-primary'],
                ) ?>
                <?= $this->Html->link(
                    __('Review answers'),
                    ['controller' => 'Tests', 'action' => 'review', $attempt->id, 'lang' => $lang],
                    ['class' => 'btn btn-outline-light'],
                ) ?>
                <?= $this->Html->link(
                    __('View quiz details'),
                    ['controller' => 'Tests', 'action' => 'view', $attempt->test_id, 'lang' => $lang],
                    ['class' => 'btn btn-outline-light'],
                ) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
