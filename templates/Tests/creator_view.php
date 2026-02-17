<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Test $test
 * @var array<string, float|int> $stats
 */

$lang = $this->request->getParam('lang', 'en');
$isAdminPrefix = (string)$this->request->getParam('prefix', '') === 'Admin';
$this->assign('title', __('Quiz stats'));

$title = '';
$category = '';
$difficulty = '';
$questionsCount = $test->number_of_questions !== null ? (int)$test->number_of_questions : 0;
if (!empty($test->test_translations)) {
    $title = (string)($test->test_translations[0]->title ?? '');
}

if ($test->hasValue('category') && !empty($test->category->category_translations)) {
    $category = (string)($test->category->category_translations[0]->name ?? '');
}

if ($test->hasValue('difficulty') && !empty($test->difficulty->difficulty_translations)) {
    $difficulty = (string)($test->difficulty->difficulty_translations[0]->name ?? '');
}

$formatPercent = static function (float $value): string {
    $v = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

    return $v . '%';
};

$accentAlpha = '0.18';
?>

<div class="py-3 py-lg-4">
    <article class="mf-quiz-card mf-quiz-details" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: <?= h($accentAlpha) ?>;">
        <div class="mf-quiz-card__cover">
            <div class="mf-quiz-card__cover-inner">
                <div class="mf-quiz-card__icon" aria-hidden="true">
                    <i class="bi bi-bar-chart-line-fill"></i>
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
                        <div class="mf-quiz-stat" title="<?= h(__('Difficulty')) ?>">
                            <i class="bi bi-speedometer2" aria-hidden="true"></i>
                            <span class="mf-quiz-stat__value"><?= h($difficulty) ?></span>
                            <span class="mf-quiz-stat__label"><?= __('Difficulty') ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="mf-quiz-stat" title="<?= h(__('Questions')) ?>">
                        <i class="bi bi-list-ol" aria-hidden="true"></i>
                        <span class="mf-quiz-stat__value"><?= (int)$questionsCount ?></span>
                        <span class="mf-quiz-stat__label"><?= __('Questions') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mf-quiz-card__content">
            <div class="mf-quiz-details__kicker"><?= __('Quiz Statistics') ?></div>
            <div class="mf-quiz-card__title"><?= $title !== '' ? h($title) : __('Untitled quiz') ?></div>

            <div class="mf-quiz-details__stats">
                <div class="mf-quiz-stat" title="<?= h(__('Attempts')) ?>">
                    <i class="bi bi-stack" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= (int)($stats['attempts'] ?? 0) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Attempts') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Finished')) ?>">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= (int)($stats['finished'] ?? 0) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Finished') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Completion Rate')) ?>">
                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h($formatPercent((float)($stats['completionRate'] ?? 0.0))) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Completion') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Average Score')) ?>">
                    <i class="bi bi-percent" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h($formatPercent((float)($stats['avgScore'] ?? 0.0))) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Avg Score') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Best Score')) ?>">
                    <i class="bi bi-trophy" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h($formatPercent((float)($stats['bestScore'] ?? 0.0))) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Best Score') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Average Correct Rate')) ?>">
                    <i class="bi bi-bullseye" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h($formatPercent((float)($stats['avgCorrectRate'] ?? 0.0))) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Correct Rate') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Unique Users')) ?>">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= (int)($stats['uniqueUsers'] ?? 0) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Unique Users') ?></span>
                </div>
            </div>
        </div>

        <div class="mf-quiz-card__actions">
            <?= $this->Html->link(
                $isAdminPrefix ? __('Back to Tests') : __('Back to My Quizzes'),
                ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-outline-light mf-quiz-card__secondary'],
            ) ?>
            <?= $this->Html->link(
                __('Edit Quiz'),
                ['controller' => 'Tests', 'action' => 'edit', (string)$test->id, 'lang' => $lang],
                ['class' => 'btn btn-primary mf-quiz-card__cta'],
            ) ?>
        </div>
    </article>
</div>
