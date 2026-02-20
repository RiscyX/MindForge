<?php
/**
 * @var \App\View\AppView $this
 * @var int $totalAttempts
 * @var int $finishedAttempts
 * @var int $uniqueQuizzes
 * @var float $avgScore
 * @var float $bestScore
 * @var int $last7DaysCount
 * @var array<int, array{category_id:int,name:string,attempts:int,avg_score:float|null,best_score:float|null}> $categoryBreakdown
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttempt> $recentAttempts
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('My Stats'));

$formatScore = static function (?float $score): string {
    if ($score === null) {
        return '-';
    }
    $s = rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.');
    return $s . '%';
};

$categoryBreakdown = $categoryBreakdown ?? [];
$maxAttempts = 0;
foreach ($categoryBreakdown as $row) {
    if ($row['attempts'] > $maxAttempts) {
        $maxAttempts = $row['attempts'];
    }
}
?>

<div class="py-3 py-lg-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h1 class="h3 mb-1 text-white"><?= __('My Stats') ?></h1>
            <div class="mf-muted"><?= __('Your quiz history and performance overview.') ?></div>
        </div>

        <div class="d-flex gap-2">
            <?= $this->Html->link(
                __('Back to Profile'),
                ['controller' => 'Users', 'action' => 'profile', 'lang' => $lang],
                ['class' => 'btn btn-outline-light rounded-pill'],
            ) ?>
        </div>
    </div>

    <?= $this->element('users/stats') ?>

    <?php if ($categoryBreakdown) : ?>
    <div class="mt-4">
        <div class="mf-stats-panel">
            <div class="mf-stats-panel__header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <h2 class="h5 text-white mb-0">
                        <i class="bi bi-grid-3x3-gap-fill me-2" aria-hidden="true"></i>
                        <?= __('Category Breakdown') ?>
                    </h2>
                    <div class="text-white-50 small"><?= __('Based on all finished attempts') ?></div>
                </div>
            </div>
            <div class="mf-stats-panel__body">
                <div class="table-responsive">
                    <table class="table table-dark align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="min-width:160px;"><?= __('Category') ?></th>
                                <th scope="col" class="text-end" style="white-space:nowrap;"><?= __('Attempts') ?></th>
                                <th scope="col" style="min-width:130px;"><?= __('Avg Score') ?></th>
                                <th scope="col" class="text-end" style="white-space:nowrap;"><?= __('Best Score') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryBreakdown as $row) :
                                $avgPct = $row['avg_score'] !== null ? (float)$row['avg_score'] : null;
                                $barWidth = $avgPct !== null ? max(4, (int)round($avgPct)) : 0;
                                $barClass = $avgPct === null ? '' : ($avgPct >= 80 ? 'bg-success' : ($avgPct >= 50 ? 'bg-warning' : 'bg-danger'));
                            ?>
                            <tr>
                                <td class="text-white fw-semibold text-truncate" style="max-width:200px;">
                                    <?= h((string)$row['name']) ?>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-secondary rounded-pill"><?= (int)$row['attempts'] ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($avgPct !== null) : ?>
                                            <div class="flex-grow-1" style="min-width:60px;">
                                                <div class="progress" style="height:6px;background:rgba(255,255,255,0.08);">
                                                    <div class="progress-bar <?= $barClass ?>"
                                                         role="progressbar"
                                                         style="width:<?= $barWidth ?>%;"
                                                         aria-valuenow="<?= $barWidth ?>"
                                                         aria-valuemin="0"
                                                         aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                            <span class="text-white-75 small fw-semibold" style="min-width:46px;text-align:right;"><?= h($formatScore($avgPct)) ?></span>
                                        <?php else : ?>
                                            <span class="mf-muted small">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <?php if ($row['best_score'] !== null) : ?>
                                        <span class="fw-semibold text-white"><?= h($formatScore((float)$row['best_score'])) ?></span>
                                    <?php else : ?>
                                        <span class="mf-muted">—</span>
                                    <?php endif; ?>
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
</div>
