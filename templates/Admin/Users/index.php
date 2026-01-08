<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface<\App\Model\Entity\User> $users
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('Users'));

$this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css', ['block' => 'css']);
$this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['block' => 'script']);
$this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js', ['block' => 'script']);
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= __('Users') ?></h1>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between gap-3 mt-4 flex-wrap">
    <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
        <label class="visually-hidden" for="mfUsersSearch"><?= __('Search by username or email') ?></label>
        <input id="mfUsersSearch" type="search" class="form-control form-control-sm mf-admin-input flex-grow-1"
               style="max-width:400px;" placeholder="<?= __('Search username/email…') ?>">
    </div>

    <div class="d-flex align-items-center gap-2">
        <label class="mf-muted" for="mfUsersLimit" style="font-size:0.9rem;"><?= __('Show') ?></label>
        <select id="mfUsersLimit" class="form-select form-select-sm mf-admin-select" style="width:auto;">
            <option value="10" selected>10</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="-1"><?= __('All') ?></option>
        </select>

        <?= $this->Html->link(
            __('Create User') . ' +',
            [
                'prefix' => 'Admin',
                'controller' => 'Users',
                'action' => 'add',
                'lang' => $lang,
            ],
            ['class' => 'btn btn-sm btn-primary'],
        ) ?>
    </div>
</div>

