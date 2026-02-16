<?php
$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Admin Dashboard'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Dashboard Overview') ?></h1>
        <div class="mf-muted"><?= __('Welcome back, Administrator. Here\'s what\'s happening today.') ?></div>
    </div>
    <div class="mf-muted" style="font-size:0.9rem;">
        <span class="mf-admin-pill"><?= __('Last updated: Just now') ?></span>
    </div>
</div>

<div class="row g-3 mt-3 mf-admin-kpi-grid">
    <?php
    /** @var array{totalUsers:int,activeUsers:int,totalTests:int,totalQuestions:int,todaysLogins:int,aiRequests:int} $metrics */
    $metrics = $stats ?? [
        'totalUsers' => 0,
        'activeUsers' => 0,
        'totalTests' => 0,
        'totalQuestions' => 0,
        'todaysLogins' => 0,
        'aiRequests' => 0,
    ];

    $statCards = [
        ['label' => __('Total Users'), 'value' => number_format((int)$metrics['totalUsers']), 'delta' => ''],
        ['label' => __('Active Users'), 'value' => number_format((int)$metrics['activeUsers']), 'delta' => ''],
        ['label' => __('Total Tests'), 'value' => number_format((int)$metrics['totalTests']), 'delta' => ''],
        ['label' => __('Questions'), 'value' => number_format((int)$metrics['totalQuestions']), 'delta' => ''],
        ['label' => __('Today\'s Logins'), 'value' => number_format((int)$metrics['todaysLogins']), 'delta' => ''],
        ['label' => __('AI Requests'), 'value' => number_format((int)$metrics['aiRequests']), 'delta' => ''],
    ];
    ?>
    <?php foreach ($statCards as $stat) : ?>
        <div class="col-6 col-lg-4 col-xl-2">
            <div class="mf-admin-card p-3 h-100">
                <div class="mf-muted" style="font-size:0.85rem;"><?= h($stat['label']) ?></div>
                <div class="fw-semibold" style="font-size:1.35rem; line-height:1.15;"><?= h($stat['value']) ?></div>
                <?php if ($stat['delta'] !== '') : ?>
                    <div class="mf-admin-delta"><?= h($stat['delta']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 mt-4 flex-wrap">
    <div class="d-flex align-items-center gap-2">
        <h2 class="h5 mb-0"><?= __('Recent System Events') ?></h2>
    </div>
</div>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfEventsSearch',
        'label' => __('Search'),
        'placeholder' => __('Search…'),
        'maxWidth' => '480px',
    ],
    'limit' => [
        'id' => 'mfEventsLimit',
        'label' => __('Show'),
        'default' => '10',
        'options' => [
            '10' => '10',
            '50' => '50',
            '100' => '100',
            '-1' => __('All'),
        ],
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => [
            'prefix' => 'Admin',
            'controller' => 'Dashboard',
            'action' => 'bulk',
            'lang' => $lang,
        ],
        'id' => 'mfEventsBulkForm',
    ]) ?>

    <div class="mf-admin-table-scroll">
        <table id="mfEventsTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Timestamp') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Event Type') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('User') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Details') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $badge = static function (string $status): string {
                    return match ($status) {
                        'Success' => 'bg-success',
                        'Failed' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                };

                /** @var list<array{id:int,ts:string,type:string,user:string,details:string,status:string}> $rows */
                $rows = $recentEvents ?? [];
                ?>

                <?php if (!$rows) : ?>
                    <tr>
                        <td colspan="6" class="mf-muted py-4">
                            <?= __('No events yet.') ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td>
                                <input
                                    class="form-check-input mf-row-select"
                                    type="checkbox"
                                    name="ids[]"
                                    value="<?= h((string)$row['id']) ?>"
                                    aria-label="<?= h(__('Select event')) ?>"
                                />
                            </td>
                            <td class="mf-muted" data-order="<?= h($row['ts']) ?>"><span class="text-nowrap"><?= h($row['ts']) ?></span></td>
                            <td class="mf-muted"><?= h($row['type']) ?></td>
                            <td class="mf-muted"><?= h($row['user']) ?></td>
                            <td class="mf-muted" style="max-width: 420px;">
                                <span style="display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= h($row['details']) ?>
                                </span>
                            </td>
                            <td data-order="<?= h($row['status']) ?>"><span class="badge <?= h($badge($row['status'])) ?>"><?= h($row['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?= $this->Form->end() ?>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mt-2">
    <div>
        <?php if ($rows) : ?>
        <?= $this->element('functions/admin_bulk_controls', [
            'containerClass' => 'd-flex align-items-center gap-3 flex-wrap',
            'selectAll' => [
                'checkboxId' => 'mfEventsSelectAll',
                'linkId' => 'mfEventsSelectAllLink',
                'text' => __('Összes bejelölése'),
            ],
            'bulk' => [
                'label' => __('A kijelöltekkel végzendő művelet:'),
                'formId' => 'mfEventsBulkForm',
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
        <?php endif; ?>
    </div>

    <nav aria-label="<?= h(__('Pagination')) ?>">
        <div id="mfEventsPagination"></div>
    </nav>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfEventsTable',
        'searchInputId' => 'mfEventsSearch',
        'limitSelectId' => 'mfEventsLimit',
        'bulkFormId' => 'mfEventsBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfEventsSelectAll',
        'selectAllLinkId' => 'mfEventsSelectAllLink',
        'paginationContainerId' => 'mfEventsPagination',
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
            'order' => [[1, 'desc']],
            'nonOrderableTargets' => [0],
            'nonSearchableTargets' => [5],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 1,
            'defaultSortDir' => 'desc',
            'excludedSortCols' => [0],
            'searchCols' => [1, 2, 3, 4],
        ],
    ],
]) ?>
<?php $this->end(); ?>
