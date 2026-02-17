<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TestAttempt $attempt
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Quiz results'));
$this->Html->css('tests_result', ['block' => 'css']);

$total = (int)($attempt->total_questions ?? 0);
$correct = (int)($attempt->correct_answers ?? 0);
$score = $attempt->score !== null ? (string)$attempt->score : null;
$scoreValue = $attempt->score !== null ? (float)$attempt->score : null;
$scoreLabel = $score !== null ? ($score . '%') : '—';

$scoreTone = 'mid';
if ($scoreValue !== null) {
    if ($scoreValue >= 80.0) {
        $scoreTone = 'good';
    } elseif ($scoreValue < 50.0) {
        $scoreTone = 'low';
    }
}

$accuracyLabel = $total > 0 ? (string)round(($correct / $total) * 100) . '%' : '—';
$finishedLabel = $attempt->finished_at ? $attempt->finished_at->i18nFormat('yyyy-MM-dd HH:mm') : '—';
?>

<div class="py-3 py-lg-4">
    <article class="mf-quiz-card mf-quiz-details mf-result-hero" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: 0.16;">
        <div class="mf-quiz-card__cover">
            <div class="mf-quiz-card__cover-inner">
                <div class="mf-quiz-card__icon" aria-hidden="true">
                    <i class="bi bi-award-fill"></i>
                </div>

                <div class="mf-quiz-card__cover-meta">
                    <div class="mf-quiz-card__category"><?= __('Quiz results') ?></div>
                    <div class="mf-result-hero__attempt"><?= __('Attempt') ?> #<?= h((string)$attempt->id) ?></div>
                </div>

                <div class="mf-quiz-card__rightmeta">
                    <span class="mf-result-score mf-result-score--<?= h($scoreTone) ?>">
                        <?= h($scoreLabel) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="mf-quiz-card__content">
            <div class="mf-quiz-details__stats">
                <div class="mf-quiz-stat" title="<?= h(__('Correct answers')) ?>">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h((string)$correct) ?>/<?= h((string)$total) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Correct') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Accuracy')) ?>">
                    <i class="bi bi-percent" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h($accuracyLabel) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Accuracy') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Finished')) ?>">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h((string)$finishedLabel) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Finished') ?></span>
                </div>
            </div>
        </div>

        <div class="mf-quiz-card__actions">
            <?= $this->Html->link(
                __('Back to quizzes'),
                ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-outline-light'],
            ) ?>

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
                    ['controller' => 'Tests', 'action' => 'details', $attempt->test_id, 'lang' => $lang],
                    ['class' => 'btn btn-outline-light'],
                ) ?>
            <?php endif; ?>
        </div>
    </article>

</div>
