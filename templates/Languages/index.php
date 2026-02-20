<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Language> $languages
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Languages'));

$allLanguages = is_array($languages) ? $languages : iterator_to_array($languages);
$totalLanguages = count($allLanguages);
$langCodes = array_map(fn($l) => strtoupper($l->code ?? ''), $allLanguages);
?>

<header class="mf-page-header">
    <div class="mf-page-header__left">
        <div>
            <h1 class="mf-page-header__title">
                <i class="bi bi-translate me-2 text-primary" aria-hidden="true"></i>
                <?= __('Languages') ?>
                <span class="mf-page-header__count"><?= $this->Number->format($totalLanguages) ?></span>
            </h1>
            <p class="mf-page-header__sub"><?= __('Manage supported interface and content languages.') ?></p>
        </div>
    </div>
</header>

<div class="row g-3 mb-3 mf-admin-kpi-grid">
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-translate mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Total') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($totalLanguages) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-globe mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Codes') ?></div>
                <div class="mf-kpi-card__value" style="font-size:0.95rem;"><?= h(implode(', ', $langCodes)) ?></div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfLanguagesSearch',
        'label' => __('Search'),
        'placeholder' => __('Search…'),
        'maxWidth' => '400px',
    ],
    'limit' => [
        'id' => 'mfLanguagesLimit',
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
        'label' => __('New Language') . ' +',
        'url' => ['action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => ['action' => 'bulk', 'lang' => $lang],
        'id' => 'mfLanguagesBulkForm',
    ]) ?>

    <div class="mf-admin-table-scroll">
        <table id="mfLanguagesTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Code') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Name') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($languages as $language) : ?>
                    <tr>
                        <td>
                            <input
                                class="form-check-input mf-row-select"
                                type="checkbox"
                                name="ids[]"
                                value="<?= h((string)$language->id) ?>"
                                aria-label="<?= h(__('Select language')) ?>"
                            />
                        </td>
                        <td class="mf-muted" data-order="<?= h((string)$language->id) ?>"><?= $this->Number->format($language->id) ?></td>
                        <td class="mf-muted"><?= h($language->code) ?></td>
                        <td><?= h($language->name) ?></td>
                        <td>
                            <div class="mf-admin-actions">
                                <?= $this->Html->link(
                                    '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>' . h(__('Edit')) . '</span>',
                                    ['action' => 'edit', $language->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm mf-admin-action mf-admin-action--neutral', 'escape' => false],
                                ) ?>
                                <?= $this->Form->postLink(
                                    '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
                                    ['action' => 'delete', $language->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $language->id),
                                        'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                                        'escape' => false,
                                    ],
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($languages) === 0) : ?>
                    <?= $this->element('functions/admin_empty_state', [
                        'message' => __('No languages found.'),
                        'ctaUrl' => ['action' => 'add', 'lang' => $lang],
                        'ctaLabel' => __('New Language'),
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
            'checkboxId' => 'mfLanguagesSelectAll',
            'linkId' => 'mfLanguagesSelectAllLink',
            'text' => __('Select all'),
        ],
        'bulk' => [
            'label' => __('Action for selected items:'),
            'formId' => 'mfLanguagesBulkForm',
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
        <div id="mfLanguagesPagination"></div>
    </nav>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfLanguagesTable',
        'searchInputId' => 'mfLanguagesSearch',
        'limitSelectId' => 'mfLanguagesLimit',
        'bulkFormId' => 'mfLanguagesBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfLanguagesSelectAll',
        'selectAllLinkId' => 'mfLanguagesSelectAllLink',
        'paginationContainerId' => 'mfLanguagesPagination',
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
            'nonSearchableTargets' => [0, -1],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 1,
            'defaultSortDir' => 'asc',
            'excludedSortCols' => [0, 4],
            'searchCols' => [1, 2, 3],
        ],
    ],
]) ?>
<?php $this->end(); ?>
