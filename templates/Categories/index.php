<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Category> $categories
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Categories'));

// — Compute KPIs from the already-loaded result set --------------------------
$allCategories = is_array($categories) ? $categories : iterator_to_array($categories);
$totalCategories = count($allCategories);
$activeCount = 0;
$inactiveCount = 0;
foreach ($allCategories as $cat) {
    if (!empty($cat->is_active)) {
        $activeCount++;
    } else {
        $inactiveCount++;
    }
}
?>

<header class="mf-page-header">
    <div class="mf-page-header__left">
        <div>
            <h1 class="mf-page-header__title">
                <i class="bi bi-tag-fill me-2 text-primary" aria-hidden="true"></i>
                <?= __('Categories') ?>
                <span class="mf-page-header__count"><?= $this->Number->format($totalCategories) ?></span>
            </h1>
            <p class="mf-page-header__sub"><?= __('Organise quizzes into categories and manage their translations.') ?></p>
        </div>
    </div>
</header>

<div class="row g-3 mb-3 mf-admin-kpi-grid">
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-tags mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Total') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($totalCategories) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-check-circle mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Active') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($activeCount) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-x-circle mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Inactive') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($inactiveCount) ?></div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfCategoriesSearch',
        'label' => __('Search by name or id'),
        'placeholder' => __('Search…'),
        'maxWidth' => '400px',
    ],
    'limit' => [
        'id' => 'mfCategoriesLimit',
        'label' => __('Show'),
        'default' => '10',
        'options' => [
            '10' => '10',
            '50' => '50',
            '100' => '100',
            '-1' => __('All'),
        ],
    ],
    'create' => [
        'label' => __('New Category') . ' +',
        'url' => ['action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => ['action' => 'bulk', 'lang' => $lang],
        'id' => 'mfCategoriesBulkForm',
    ]) ?>

    <div class="mf-admin-table-scroll">
        <table id="mfCategoriesTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Names') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Active') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Updated') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category) : ?>
                    <tr>
                        <td>
                            <input
                                class="form-check-input mf-row-select"
                                type="checkbox"
                                name="ids[]"
                                value="<?= h((string)$category->id) ?>"
                                aria-label="<?= h(__('Select category')) ?>"
                            />
                        </td>
                        <td class="mf-muted" data-order="<?= h((string)$category->id) ?>"><?= $this->Number->format($category->id) ?></td>
                        <td>
                            <?php foreach ($category->category_translations as $translation) : ?>
                                <?php if ($translation->hasValue('language')) : ?>
                                    <div>
                                        <span class="badge bg-secondary me-1"><?= h($translation->language->code) ?></span>
                                        <?= h($translation->name) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ($category->is_active) : ?>
                                <span class="badge bg-success"><?= __('Active') ?></span>
                            <?php else : ?>
                                <span class="badge bg-secondary"><?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $category->created_at ? h($category->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $category->created_at ? h($category->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td class="mf-muted" data-order="<?= $category->updated_at ? h($category->updated_at->format('Y-m-d H:i:s')) : '0' ?>">
                            <?= $category->updated_at ? h($category->updated_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                        </td>
                        <td>
                            <div class="mf-admin-actions">
                                <?= $this->Html->link(
                                    '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>' . h(__('Edit')) . '</span>',
                                    ['action' => 'edit', $category->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm mf-admin-action mf-admin-action--neutral', 'escape' => false],
                                ) ?>
                                <?= $this->Form->postLink(
                                    '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
                                    ['action' => 'delete', $category->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $category->id),
                                        'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                                        'escape' => false,
                                    ],
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($categories) === 0) : ?>
                    <?= $this->element('functions/admin_empty_state', [
                        'message' => __('No categories found.'),
                        'ctaUrl' => ['action' => 'add', 'lang' => $lang],
                        'ctaLabel' => __('New Category'),
                    ]) ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= $this->Form->end() ?>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-2">
    <?= $this->element('functions/admin_bulk_controls', [
        'containerClass' => 'd-flex align-items-center gap-3 flex-wrap',
        'selectAll' => [
            'checkboxId' => 'mfCategoriesSelectAll',
            'linkId' => 'mfCategoriesSelectAllLink',
            'text' => __('Select all'),
        ],
        'bulk' => [
            'label' => __('Action for selected items:'),
            'formId' => 'mfCategoriesBulkForm',
            'buttons' => [
                [
                    'label' => '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
                    'value' => 'delete',
                    'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                    'escapeTitle' => false,
                    'attrs' => [
                        'data-mf-bulk-delete' => true,
                    ],
                ],
            ],
        ],
    ]) ?>

    <nav aria-label="<?= h(__('Pagination')) ?>">
        <div id="mfCategoriesPagination"></div>
    </nav>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfCategoriesTable',
        'searchInputId' => 'mfCategoriesSearch',
        'limitSelectId' => 'mfCategoriesLimit',
        'bulkFormId' => 'mfCategoriesBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfCategoriesSelectAll',
        'selectAllLinkId' => 'mfCategoriesSelectAllLink',
        'paginationContainerId' => 'mfCategoriesPagination',
        'pagination' => [
            'windowSize' => 3,
            'jumpSize' => 3,
        ],
        'strings' => [
            'selectAtLeastOne' => (string)__('Select at least one item.'),
            'confirmDelete' => (string)__('Are you sure you want to delete the selected items?'),
        ],
        'bulkDeleteValues' => ['delete'],
        'dataTables' => [
            'enabled' => true,
            'searching' => true,
            'lengthChange' => false,
            'pageLength' => 10,
            'order' => [[1, 'asc']],
            'nonOrderableTargets' => [0, -1],
            'nonSearchableTargets' => [3, 4, 5],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 1,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [0, 6],
            'searchCols' => [1, 2],
        ],
    ],
]) ?>
<?php $this->end(); ?>
