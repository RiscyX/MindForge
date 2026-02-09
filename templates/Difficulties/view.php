<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Difficulty $difficulty
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Difficulty'));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-white"><?= __('Difficulty') ?> #<?= h((string)$difficulty->id) ?></h1>
            <div class="mf-muted"><?= h((string)$difficulty->name) ?></div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $difficulty->id, 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $difficulty->id, 'lang' => $lang],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id),
                    'class' => 'btn btn-outline-danger',
                ],
            ) ?>
            <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="mf-admin-card p-3">
        <div class="row g-3">
            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Name') ?></div>
                <div class="text-white"><?= h((string)$difficulty->name) ?></div>
            </div>
            <div class="col-12 col-lg-2">
                <div class="mf-muted mb-1"><?= __('Level') ?></div>
                <div class="text-white"><?= h((string)$difficulty->level) ?></div>
            </div>
            <div class="col-12 col-lg-2">
                <div class="mf-muted mb-1"><?= __('Questions') ?></div>
                <div class="text-white"><?= h((string)count($difficulty->questions ?? [])) ?></div>
            </div>
            <div class="col-12 col-lg-2">
                <div class="mf-muted mb-1"><?= __('Tests') ?></div>
                <div class="text-white"><?= h((string)count($difficulty->tests ?? [])) ?></div>
            </div>
            <div class="col-12 col-lg-2">
                <div class="mf-muted mb-1"><?= __('Test Attempts') ?></div>
                <div class="text-white"><?= h((string)count($difficulty->test_attempts ?? [])) ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($difficulty->tests)) : ?>
        <div class="mf-admin-card p-3 mt-3">
            <h2 class="h5 mb-3 text-white"><?= __('Tests') ?></h2>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th><?= __('ID') ?></th>
                            <th><?= __('Public') ?></th>
                            <th><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($difficulty->tests as $t) : ?>
                            <tr>
                                <td class="mf-muted"><?= h((string)$t->id) ?></td>
                                <td class="mf-muted"><?= $t->is_public ? __('Yes') : __('No') ?></td>
                                <td>
                                    <?= $this->Html->link(
                                        __('View'),
                                        ['controller' => 'Tests', 'action' => 'view', $t->id, 'lang' => $lang],
                                        ['class' => 'btn btn-sm btn-outline-light'],
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
