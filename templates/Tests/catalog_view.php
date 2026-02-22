<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Test $test
 * @var int $attemptsCount
 * @var int $finishedCount
 * @var \App\Model\Entity\TestAttempt|null $bestAttempt
 * @var \App\Model\Entity\TestAttempt|null $lastAttempt
 * @var iterable<\App\Model\Entity\TestAttempt> $attemptHistory
 * @var bool|null $isFavorited
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
$attemptHistory = isset($attemptHistory) ? $attemptHistory : [];
$isFavorited = (bool)($isFavorited ?? false);
$isLoggedIn = $this->getRequest()->getAttribute('identity') !== null;

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
            <?php if ($isLoggedIn) : ?>
                <?php if ($isFavorited) : ?>
                    <?= $this->Form->postLink(
                        __('Remove from favorites'),
                        ['controller' => 'Tests', 'action' => 'unfavorite', (string)$test->id, 'lang' => $lang],
                        ['class' => 'btn btn-outline-light'],
                    ) ?>
                <?php else : ?>
                    <?= $this->Form->postLink(
                        __('Save to favorites'),
                        ['controller' => 'Tests', 'action' => 'favorite', (string)$test->id, 'lang' => $lang],
                        ['class' => 'btn btn-outline-light'],
                    ) ?>
                <?php endif; ?>
            <?php endif; ?>

            <?= $this->Form->postLink(
                __('Start quiz'),
                ['controller' => 'Tests', 'action' => 'start', (string)$test->id, 'lang' => $lang],
                ['class' => 'btn btn-primary mf-quiz-card__cta'],
            ) ?>

            <?php if ($bestAttempt) : ?>
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

<?php if (count($attemptHistory) > 0) : ?>
<div class="pb-4">
    <div class="mf-stats-panel">
        <div class="mf-stats-panel__header">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <h2 class="h5 text-white mb-0"><?= __('My Attempts') ?></h2>
                <div class="text-white-50 small"><?= __('Last {0}', 20) ?></div>
            </div>
        </div>
        <div class="mf-stats-panel__body">
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col"><?= __('Status') ?></th>
                                    <th scope="col" class="text-end"><?= __('Score') ?></th>
                                    <th scope="col" class="text-end"><?= __('Correct') ?></th>
                                    <th scope="col" class="text-end"><?= __('Finished') ?></th>
                                    <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attemptHistory as $attempt) : ?>
                            <?php
                            $isFinished = $attempt->finished_at !== null;
                            $finishedLabel = $isFinished ? (string)$attempt->finished_at->i18nFormat('yyyy. MMM d. HH:mm') : '-';
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $isFinished ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' ?>">
                                        <?= $isFinished ? __('Finished') : __('In progress') ?>
                                    </span>
                                </td>
                                <td class="text-end"><?= h($formatScore($attempt->score !== null ? (float)$attempt->score : null)) ?></td>
                                <td class="text-end">
                                    <?= $attempt->correct_answers !== null && $attempt->total_questions !== null
                                        ? h((string)$attempt->correct_answers . '/' . (string)$attempt->total_questions)
                                        : '-' ?>
                                </td>
                                <td class="text-end text-white-50"><?= h($finishedLabel) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 flex-wrap justify-content-end mf-stats-actions">
                                        <?php if ($isFinished) : ?>
                                            <?= $this->Html->link(
                                                __('Review'),
                                                ['controller' => 'Tests', 'action' => 'review', (string)$attempt->id, 'lang' => $lang],
                                                ['class' => 'btn btn-sm btn-primary'],
                                            ) ?>
                                        <?php else : ?>
                                            <?= $this->Html->link(
                                                __('Continue'),
                                                ['controller' => 'Tests', 'action' => 'take', (string)$attempt->id, 'lang' => $lang],
                                                ['class' => 'btn btn-sm btn-outline-light'],
                                            ) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
