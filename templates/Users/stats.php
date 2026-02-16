<?php
/**
 * @var \App\View\AppView $this
 * @var int $totalAttempts
 * @var int $finishedAttempts
 * @var int $uniqueQuizzes
 * @var float $avgScore
 * @var float $bestScore
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\TestAttempt> $recentAttempts
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('My Stats'));

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
</div>
