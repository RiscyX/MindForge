<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Test $test
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Test'));

$title = null;
if (!empty($test->test_translations)) {
    $title = $test->test_translations[0]->title ?? null;
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-white"><?= __('Test') ?> #<?= h((string)$test->id) ?></h1>
            <div class="mf-muted"><?= $title ? h((string)$title) : __('N/A') ?></div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $test->id, 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $test->id, 'lang' => $lang],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $test->id),
                    'class' => 'btn btn-outline-danger',
                ],
            ) ?>
            <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="mf-admin-card p-3">
        <div class="row g-3">
            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Category') ?></div>
                <div class="text-white">
                    <?php if ($test->hasValue('category')) : ?>
                        <?= $this->Html->link(
                            '#' . h((string)$test->category->id),
                            ['controller' => 'Categories', 'action' => 'view', $test->category->id, 'lang' => $lang],
                            ['class' => 'link-light'],
                        ) ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Difficulty') ?></div>
                <div class="text-white">
                    <?php if ($test->hasValue('difficulty')) : ?>
                        <?= $this->Html->link(
                            h((string)$test->difficulty->name),
                            ['controller' => 'Difficulties', 'action' => 'view', $test->difficulty->id, 'lang' => $lang],
                            ['class' => 'link-light'],
                        ) ?>
                    <?php else : ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-2">
                <div class="mf-muted mb-1"><?= __('Questions') ?></div>
                <div class="text-white"><?= h((string)count($test->questions ?? [])) ?></div>
            </div>

            <div class="col-12 col-lg-2">
                <div class="mf-muted mb-1"><?= __('Public') ?></div>
                <div>
                    <?php if ($test->is_public) : ?>
                        <span class="badge bg-success"><?= __('Yes') ?></span>
                    <?php else : ?>
                        <span class="badge bg-secondary"><?= __('No') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Created') ?></div>
                <div class="text-white"><?= $test->created_at ? h($test->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Updated') ?></div>
                <div class="text-white"><?= $test->updated_at ? h($test->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($test->questions)) : ?>
        <div class="mf-admin-card p-3 mt-3">
            <h2 class="h5 mb-3 text-white"><?= __('Questions') ?></h2>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th><?= __('ID') ?></th>
                            <th><?= __('Source') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($test->questions as $q) : ?>
                            <tr>
                                <td class="mf-muted"><?= h((string)$q->id) ?></td>
                                <td class="mf-muted"><?= h((string)$q->source_type) ?></td>
                                <td>
                                    <?php if ($q->is_active) : ?>
                                        <span class="badge bg-success"><?= __('Active') ?></span>
                                    <?php else : ?>
                                        <span class="badge bg-secondary"><?= __('Inactive') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $this->Html->link(
                                        __('View'),
                                        ['controller' => 'Questions', 'action' => 'view', $q->id, 'lang' => $lang],
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
