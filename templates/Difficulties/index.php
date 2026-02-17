<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Difficulty> $difficulties
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Difficulties'));
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Difficulties') ?></h1>
    </div>
</div>

<br>

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
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['action' => 'view', $difficulty->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $difficulty->id, 'lang' => $lang],
                                    ['class' => 'btn btn-sm btn-outline-light'],
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $difficulty->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $difficulty->id),
                                        'class' => 'btn btn-sm btn-outline-danger',
                                    ],
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
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
                    'label' => __('Delete'),
                    'value' => 'delete',
                    'class' => 'btn btn-sm btn-outline-danger',
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
