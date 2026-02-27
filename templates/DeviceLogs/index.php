<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\DeviceLog> $deviceLogs
 * @var array<string, int> $stats
 * @var array<string, string> $filters
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Device Logs'));

$deviceTypeLabels = [
    0 => __('Mobile'),
    1 => __('Tablet'),
    2 => __('Desktop'),
];

$selectedDeviceType = (string)($filters['device_type'] ?? '');
$from = (string)($filters['from'] ?? '');
$to = (string)($filters['to'] ?? '');
?>

<header class="mf-page-header">
    <div class="mf-page-header__left">
        <div>
            <h1 class="mf-page-header__title">
                <i class="bi bi-phone me-2 text-primary" aria-hidden="true"></i><?= __('Device Logs') ?></h1>
            <p class="mf-page-header__sub"><?= __('Security & device activity signals') ?></p>
        </div>
    </div>
    <?php if (($filters['user_id'] ?? '') !== '') : ?>
        <div class="mf-page-header__right">
            <span class="mf-admin-pill">
                <i class="bi bi-person-fill me-1" aria-hidden="true"></i>
                <?= __('Filtered: User #{0}', h($filters['user_id'])) ?>
            </span>
            <?= $this->Html->link(
                '<i class="bi bi-x" aria-hidden="true"></i> ' . h(__('Clear filter')),
                ['action' => 'index', 'lang' => $lang],
                ['class' => 'btn btn-sm btn-outline-light', 'escape' => false],
            ) ?>
        </div>
    <?php endif; ?>
</header>

