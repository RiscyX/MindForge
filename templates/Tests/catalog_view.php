<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Test $test
 * @var int $attemptsCount
 * @var int $finishedCount
 * @var \App\Model\Entity\TestAttempt|null $bestAttempt
 * @var \App\Model\Entity\TestAttempt|null $lastAttempt
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Quiz'));

$title = '';
$category = '';
$difficulty = '';
$questionsCount = $test->number_of_questions !== null ? (int)$test->number_of_questions : null;
if (!empty($test->test_translations)) {
    $title = (string)($test->test_translations[0]->title ?? '');
}

if ($test->hasValue('category') && !empty($test->category->category_translations)) {
    $category = (string)($test->category->category_translations[0]->name ?? '');
}
if ($test->hasValue('difficulty') && !empty($test->difficulty->difficulty_translations)) {
    $difficulty = (string)($test->difficulty->difficulty_translations[0]->name ?? '');
}

$difficultyClass = 'mf-quiz-diff--default';

// Keep accents strictly within the app's base palette.
// We only vary intensity per card to avoid "samey" repetition.
$variant = ((int)$test->id) % 3;
$accentAlpha = match ($variant) {
    0 => '0.22',
    1 => '0.16',
    default => '0.12',
};

$formatScore = static function (?float $score): string {
    if ($score === null) {
        return '-';
    }

    $s = rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.');

    return $s . '%';
};

$bestFinished = $bestAttempt?->finished_at ? $bestAttempt->finished_at->i18nFormat('yyyy. MMM d. HH:mm') : '-';
$lastFinished = $lastAttempt?->finished_at ? $lastAttempt->finished_at->i18nFormat('yyyy. MMM d. HH:mm') : '-';

?>

<div class="py-3 py-lg-4">
    <article class="mf-quiz-card mf-quiz-details" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: <?= h($accentAlpha) ?>;">
        <div class="mf-quiz-card__cover">
            <div class="mf-quiz-card__cover-inner">
                <div class="mf-quiz-card__icon" aria-hidden="true">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>

                <div class="mf-quiz-card__cover-meta">
                    <?php if ($category !== '') : ?>
                        <div class="mf-quiz-card__category" title="<?= h($category) ?>">
                            <?= h($category) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mf-quiz-card__rightmeta">
                    <?php if ($difficulty !== '') : ?>
                        <span class="mf-quiz-diff <?= h($difficultyClass) ?>" title="<?= h($difficulty) ?>">
                            <span class="mf-quiz-diff__dot" aria-hidden="true"></span>
                            <span class="mf-quiz-diff__text"><?= h($difficulty) ?></span>
                        </span>
                    <?php endif; ?>
                    <?php if ($questionsCount !== null) : ?>
                        <div class="mf-quiz-stat" title="<?= h(__('Questions')) ?>">
                            <i class="bi bi-list-ol" aria-hidden="true"></i>
                            <span class="mf-quiz-stat__value"><?= (int)$questionsCount ?></span>
                            <span class="mf-quiz-stat__label"><?= __('questions') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mf-quiz-card__content">
            <div class="mf-quiz-details__kicker"><?= __('My Stats') ?></div>
            <div class="mf-quiz-card__title">
                <?= $title !== '' ? h($title) : __('Untitled quiz') ?>
            </div>

            <div class="mf-quiz-details__stats">
                <div class="mf-quiz-stat" title="<?= h(__('Attempts')) ?>">
                    <i class="bi bi-stack" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= (int)$attemptsCount ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Attempts') ?></span>
                </div>

                <div class="mf-quiz-stat" title="<?= h(__('Finished')) ?>">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= (int)$finishedCount ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Finished') ?></span>
                </div>

                <div class="mf-quiz-stat" title="<?= h(__('Best Result')) ?>">
                    <i class="bi bi-trophy" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h($formatScore($bestAttempt?->score !== null ? (float)$bestAttempt->score : null)) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Best Result') ?></span>
                </div>
            </div>

            <div class="mf-quiz-details__submeta">
                <span class="text-white-50 small">
                    <?= __('Finished') ?>: <?= h($bestFinished) ?>
                </span>
                <span class="text-white-50 small">
                    <?= __('Correct') ?>:
                    <?php if ($bestAttempt && $bestAttempt->correct_answers !== null && $bestAttempt->total_questions !== null) : ?>
                        <?= h((string)$bestAttempt->correct_answers . '/' . (string)$bestAttempt->total_questions) ?>
                    <?php else : ?>
                        -
                    <?php endif; ?>
                </span>
                <?php if ($lastAttempt && (!$bestAttempt || (int)$lastAttempt->id !== (int)$bestAttempt->id)) : ?>
                    <span class="text-white-50 small">
                        <?= __('Last {0}', __('Finished')) ?>: <?= h($lastFinished) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="mf-quiz-card__actions">
            <?= $this->Form->postLink(
                __('Start quiz'),
                ['controller' => 'Tests', 'action' => 'start', (string)$test->id, 'lang' => $lang],
                ['class' => 'btn btn-primary mf-quiz-card__cta'],
            ) ?>

            <?php if ($bestAttempt) : ?>
                <?= $this->Html->link(
                    __('Result'),
                    ['controller' => 'Tests', 'action' => 'result', (string)$bestAttempt->id, 'lang' => $lang],
                    ['class' => 'btn btn-outline-light'],
                ) ?>
                <?= $this->Html->link(
                    __('Review'),
                    ['controller' => 'Tests', 'action' => 'review', (string)$bestAttempt->id, 'lang' => $lang],
                    ['class' => 'btn btn-outline-light'],
                ) ?>
            <?php endif; ?>

            <?= $this->Html->link(
                __('Back to Quizzes'),
                ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-outline-light mf-quiz-card__secondary'],
            ) ?>
        </div>
    </article>
</div>
