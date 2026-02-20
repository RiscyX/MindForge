<?php
/**
 * Embedded stats for the logged-in user.
 *
 * Expected variables:
 * @var int $totalAttempts
 * @var int $finishedAttempts
 * @var int $uniqueQuizzes
 * @var float $avgScore
 * @var float $bestScore
 * @var int $last7DaysCount
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttempt> $recentAttempts
 */

$lang = $this->request->getParam('lang', 'en');
$accentAlpha = '0.16';
$showRecentAttempts = isset($showRecentAttempts) ? (bool)$showRecentAttempts : true;
$last7DaysCount = (int)($last7DaysCount ?? 0);

$formatScore = static function (?float $score): string {
    if ($score === null) {
        return '-';
    }

    $s = rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.');

    return $s . '%';
};

?>

<article class="mf-quiz-card mf-quiz-details mf-stats-hero" style="--mf-quiz-accent-rgb: var(--mf-primary-rgb); --mf-quiz-accent-a: <?= h($accentAlpha) ?>;">
    <div class="mf-quiz-card__cover">
        <div class="mf-quiz-card__cover-inner">
            <div class="mf-quiz-card__icon" aria-hidden="true">
                <i class="bi bi-graph-up-arrow"></i>
            </div>

            <div class="mf-quiz-card__cover-meta">
                <div class="mf-quiz-card__category">
                    <?= __('My Stats') ?>
                </div>
            </div>

            <div class="mf-quiz-card__rightmeta">
                <div class="mf-quiz-stat" title="<?= h(__('Quizzes')) ?>">
                    <i class="bi bi-journal-check" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= (int)$uniqueQuizzes ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Quizzes') ?></span>
                </div>
                <div class="mf-quiz-stat" title="<?= h(__('Best Result')) ?>">
                    <i class="bi bi-trophy" aria-hidden="true"></i>
                    <span class="mf-quiz-stat__value"><?= h($formatScore($bestScore)) ?></span>
                    <span class="mf-quiz-stat__label"><?= __('Best Result') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="mf-quiz-card__content">
        <div class="mf-quiz-details__stats">
            <div class="mf-quiz-stat" title="<?= h(__('Attempts')) ?>">
                <i class="bi bi-stack" aria-hidden="true"></i>
                <span class="mf-quiz-stat__value"><?= (int)$totalAttempts ?></span>
                <span class="mf-quiz-stat__label"><?= __('Attempts') ?></span>
            </div>
            <div class="mf-quiz-stat" title="<?= h(__('Finished')) ?>">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                <span class="mf-quiz-stat__value"><?= (int)$finishedAttempts ?></span>
                <span class="mf-quiz-stat__label"><?= __('Finished') ?></span>
            </div>
            <div class="mf-quiz-stat" title="<?= h(__('Avg: {0}', $formatScore($avgScore))) ?>">
                <i class="bi bi-percent" aria-hidden="true"></i>
                <span class="mf-quiz-stat__value"><?= h($formatScore($avgScore)) ?></span>
                <span class="mf-quiz-stat__label"><?= __('Avg') ?></span>
            </div>
            <div class="mf-quiz-stat" title="<?= h(__('Last 7 days')) ?>">
                <i class="bi bi-calendar-week" aria-hidden="true"></i>
                <span class="mf-quiz-stat__value"><?= (int)$last7DaysCount ?></span>
                <span class="mf-quiz-stat__label"><?= __('Last 7d') ?></span>
            </div>
        </div>
    </div>
</article>

<?php if ($showRecentAttempts) : ?>
<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="mf-stats-panel h-100">
            <div class="mf-stats-panel__header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <h2 class="h5 text-white mb-0"><?= __('Recent Attempts') ?></h2>
                    <div class="text-white-50 small"><?= __('Last {0}', 20) ?></div>
                </div>
            </div>
            <div class="mf-stats-panel__body">
                <?php if ($recentAttempts->count() === 0) : ?>
                    <div class="mf-muted">
                        <?= __('No finished attempts yet.') ?>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col"><?= __('Quiz') ?></th>
                                    <th scope="col" class="text-end"><?= __('Score') ?></th>
                                    <th scope="col" class="text-end"><?= __('Correct') ?></th>
                                    <th scope="col" class="text-end"><?= __('Finished') ?></th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAttempts as $attempt) : ?>
                                    <?php
                                    $test = $attempt->test;
                                    $tt = $test ? ($test->test_translations[0] ?? null) : null;
                                    $title = $tt?->title ?? ($test ? '#' . (int)$test->id : __('Unknown'));
                                    $finished = $attempt->finished_at ? $attempt->finished_at->i18nFormat('yyyy. MMM d. HH:mm') : '-';

                                    $resultUrl = ['controller' => 'Tests', 'action' => 'result', (string)$attempt->id, 'lang' => $lang];
                                    $reviewUrl = ['controller' => 'Tests', 'action' => 'review', (string)$attempt->id, 'lang' => $lang];
                                    ?>
                                    <tr>
                                        <td class="text-truncate" style="max-width: 260px;">
                                            <?= h($title) ?>
                                        </td>
                                        <td class="text-end"><?= h($formatScore($attempt->score !== null ? (float)$attempt->score : null)) ?></td>
                                        <td class="text-end">
                                            <?= $attempt->correct_answers !== null && $attempt->total_questions !== null
                                                ? h((string)$attempt->correct_answers . '/' . (string)$attempt->total_questions)
                                                : '-' ?>
                                        </td>
                                        <td class="text-end text-white-50"><?= h($finished) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2 flex-wrap justify-content-end mf-stats-actions">
                                                <?= $this->Html->link(
                                                    __('Result'),
                                                    $resultUrl,
                                                    ['class' => 'btn btn-sm btn-primary'],
                                                ) ?>
                                                <?= $this->Html->link(
                                                    __('Review'),
                                                    $reviewUrl,
                                                    ['class' => 'btn btn-sm btn-outline-light'],
                                                ) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
