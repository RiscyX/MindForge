<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Difficulty> $difficulties
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Difficulties'));

$allDifficulties = is_array($difficulties) ? $difficulties : iterator_to_array($difficulties);
$totalDifficulties = count($allDifficulties);
$maxLevel = 0;
foreach ($allDifficulties as $d) {
    $lvl = (int)($d->level ?? 0);
    if ($lvl > $maxLevel) {
        $maxLevel = $lvl;
    }
}
?>

<header class="mf-page-header">
    <div class="mf-page-header__left">
        <div>
            <h1 class="mf-page-header__title">
                <i class="bi bi-bar-chart-steps me-2 text-primary" aria-hidden="true"></i>
                <?= __('Difficulties') ?>
                <span class="mf-page-header__count"><?= $this->Number->format($totalDifficulties) ?></span>
            </h1>
            <p class="mf-page-header__sub"><?= __('Define difficulty levels used when creating quizzes.') ?></p>
        </div>
    </div>
</header>

<div class="row g-3 mb-3 mf-admin-kpi-grid">
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-bar-chart-steps mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Total') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($totalDifficulties) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-speedometer2 mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Max level') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format($maxLevel) ?></div>
            </div>
        </div>
    </div>
</div>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfDifficultiesSearch',
        'label' => __('Search by name or id'),
        'placeholder' => __('Search…'),
        'maxWidth' => '400px',
    ],
    'limit' => [
        'id' => 'mfDifficultiesLimit',
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
        'label' => __('New Difficulty') . ' +',
        'url' => ['action' => 'add', 'lang' => $lang],
        'class' => 'btn btn-sm btn-primary',
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => ['action' => 'bulk', 'lang' => $lang],
        'id' => 'mfDifficultiesBulkForm',
    ]) ?>

    <div class="mf-admin-table-scroll">
        <table id="mfDifficultiesTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('ID') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Name') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Level') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($difficulties as $difficulty) : ?>
                    <?php 
                        $translatedName = '';
                        if (!empty($difficulty->difficulty_translations)) {
                            $translatedName = $difficulty->difficulty_translations[0]->name;
                        } else {
                            // Fallback if no translation for current lang found
                            // Attempt to load default English translation or the first available?
                            // Since we filtered in controller, we don't have others here.
                            $translatedName = __('Not translated');
                        }
                    ?>
                    <tr>
                        <td>
                            <input
                                class="form-check-input mf-row-select"
                                type="checkbox"
                                name="ids[]"
                                value="<?= h((string)$difficulty->id) ?>"
                                aria-label="<?= h(__('Select difficulty')) ?>"
                            />
                        </td>
                        <td class="mf-muted" data-order="<?= h((string)$difficulty->id) ?>"><?= $this->Number->format($difficulty->id) ?></td>
                        <td><?= h($translatedName) ?></td>
                        <td class="mf-muted" data-order="<?= h((string)$difficulty->level) ?>"><?= $this->Number->format($difficulty->level) ?></td>
                        <td>
                            <div class="mf-admin-actions">
                                <?= $this->Html->link(
                                    '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>' . h(__('Edit')) . '</span>',
                                    ['action' => 'edit', $difficulty->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm mf-admin-action mf-admin-action--neutral', 'escape' => false],
                                ) ?>
                                <?= $this->Form->postLink(
                                    '<i class="bi bi-trash3" aria-hidden="true"></i><span>' . h(__('Delete')) . '</span>',
                                    ['action' => 'delete', $difficulty->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id),
                                        'class' => 'btn btn-sm mf-admin-action mf-admin-action--danger',
                                        'escape' => false,
                                    ],
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($difficulties) === 0) : ?>
                    <?= $this->element('functions/admin_empty_state', [
                        'message' => __('No difficulties found.'),
                        'ctaUrl' => ['action' => 'add', 'lang' => $lang],
                        'ctaLabel' => __('New Difficulty'),
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
            'checkboxId' => 'mfDifficultiesSelectAll',
            'linkId' => 'mfDifficultiesSelectAllLink',
            'text' => __('Select all'),
        ],
        'bulk' => [
            'label' => __('Action for selected items:'),
            'formId' => 'mfDifficultiesBulkForm',
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
        <div id="mfDifficultiesPagination"></div>
    </nav>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfDifficultiesTable',
        'searchInputId' => 'mfDifficultiesSearch',
        'limitSelectId' => 'mfDifficultiesLimit',
        'bulkFormId' => 'mfDifficultiesBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfDifficultiesSelectAll',
        'selectAllLinkId' => 'mfDifficultiesSelectAllLink',
        'paginationContainerId' => 'mfDifficultiesPagination',
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
