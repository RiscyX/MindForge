<?php
/**
 * @var \App\View\AppView $this
 * @var array<int, \App\Model\Entity\TestAttempt>|null $recentAttempts
 */

$this->assign('title', __('Dashboard'));
$lang = (string)$this->request->getParam('lang', 'en');
$recentAttempts = is_array($recentAttempts ?? null) ? $recentAttempts : [];
?>

<div class="py-3 py-lg-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h1 class="h3 mb-1 text-white"><?= __('Dashboard') ?></h1>
            <div class="mf-muted"><?= __('Track your latest quiz activity and jump back to results quickly.') ?></div>
        </div>
    </div>

    <div class="mf-admin-card p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0 text-white"><?= __('Last 5 Attempts') ?></h2>
            <?= $this->Html->link(
                __('Open Quizzes'),
                ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-sm btn-outline-light'],
            ) ?>
        </div>

        <?php if ($recentAttempts) : ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= __('Date') ?></th>
                            <th><?= __('Category') ?></th>
                            <th><?= __('Score') ?></th>
                            <th class="text-end"><?= __('Result') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAttempts as $attempt) : ?>
                            <?php
                            $categoryName = $attempt->hasValue('category') && !empty($attempt->category->category_translations)
                                ? (string)$attempt->category->category_translations[0]->name
                                : __('Uncategorized');
                            $scoreLabel = $attempt->score !== null
                                ? rtrim(rtrim((string)$attempt->score, '0'), '.') . '%'
                                : '—';
                            ?>
                            <tr>
                                <td class="mf-muted">
                                    <?= $attempt->finished_at ? h($attempt->finished_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                                </td>
                                <td class="mf-muted"><?= h($categoryName) ?></td>
                                <td class="mf-muted"><?= h($scoreLabel) ?></td>
                                <td class="text-end">
                                    <?= $this->Html->link(
                                        __('Open result'),
                                        ['controller' => 'Tests', 'action' => 'result', $attempt->id, 'lang' => $lang],
                                        ['class' => 'btn btn-sm btn-primary'],
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="p-3 rounded-3 border border-secondary-subtle">
                <div class="text-white fw-semibold mb-1"><?= __('No attempts yet') ?></div>
                <div class="mf-muted mb-3"><?= __('Start your first quiz and your recent results will appear here.') ?></div>
                <?= $this->Html->link(
                    __('Start test'),
                    ['controller' => 'Tests', 'action' => 'index', 'lang' => $lang],
                    ['class' => 'btn btn-primary'],
                ) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
