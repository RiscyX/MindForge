<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\DeviceLog> $deviceLogs
 * @var array<string, int> $stats
 * @var array<string, string> $filters
 * @var int $limit
 * @var array<int, int> $limitOptions
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Device Logs'));

$deviceTypeLabels = [
    0 => __('Mobile'),
    1 => __('Tablet'),
    2 => __('Desktop'),
];

$q = (string)($filters['q'] ?? '');
$selectedDeviceType = (string)($filters['device_type'] ?? '');
$from = (string)($filters['from'] ?? '');
$to = (string)($filters['to'] ?? '');

$pagination = (array)$this->Paginator->params();
$currentPage = (int)($pagination['page'] ?? 1);
$pageCount = (int)($pagination['pageCount'] ?? 1);
$recordCount = (int)($pagination['count'] ?? 0);
$queryParams = (array)$this->request->getQueryParams();
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Device Logs') ?></h1>
        <div class="mf-muted"><?= __('Security & device activity signals') ?></div>
    </div>
</div>

<div class="row g-3 mt-2 mf-admin-kpi-grid">
    <div class="col-6 col-md-6 col-xl-3">
        <div class="mf-admin-card p-3 h-100">
            <div class="mf-muted"><?= __('Total logs') ?></div>
            <div class="fs-3 fw-semibold"><?= $this->Number->format((int)($stats['total'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="mf-admin-card p-3 h-100">
            <div class="mf-muted"><?= __('Last 24h') ?></div>
            <div class="fs-3 fw-semibold"><?= $this->Number->format((int)($stats['last24h'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="mf-admin-card p-3 h-100">
            <div class="mf-muted"><?= __('Unique users') ?></div>
            <div class="fs-3 fw-semibold"><?= $this->Number->format((int)($stats['uniqueUsers'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="mf-admin-card p-3 h-100">
            <div class="mf-muted"><?= __('Unique IPs (24h)') ?></div>
            <div class="fs-3 fw-semibold"><?= $this->Number->format((int)($stats['uniqueIps24h'] ?? 0)) ?></div>
        </div>
    </div>
</div>

<div class="mf-admin-card p-3 mt-3">
    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'row g-2 align-items-end']) ?>
        <div class="col-12 col-xl-4">
            <?= $this->Form->control('q', [
                'label' => __('Search'),
                'value' => $q,
                'class' => 'form-control',
                'placeholder' => __('IP, location, user agent, user email'),
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
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
                'class' => 'form-select',
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <?= $this->Form->control('from', [
                'label' => __('From'),
                'type' => 'date',
                'value' => $from,
                'class' => 'form-control',
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <?= $this->Form->control('to', [
                'label' => __('To'),
                'type' => 'date',
                'value' => $to,
                'class' => 'form-control',
            ]) ?>
        </div>
        <div class="col-12 col-sm-6 col-xl-1">
            <?= $this->Form->control('limit', [
                'label' => __('Show'),
                'type' => 'select',
                'options' => array_combine(array_map('strval', $limitOptions), array_map('strval', $limitOptions)),
                'value' => (string)$limit,
                'class' => 'form-select',
            ]) ?>
        </div>
        <div class="col-12 col-xl-1 d-grid">
            <?= $this->Form->button(__('Apply'), ['class' => 'btn btn-primary']) ?>
        </div>
        <div class="col-12">
            <?= $this->Html->link(__('Reset filters'), ['action' => 'index', 'lang' => $lang], ['class' => 'btn btn-sm btn-outline-light']) ?>
        </div>
    <?= $this->Form->end() ?>
</div>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => ['action' => 'bulk', 'lang' => $lang, '?' => $this->request->getQueryParams()],
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
                        <td class="mf-muted"><?= h($createdLabel) ?></td>
                        <td>
                            <?php if ($deviceLog->user !== null) : ?>
                                <?= $this->Html->link(
                                    h((string)($deviceLog->user->email ?? ('User #' . (string)$deviceLog->user_id))),
                                    ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $deviceLog->user->id, 'lang' => $lang],
                                    ['class' => 'link-light link-underline-opacity-0 link-underline-opacity-100-hover'],
                                ) ?>
                            <?php elseif ($deviceLog->user_id !== null) : ?>
                                <span class="mf-muted"><?= h('User #' . (string)$deviceLog->user_id) ?></span>
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
                                    ['action' => 'delete', $deviceLog->id, 'lang' => $lang, '?' => $this->request->getQueryParams()],
                                    [
                                        'confirm' => __('Are you sure you want to delete # {0}?', $deviceLog->id),
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

    <?php if ($pageCount > 1) : ?>
        <?php
        $prevPage = max(1, $currentPage - 1);
        $nextPage = min($pageCount, $currentPage + 1);
        $startPage = max(1, $currentPage - 2);
        $endPage = min($pageCount, $currentPage + 2);
        ?>
        <nav aria-label="<?= h(__('Pagination')) ?>">
            <ul class="pagination pagination-sm mb-0 mf-admin-pagination">
                <li class="page-item <?= $this->Paginator->hasPrev() ? '' : 'disabled' ?>">
                    <?= $this->Html->link(
                        __('Previous'),
                        ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => $prevPage])],
                        ['class' => 'page-link'],
                    ) ?>
                </li>
                <?php for ($p = $startPage; $p <= $endPage; $p++) : ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <?= $this->Html->link(
                            (string)$p,
                            ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => $p])],
                            ['class' => 'page-link'],
                        ) ?>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $this->Paginator->hasNext() ? '' : 'disabled' ?>">
                    <?= $this->Html->link(
                        __('Next'),
                        ['action' => 'index', 'lang' => $lang, '?' => array_merge($queryParams, ['page' => $nextPage])],
                        ['class' => 'page-link'],
                    ) ?>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<div class="mf-muted small mt-2">
    <?= __('Total records: {0}', $recordCount) ?>
</div>

<?php $this->start('script'); ?>
<?= $this->Html->script('device_logs_index') ?>
<?php $this->end(); ?>
