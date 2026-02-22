<?php
/**
 * @var \App\View\AppView $this
 * @var int $totalQuizzes
 * @var int $publicQuizzes
 * @var int $totalAttempts
 * @var int $finishedAttempts
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Quiz Creator'));
?>

<div class="py-3 py-lg-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h1 class="h3 mb-1 text-white"><?= __('Quiz Creator') ?></h1>
            <div class="mf-muted"><?= __('Create quizzes and track their performance.') ?></div>
        </div>
        <div>
            <?= $this->Html->link(
                __('Create Quiz'),
                ['prefix' => 'QuizCreator', 'controller' => 'Tests', 'action' => 'add', 'lang' => $lang],
                ['class' => 'btn btn-primary'],
            ) ?>
        </div>
    </div>

    <div class="mf-quiz-grid mt-2">
        <article class="mf-quiz-card" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: 0.18;">
            <div class="mf-quiz-card__cover">
                <div class="mf-quiz-card__cover-inner">
                    <div class="mf-quiz-card__icon" aria-hidden="true">
                        <i class="bi bi-journal-richtext"></i>
                    </div>

                    <div class="mf-quiz-card__cover-meta">
                        <div class="mf-quiz-card__category"><?= __('My Quizzes') ?></div>
                    </div>

                    <div class="mf-quiz-card__rightmeta">
                        <div class="mf-quiz-stat" title="<?= h(__('Quizzes')) ?>">
                            <i class="bi bi-collection" aria-hidden="true"></i>
                            <span class="mf-quiz-stat__value"><?= (int)$totalQuizzes ?></span>
                            <span class="mf-quiz-stat__label"><?= __('Quizzes') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mf-quiz-card__content">
                <div class="mf-quiz-details__stats">
                    <div class="mf-quiz-stat" title="<?= h(__('Public quizzes')) ?>">
                        <i class="bi bi-globe2" aria-hidden="true"></i>
                        <span class="mf-quiz-stat__value"><?= (int)$publicQuizzes ?></span>
                        <span class="mf-quiz-stat__label"><?= __('Public') ?></span>
                    </div>
                    <div class="mf-quiz-stat" title="<?= h(__('Attempts')) ?>">
                        <i class="bi bi-stack" aria-hidden="true"></i>
                        <span class="mf-quiz-stat__value"><?= (int)$totalAttempts ?></span>
                        <span class="mf-quiz-stat__label"><?= __('Attempts') ?></span>
                    </div>
                    <div class="mf-quiz-stat" title="<?= h(__('Finished attempts')) ?>">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        <span class="mf-quiz-stat__value"><?= (int)$finishedAttempts ?></span>
                        <span class="mf-quiz-stat__label"><?= __('Finished') ?></span>
                    </div>
                </div>
            </div>

            <div class="mf-quiz-card__actions">
                <?= $this->Html->link(
                    __('Open Quizzes'),
                    ['prefix' => 'QuizCreator', 'controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                    ['class' => 'btn btn-primary mf-quiz-card__cta'],
                ) ?>
            </div>
        </article>

        <div class="mf-admin-card p-3">
            <div class="fw-semibold mb-2"><?= __('Quick Notes') ?></div>
            <div class="mf-muted mb-2"><?= __('Only your own quizzes are shown in the list.') ?></div>
            <div class="mf-muted"><?= __('Open a quiz to see detailed stats for that specific quiz.') ?></div>
        </div>
    </div>
</div>
