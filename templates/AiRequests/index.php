<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AiRequest> $aiRequests
 * @var array $stats
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('AI Requests'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);

$topTypes24h = (array)($stats['topTypes24h'] ?? []);
$topSources24h = (array)($stats['topSources24h'] ?? []);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('AI Requests') ?></h1>
        <div class="mf-muted"><?= __('AI usage telemetry & diagnostics') ?></div>
    </div>
</div>

    <div class="row g-3 mt-2 mf-admin-kpi-grid">
        <div class="col-6 col-md-6 col-xl-3">
            <div class="mf-admin-card p-3 h-100">
                <div class="mf-muted"><?= __('Total requests') ?></div>
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
                <div class="mf-muted"><?= __('Success (total)') ?></div>
                <div class="fs-3 fw-semibold"><?= $this->Number->format((int)($stats['successTotal'] ?? 0)) ?></div>
                <div class="mf-admin-delta"><?= __('Last 24h: {0}', $this->Number->format((int)($stats['success24h'] ?? 0))) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-6 col-xl-3">
            <div class="mf-admin-card p-3 h-100">
                <div class="mf-muted"><?= __('Unique users (24h)') ?></div>
                <div class="fs-3 fw-semibold"><?= $this->Number->format((int)($stats['uniqueUsers24h'] ?? 0)) ?></div>
            </div>
        </div>
    </div>

    <?php if ($topTypes24h || $topSources24h) : ?>
        <div class="row g-3 mt-1">
            <div class="col-12 col-xl-6">
                <div class="mf-admin-card p-3 h-100">
                    <div class="mf-muted mb-2"><?= __('Top types (24h)') ?></div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!$topTypes24h) : ?>
                            <span class="mf-muted">—</span>
                        <?php else : ?>
                            <?php foreach ($topTypes24h as $row) : ?>
                                <?php
                                    $label = (string)($row['type'] ?? '');
                                    $count = (int)($row['count'] ?? 0);
                                ?>
                                <span class="mf-admin-pill"><?= h($label !== '' ? $label : __('Unknown')) ?> · <?= $this->Number->format($count) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="mf-admin-card p-3 h-100">
                    <div class="mf-muted mb-2"><?= __('Top sources (24h)') ?></div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!$topSources24h) : ?>
                            <span class="mf-muted">—</span>
                        <?php else : ?>
                            <?php foreach ($topSources24h as $row) : ?>
                                <?php
                                    $label = (string)($row['source_reference'] ?? '');
                                    $count = (int)($row['count'] ?? 0);
                                ?>
                                <span class="mf-admin-pill"><?= h($label !== '' ? $label : __('Unknown')) ?> · <?= $this->Number->format($count) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?= $this->element('functions/admin_list_controls', [
        'search' => [
            'id' => 'mfAiRequestsSearch',
            'label' => __('Search'),
            'placeholder' => __('Search…'),
            'maxWidth' => '420px',
        ],
        'limit' => [
            'id' => 'mfAiRequestsLimit',
            'label' => __('Show'),
            'default' => '10',
            'options' => [
                '10' => '10',
                '50' => '50',
                '100' => '100',
                '-1' => __('All'),
            ],
        ],
        'create' => null,
    ]) ?>

    <div class="mf-admin-table-card mt-3">
        <?= $this->Form->create(null, [
            'url' => ['action' => 'bulk', 'lang' => $lang],
            'id' => 'mfAiRequestsBulkForm',
        ]) ?>

        <div class="mf-admin-table-scroll">
            <table id="mfAiRequestsTable" class="table table-dark table-hover mb-0 align-middle text-center">
                <thead>
                    <tr>
                        <th scope="col" class="mf-muted fs-6"></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('User') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Type') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Status') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Lang') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Source') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Ref') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Payload') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aiRequests as $aiRequest) : ?>
                        <?php
                            $createdOrder = $aiRequest->created_at ? $aiRequest->created_at->format('Y-m-d H:i:s') : '0';
                            $createdLabel = $aiRequest->created_at ? $aiRequest->created_at->i18nFormat('yyyy-MM-dd HH:mm') : '—';

                            $status = (string)($aiRequest->status ?? '');
                            $statusClass = 'mf-admin-pill';
                            if ($status === 'success') {
                                $statusClass .= ' border-success text-success';
                            } elseif ($status !== '') {
                                $statusClass .= ' border-warning text-warning';
                            }

                            $sourceMedium = (string)($aiRequest->source_medium ?? '');
                            $sourceRef = (string)($aiRequest->source_reference ?? '');

                            $inputLen = $aiRequest->input_payload !== null ? strlen((string)$aiRequest->input_payload) : 0;
                            $outputLen = $aiRequest->output_payload !== null ? strlen((string)$aiRequest->output_payload) : 0;
                        ?>
                        <tr>
                            <td>
                                <input
                                    class="form-check-input mf-row-select"
                                    type="checkbox"
                                    name="ids[]"
                                    value="<?= h((string)$aiRequest->id) ?>"
                                    aria-label="<?= h(__('Select request')) ?>"
                                />
                            </td>
                            <td class="mf-muted" data-order="<?= h($createdOrder) ?>"><?= h($createdLabel) ?></td>
                            <td>
                                <?php if ($aiRequest->user !== null) : ?>
                                    <?= $this->Html->link(
                                        h((string)($aiRequest->user->email ?? ('User #' . (string)$aiRequest->user_id))),
                                        ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $aiRequest->user->id, 'lang' => $lang],
                                        ['class' => 'link-light link-underline-opacity-0 link-underline-opacity-100-hover'],
                                    ) ?>
                                <?php elseif ($aiRequest->user_id !== null) : ?>
                                    <span class="mf-muted"><?= h('User #' . (string)$aiRequest->user_id) ?></span>
                                <?php else : ?>
                                    <span class="mf-muted"><?= __('System') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="mf-admin-pill"><?= h((string)$aiRequest->type) ?></span>
                            </td>
                            <td data-order="<?= h($status) ?>">
                                <span class="<?= h($statusClass) ?>"><?= h($status !== '' ? $status : __('Unknown')) ?></span>
                            </td>
                            <td class="mf-muted" data-order="<?= h((string)($aiRequest->language?->code ?? '')) ?>">
                                <?= $aiRequest->language !== null ? h((string)($aiRequest->language->code ?? $aiRequest->language->name ?? '—')) : '—' ?>
                            </td>
                            <td class="mf-muted" data-order="<?= h($sourceMedium) ?>">
                                <?= $sourceMedium !== '' ? h($sourceMedium) : '—' ?>
                            </td>
                            <td class="mf-muted" data-order="<?= h($sourceRef) ?>">
                                <div class="text-truncate" style="max-width: 220px;" title="<?= h($sourceRef) ?>">
                                    <?= $sourceRef !== '' ? h($sourceRef) : '—' ?>
                                </div>
                            </td>
                            <td class="mf-muted" data-order="<?= h((string)($inputLen + $outputLen)) ?>">
                                <?= __('in {0} / out {1}', $this->Number->format($inputLen), $this->Number->format($outputLen)) ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                    <?= $this->Html->link(
                                        __('Details'),
                                        ['action' => 'view', $aiRequest->id, 'lang' => $lang],
                                        ['class' => 'btn btn-sm btn-outline-light'],
                                    ) ?>
                                    <?= $this->Form->postLink(
                                        __('Delete'),
                                        ['action' => 'delete', $aiRequest->id, 'lang' => $lang],
                                        [
                                            'confirm' => __('Are you sure you want to delete # {0}?', $aiRequest->id),
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
                'checkboxId' => 'mfAiRequestsSelectAll',
                'linkId' => 'mfAiRequestsSelectAllLink',
                'text' => __('Select all'),
            ],
            'bulk' => [
                'label' => __('Action for selected items:'),
                'formId' => 'mfAiRequestsBulkForm',
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
            <div id="mfAiRequestsPagination"></div>
        </nav>
    </div>

    <?php $this->start('script'); ?>
    <?= $this->element('functions/admin_table_operations', [
        'config' => [
            'tableId' => 'mfAiRequestsTable',
            'searchInputId' => 'mfAiRequestsSearch',
            'limitSelectId' => 'mfAiRequestsLimit',
            'bulkFormId' => 'mfAiRequestsBulkForm',
            'rowCheckboxSelector' => '.mf-row-select',
            'selectAllCheckboxId' => 'mfAiRequestsSelectAll',
            'selectAllLinkId' => 'mfAiRequestsSelectAllLink',
            'paginationContainerId' => 'mfAiRequestsPagination',
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
                'nonOrderableTargets' => [0, -1],
                'nonSearchableTargets' => [0, -1],
                'dom' => 'rt',
            ],
            'vanilla' => [
                'defaultSortCol' => 1,
                'defaultSortDir' => 'desc',
                'excludedSortCols' => [0, 9],
                'searchCols' => [1, 2, 3, 4, 5, 6, 7, 8],
            ],
        ],
    ]) ?>
    <?php $this->end(); ?>
