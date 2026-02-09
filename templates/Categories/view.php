<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Category $category
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Category'));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-white"><?= __('Category') ?> #<?= h((string)$category->id) ?></h1>
            <div class="mf-muted"><?= __('Translations') ?>: <?= h((string)count($category->category_translations ?? [])) ?></div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $category->id, 'lang' => $lang], ['class' => 'btn btn-outline-light']) ?>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $category->id, 'lang' => $lang],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $category->id),
                    'class' => 'btn btn-outline-danger',
                ],
            ) ?>
            <?= $this->Html->link(__('Back to List'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="mf-admin-card p-3">
        <div class="row g-3">
            <div class="col-12 col-lg-3">
                <div class="mf-muted mb-1"><?= __('Is Active') ?></div>
                <div>
                    <?php if ($category->is_active) : ?>
                        <span class="badge bg-success"><?= __('Active') ?></span>
                    <?php else : ?>
                        <span class="badge bg-secondary"><?= __('Inactive') ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Created') ?></div>
                <div class="text-white"><?= $category->created_at ? h($category->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="mf-muted mb-1"><?= __('Updated') ?></div>
                <div class="text-white"><?= $category->updated_at ? h($category->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($category->category_translations)) : ?>
        <div class="mf-admin-card p-3 mt-3">
            <h2 class="h5 mb-3 text-white"><?= __('Translations') ?></h2>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th><?= __('Language Id') ?></th>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Description') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($category->category_translations as $t) : ?>
                            <tr>
                                <td class="mf-muted"><?= h((string)$t->language_id) ?></td>
                                <td class="text-white"><?= h((string)$t->name) ?></td>
                                <td class="mf-muted"><?= h((string)$t->description) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