<div class="mf-admin-table-card mt-3">
    <?= $this->Form->create(null, [
        'url' => [
            'prefix' => 'Admin',
            'controller' => 'Users',
            'action' => 'bulk',
            'lang' => $lang,
        ],
        'id' => 'mfUsersBulkForm',
    ]) ?>

        <div class="mf-admin-table-scroll">
            <table id="mfUsersTable" class="table table-dark table-hover mb-0 align-middle text-center">
                <thead>
                    <tr>
                        <th scope="col" class="mf-muted fs-6"></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Username') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Email') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Role') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Last login') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Created') ?></th>
                        <th scope="col" class="mf-muted fs-6"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td>
                                <input
                                    class="form-check-input mf-user-select"
                                    type="checkbox"
                                    name="ids[]"
                                    value="<?= h((string)$user->id) ?>"
                                    aria-label="<?= h(__('Select user')) ?>"
                                />
                            </td>
                            <td><?= $user->username ? h($user->username) : '-' ?></td>
                            <td><?= h($user->email) ?></td>
                            <td class="mf-muted"><?= h($user->role?->name ?? '') ?></td>
                            <td class="mf-muted" data-order="<?= $user->last_login_at ? h($user->last_login_at->format('Y-m-d H:i:s')) : '0' ?>">
                                <?= $user->last_login_at ? h($user->last_login_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                            </td>
                            <td class="mf-muted" data-order="<?= $user->created_at ? h($user->created_at->format('Y-m-d H:i:s')) : '0' ?>">
                                <?= $user->created_at ? h($user->created_at->i18nFormat('yyyy-MM-dd HH:mm')) : '—' ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                    <?= $this->Html->link(
                                        __('Edit'),
                                        [
                                            'prefix' => 'Admin',
                                            'controller' => 'Users',
                                            'action' => 'edit',
                                            $user->id,
                                            'lang' => $lang,
                                        ],
                                        ['class' => 'btn btn-sm btn-outline-light'],
                                    ) ?>

                                    <?php if ($user->is_blocked) : ?>
                                        <button class="btn btn-sm btn-danger" type="button" disabled aria-disabled="true">
                                            <?= __('Ban') ?>
                                        </button>
                                        <?= $this->Form->postLink(
                                            __('Unban'),
                                            [
                                                'prefix' => 'Admin',
                                                'controller' => 'Users',
                                                'action' => 'unban',
                                                $user->id,
                                                'lang' => $lang,
                                            ],
                                            ['class' => 'btn btn-sm btn-success'],
                                        ) ?>
                                    <?php else : ?>
                                        <?= $this->Form->postLink(
                                            __('Ban'),
                                            [
                                                'prefix' => 'Admin',
                                                'controller' => 'Users',
                                                'action' => 'ban',
                                                $user->id,
                                                'lang' => $lang,
                                            ],
                                            [
                                                'class' => 'btn btn-sm btn-danger',
                                                'confirm' => __('Are you sure you want to ban this user?'),
                                            ],
                                        ) ?>
                                        <button class="btn btn-sm btn-success" type="button" disabled aria-disabled="true">
                                            <?= __('Unban') ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?= $this->Form->end() ?>
</div>

<div class="d-flex align-items-center gap-3 flex-wrap mt-2">
    <input id="mfUsersSelectAll" class="visually-hidden" type="checkbox" />
    <a
        id="mfUsersSelectAllLink"
        href="#"
        class="link-primary link-underline-opacity-0 link-underline-opacity-100-hover"
    >
        <?= h('↑ ') ?><?= __('Összes bejelölése') ?>
    </a>

    <span class="mf-muted" style="font-size:0.9rem;">
        <?= __('A kijelöltekkel végzendő művelet:') ?>
    </span>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button class="btn btn-sm btn-danger" type="submit" name="bulk_action" value="ban" form="mfUsersBulkForm">
            <?= __('Ban') ?>
        </button>
        <button class="btn btn-sm btn-success" type="submit" name="bulk_action" value="unban" form="mfUsersBulkForm">
            <?= __('Unban') ?>
        </button>
        <button class="btn btn-sm btn-outline-danger" type="submit" name="bulk_action" value="delete" data-mf-bulk-delete form="mfUsersBulkForm">
            <?= __('Delete') ?>
        </button>
    </div>
</div>

<?php $this->start('script'); ?>
<script>
(() => {
    const bulkForm = document.getElementById('mfUsersBulkForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', (e) => {
            const submitter = e.submitter;
            if (!(submitter instanceof HTMLButtonElement)) {
                return;
            }

            const anyChecked = !!bulkForm.querySelector('.mf-user-select:checked');
            if (!anyChecked) {
                e.preventDefault();
                alert('<?= h(__('Select at least one user.')) ?>');
                return;
            }

            if (submitter.value === 'delete') {
                const ok = confirm('<?= h(__('Are you sure you want to delete the selected users?')) ?>');
                if (!ok) {
                    e.preventDefault();
                }
            }
        });
    }

    const tableEl = document.getElementById('mfUsersTable');
    if (!tableEl) return;

    const searchInput = document.getElementById('mfUsersSearch');
    const limitSelect = document.getElementById('mfUsersLimit');
    const selectAll = document.getElementById('mfUsersSelectAll');
    const selectAllLink = document.getElementById('mfUsersSelectAllLink');

    if (selectAll && selectAllLink) {
        selectAllLink.addEventListener('click', (e) => {
            e.preventDefault();
            selectAll.checked = !selectAll.checked;
            selectAll.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    const hasJQuery = typeof window.jQuery !== 'undefined';
    const hasDataTables = hasJQuery && typeof window.jQuery.fn?.DataTable === 'function';

    if (hasDataTables) {
        const $ = window.jQuery;
        const $table = $(tableEl);

        const dt = $table.DataTable({
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, searchable: false, targets: [0, -1] },
                { searchable: false, targets: [0, 3, 4, 5] },
            ],
            dom: 'rt<"d-flex align-items-center justify-content-between mt-2"ip>',
        });

        if (limitSelect) {
            limitSelect.addEventListener('change', () => {
                const len = parseInt(limitSelect.value, 10);
                dt.page.len(Number.isFinite(len) ? len : 10).draw();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                dt.search(searchInput.value || '').draw();
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                const checked = !!selectAll.checked;
                const nodes = dt.rows({ page: 'current', search: 'applied' }).nodes().toArray();
                for (const row of nodes) {
                    const cb = row.querySelector('.mf-user-select');
                    if (cb) cb.checked = checked;
                }
            });

            // Keep master checkbox consistent after paging/filtering
            dt.on('draw', () => {
                if (!selectAll) return;
                const nodes = dt.rows({ page: 'current', search: 'applied' }).nodes().toArray();
                const cbs = nodes.map((row) => row.querySelector('.mf-user-select')).filter(Boolean);
                if (cbs.length === 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                    return;
                }
                const checkedCount = cbs.filter((cb) => cb.checked).length;
                selectAll.checked = checkedCount === cbs.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < cbs.length;
            });

            tableEl.addEventListener('change', (e) => {
                const target = e.target;
                if (!(target instanceof HTMLInputElement)) return;
                if (!target.classList.contains('mf-user-select')) return;
                dt.draw(false);
            });
        }

        return;
    }

    // Vanilla fallback (works even when CDN scripts are blocked)
    const tbody = tableEl.tBodies[0];
    if (!tbody) return;

    const headers = Array.from(tableEl.tHead?.rows?.[0]?.cells || []);
    const allRows = Array.from(tbody.rows);

    let sortCol = 1;
    let sortDir = 'asc';

    const getCellKey = (row, colIndex) => {
        const cell = row.cells[colIndex];
        if (!cell) return '';
        const order = cell.getAttribute('data-order');
        if (order !== null) return order;
        const txt = (cell.textContent || '').trim();
        return txt === '-' ? '' : txt;
    };

    const applyView = () => {
        const term = (searchInput?.value || '').trim().toLowerCase();

        const filtered = allRows.filter((row) => {
            if (term === '') return true;
            const username = getCellKey(row, 1).toLowerCase();
            const email = getCellKey(row, 2).toLowerCase();
            return username.includes(term) || email.includes(term);
        });

        filtered.sort((a, b) => {
            const ak = getCellKey(a, sortCol);
            const bk = getCellKey(b, sortCol);

            const cmp = ak.localeCompare(bk, undefined, { numeric: true, sensitivity: 'base' });
            return sortDir === 'asc' ? cmp : -cmp;
        });

        // Re-append in sorted order
        for (const row of filtered) {
            tbody.appendChild(row);
        }

        // Hide rows not in filtered set
        const visibleSet = new Set(filtered);
        for (const row of allRows) {
            if (!visibleSet.has(row)) {
                row.style.display = 'none';
            }
        }

        const limit = parseInt(limitSelect?.value || '10', 10);
        let shown = 0;
        for (const row of filtered) {
            if (Number.isFinite(limit) && limit > 0 && shown >= limit) {
                row.style.display = 'none';
                continue;
            }
            row.style.display = '';
            shown += 1;
        }

        headers.forEach((th, idx) => {
            if (idx === headers.length - 1) {
                th.removeAttribute('aria-sort');
                return;
            }
            if (idx === sortCol) {
                th.setAttribute('aria-sort', sortDir === 'asc' ? 'ascending' : 'descending');
            } else {
                th.removeAttribute('aria-sort');
            }
        });
    };

    headers.forEach((th, idx) => {
        const isCheckbox = idx === 0;
        const isActions = idx === headers.length - 1;
        if (isCheckbox || isActions) return;

        th.style.cursor = 'pointer';
        th.addEventListener('click', () => {
            if (sortCol === idx) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortCol = idx;
                sortDir = 'asc';
            }
            applyView();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyView);
    }
    if (limitSelect) {
        limitSelect.addEventListener('change', applyView);
    }

    if (selectAll) {
        const syncSelectAll = () => {
            const visibleRows = allRows.filter((row) => row.style.display !== 'none');
            const cbs = visibleRows
                .map((row) => row.querySelector('.mf-user-select'))
                .filter((cb) => cb instanceof HTMLInputElement);

            if (cbs.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                return;
            }

            const checkedCount = cbs.filter((cb) => cb.checked).length;
            selectAll.checked = checkedCount === cbs.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < cbs.length;
        };

        selectAll.addEventListener('change', () => {
            const checked = !!selectAll.checked;
            const visibleRows = allRows.filter((row) => row.style.display !== 'none');
            for (const row of visibleRows) {
                const cb = row.querySelector('.mf-user-select');
                if (cb instanceof HTMLInputElement) {
                    cb.checked = checked;
                }
            }
            syncSelectAll();
        });

        tableEl.addEventListener('change', (e) => {
            const target = e.target;
            if (!(target instanceof HTMLInputElement)) return;
            if (!target.classList.contains('mf-user-select')) return;
            syncSelectAll();
        });

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                window.requestAnimationFrame(syncSelectAll);
            });
        }
        if (limitSelect) {
            limitSelect.addEventListener('change', () => {
                window.requestAnimationFrame(syncSelectAll);
            });
        }
    }

    applyView();
})();
</script>
<?php $this->end(); ?>