<div class="row g-3 mt-2 mf-admin-kpi-grid">
    <div class="col-6 col-md-6 col-xl-3">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-list-ul mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Total logs') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format((int)($stats['total'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-clock mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Last 24h') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format((int)($stats['last24h'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-people mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Unique users') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format((int)($stats['uniqueUsers'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="mf-admin-card mf-kpi-card p-3 h-100">
            <i class="bi bi-globe mf-kpi-card__icon" aria-hidden="true"></i>
            <div class="mf-kpi-card__body">
                <div class="mf-kpi-card__label"><?= __('Unique IPs (24h)') ?></div>
                <div class="mf-kpi-card__value"><?= $this->Number->format((int)($stats['uniqueIps24h'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="mf-admin-card p-3 mt-3">
    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
        <?php if (($filters['user_id'] ?? '') !== '') : ?>
            <input type="hidden" name="user_id" value="<?= h($filters['user_id']) ?>">
        <?php endif; ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <?= $this->Form->control('device_type', [
                'label' => __('Device'),
                'type' => 'select',
                'empty' => __('All'),
                'options' => [
                    '0' => __('Mobile'),
                    '1' => __('Tablet'),
                    '2' => __('Desktop'),
                ],
                'value' => $selectedDeviceType,
                'class' => 'form-select mf-admin-select',
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <?= $this->Form->control('from', [
                'label' => __('From'),
                'type' => 'date',
                'value' => $from,
                'class' => 'form-control mf-admin-input',
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <?= $this->Form->control('to', [
                'label' => __('To'),
                'type' => 'date',
                'value' => $to,
                'class' => 'form-control mf-admin-input',
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-3 d-flex gap-2">
            <?= $this->Form->button(__('Apply'), ['class' => 'btn btn-sm btn-primary mf-admin-apply-btn']) ?>
            <?= $this->Html->link(__('Reset'), ['action' => 'index', 'lang' => $lang, '?' => ($filters['user_id'] !== '' ? ['user_id' => $filters['user_id']] : [])], ['class' => 'btn btn-sm btn-outline-light mf-admin-reset-btn']) ?>
        </div>
    <?= $this->Form->end() ?>
</div>

<?= $this->element('functions/admin_list_controls', [
    'search' => [
        'id' => 'mfDeviceLogsSearch',
        'label' => __('Search by IP, location, user agent, email'),
        'placeholder' => __('Search…'),
        'maxWidth' => '420px',
    ],
    'limit' => [
        'id' => 'mfDeviceLogsLimit',
        'label' => __('Show'),
        'default' => '25',
        'options' => [
            '10' => '10',
            '25' => '25',
            '50' => '50',
            '100' => '100',
            '-1' => __('All'),
        ],
    ],
]) ?>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => ['action' => 'bulk', 'lang' => $lang],
        'id' => 'mfDeviceLogsBulkForm',
    ]) ?>

    <div class="mf-admin-table-scroll">
        <table id="mfDeviceLogsTable" class="table table-dark table-hover mb-0 align-middle text-center">
            <thead>
                <tr>
                    <th scope="col" class="mf-muted fs-6"></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('User') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('IP') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Device') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Location') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('User Agent') ?></th>
                    <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deviceLogs as $deviceLog) : ?>
                    <?php
                        $createdLabel = $deviceLog->created_at ? $deviceLog->created_at->i18nFormat('yyyy-MM-dd HH:mm') : '—';
                        $createdOrder = $deviceLog->created_at ? $deviceLog->created_at->format('Y-m-d H:i:s') : '0';
                        $userAgent = (string)($deviceLog->user_agent ?? '');
                        $deviceType = (int)($deviceLog->device_type ?? 0);
                        $deviceLabel = $deviceTypeLabels[$deviceType] ?? __('Unknown');
                        $country = trim((string)($deviceLog->country ?? ''));
                        $city = trim((string)($deviceLog->city ?? ''));
                        $location = trim(trim($city . ', ' . $country), ', ');
                    ?>
                    <tr>
                        <td>
                            <input
                                class="form-check-input mf-row-select"
                                type="checkbox"
                                name="ids[]"
                                value="<?= h((string)$deviceLog->id) ?>"
                                aria-label="<?= h(__('Select log')) ?>"
                            />
                        </td>
                        <td class="mf-muted" data-order="<?= h($createdOrder) ?>"><?= h($createdLabel) ?></td>
                        <td>
                            <?php if ($deviceLog->user !== null) : ?>
                                <?= $this->Html->link(
                                    h((string)($deviceLog->user->email ?? __('User #{0}', (string)$deviceLog->user_id))),
                                    ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $deviceLog->user->id, 'lang' => $lang],
                                    ['class' => 'link-light link-underline-opacity-0 link-underline-opacity-100-hover'],
                                ) ?>
                            <?php elseif ($deviceLog->user_id !== null) : ?>
                                <span class="mf-muted"><?= h(__('User #{0}', (string)$deviceLog->user_id)) ?></span>
                            <?php else : ?>
                                <span class="mf-muted"><?= __('Guest') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="mf-muted"><?= h((string)($deviceLog->ip_address ?? '—')) ?></td>
                        <td>
                            <span class="mf-admin-pill"><?= h($deviceLabel) ?></span>
                        </td>
                        <td class="mf-muted">
                            <?= $location !== '' ? h($location) : '—' ?>
                        </td>
                        <td class="mf-muted">
                            <div class="text-truncate" style="max-width: 420px;" title="<?= h($userAgent) ?>">
                                <?= $userAgent !== '' ? h($userAgent) : '—' ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $deviceLog->id, 'lang' => $lang],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $deviceLog->id),
                                        'class' => 'btn btn-sm btn-outline-danger',
                                    ],
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($deviceLogs) === 0) : ?>
                    <?= $this->element('functions/admin_empty_state', [
                        'message' => __('No device logs found.'),
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
            'checkboxId' => 'mfDeviceLogsSelectAll',
            'linkId' => 'mfDeviceLogsSelectAllLink',
            'text' => __('Select all'),
        ],
        'bulk' => [
            'label' => __('Action for selected items:'),
            'formId' => 'mfDeviceLogsBulkForm',
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
        <div id="mfDeviceLogsPagination"></div>
    </nav>
</div>

<?php $this->start('script'); ?>
<?= $this->element('functions/admin_table_operations', [
    'config' => [
        'tableId' => 'mfDeviceLogsTable',
        'searchInputId' => 'mfDeviceLogsSearch',
        'limitSelectId' => 'mfDeviceLogsLimit',
        'bulkFormId' => 'mfDeviceLogsBulkForm',
        'rowCheckboxSelector' => '.mf-row-select',
        'selectAllCheckboxId' => 'mfDeviceLogsSelectAll',
        'selectAllLinkId' => 'mfDeviceLogsSelectAllLink',
        'paginationContainerId' => 'mfDeviceLogsPagination',
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
            'pageLength' => 25,
            'order' => [[1, 'desc']],
            'nonOrderableTargets' => [0, -1],
            'nonSearchableTargets' => [],
            'dom' => 'rt',
        ],
        'vanilla' => [
            'defaultSortCol' => 1,
            'defaultSortDir' => 'desc',
            'excludedSortCols' => [0, 7],
            'searchCols' => [1, 2, 3, 4, 5, 6],
        ],
    ],
]) ?>
<?php $this->end(); ?>
