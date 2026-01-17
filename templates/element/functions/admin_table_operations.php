<?php
/**
 * Reusable table JS operations: search, limit, sorting (vanilla), select-all (page/visible),
 * and optional DataTables integration when available.
 *
 * @var \App\View\AppView $this
 * @var array $config
 */

$config = $config ?? [];

$encodedConfig = json_encode(
    $config,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
);
if ($encodedConfig === false) {
    $encodedConfig = '{}';
}
?>

<script>
(() => {
    const cfg = <?= $encodedConfig ?>;

    const tableId = cfg.tableId || 'mfTable';
    const tableEl = document.getElementById(tableId);
    if (!tableEl) return;

    const searchInput = cfg.searchInputId ? document.getElementById(cfg.searchInputId) : null;
    const limitSelect = cfg.limitSelectId ? document.getElementById(cfg.limitSelectId) : null;

    const bulkForm = cfg.bulkFormId ? document.getElementById(cfg.bulkFormId) : null;
    const rowCheckboxSelector = cfg.rowCheckboxSelector || '.mf-row-select';

    const selectAll = cfg.selectAllCheckboxId ? document.getElementById(cfg.selectAllCheckboxId) : null;
    const selectAllLink = cfg.selectAllLinkId ? document.getElementById(cfg.selectAllLinkId) : null;

    const paginationContainer = cfg.paginationContainerId ? document.getElementById(cfg.paginationContainerId) : null;

    const strings = Object.assign({
        selectAtLeastOne: 'Select at least one item.',
        confirmDelete: 'Are you sure you want to delete the selected items?',
    }, cfg.strings || {});

    const bulkDeleteValues = Array.isArray(cfg.bulkDeleteValues) ? cfg.bulkDeleteValues : ['delete'];

    if (bulkForm) {
        bulkForm.addEventListener('submit', (e) => {
            const submitter = e.submitter;
            if (!(submitter instanceof HTMLButtonElement)) {
                return;
            }

            const anyChecked = !!bulkForm.querySelector(`${rowCheckboxSelector}:checked`);
            if (!anyChecked) {
                e.preventDefault();
                alert(strings.selectAtLeastOne);
                return;
            }

            const isDelete = bulkDeleteValues.includes(submitter.value) || submitter.hasAttribute('data-mf-bulk-delete');
            if (isDelete) {
                const ok = confirm(strings.confirmDelete);
                if (!ok) {
                    e.preventDefault();
                }
            }
        });
    }

    if (selectAll && selectAllLink) {
        selectAllLink.addEventListener('click', (e) => {
            e.preventDefault();
            selectAll.checked = !selectAll.checked;
            selectAll.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    const hasJQuery = typeof window.jQuery !== 'undefined';
    const hasDataTables = hasJQuery && typeof window.jQuery.fn?.DataTable === 'function';

    const dtCfg = Object.assign({
        enabled: true,
        searching: true,
        lengthChange: false,
        pageLength: 10,
        order: [[1, 'asc']],
        dom: 'rt<"d-flex align-items-center justify-content-between mt-2"ip>',
        nonOrderableTargets: [0, -1],
        nonSearchableTargets: [],
    }, cfg.dataTables || {});

    if (hasDataTables && dtCfg.enabled) {
        const $ = window.jQuery;
        const $table = $(tableEl);

        const columnDefs = [];
        if (Array.isArray(dtCfg.nonOrderableTargets) && dtCfg.nonOrderableTargets.length) {
            columnDefs.push({ orderable: false, searchable: false, targets: dtCfg.nonOrderableTargets });
        }
        if (Array.isArray(dtCfg.nonSearchableTargets) && dtCfg.nonSearchableTargets.length) {
            columnDefs.push({ searchable: false, targets: dtCfg.nonSearchableTargets });
        }

        const dt = $table.DataTable({
            searching: !!dtCfg.searching,
            lengthChange: !!dtCfg.lengthChange,
            pageLength: Number.isFinite(dtCfg.pageLength) ? dtCfg.pageLength : 10,
            order: dtCfg.order || [[1, 'asc']],
            columnDefs,
            dom: paginationContainer ? 'rt' : dtCfg.dom,
        });

        const renderPagination = () => {
            if (!paginationContainer) return;

            const info = dt.page.info();
            const totalPages = Number(info.pages || 0);
            const currentPage = Number(info.page || 0); // 0-based

            if (!Number.isFinite(totalPages) || totalPages <= 1) {
                paginationContainer.innerHTML = '';
                paginationContainer.style.display = 'none';
                return;
            }

            paginationContainer.style.display = '';

            const windowSize = Number.isFinite(cfg.pagination?.windowSize)
                ? Math.max(1, parseInt(cfg.pagination.windowSize, 10))
                : 3;
            const jumpSize = Number.isFinite(cfg.pagination?.jumpSize)
                ? Math.max(1, parseInt(cfg.pagination.jumpSize, 10))
                : windowSize;

            const current1 = currentPage + 1; // 1-based for display

            let start = current1 - Math.floor(windowSize / 2);
            let end = start + windowSize - 1;

            if (start < 1) {
                start = 1;
                end = Math.min(totalPages, start + windowSize - 1);
            }
            if (end > totalPages) {
                end = totalPages;
                start = Math.max(1, end - windowSize + 1);
            }

            const pages = [];
            for (let p = start; p <= end; p += 1) pages.push(p);

            const mkBtn = (label, page1, { active = false, disabled = false } = {}) => {
                const li = document.createElement('li');
                li.className = 'page-item' + (active ? ' active' : '') + (disabled ? ' disabled' : '');

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'page-link' + (active ? ' fw-semibold' : '');
                btn.textContent = label;
                btn.disabled = !!disabled;

                if (!disabled && typeof page1 === 'number') {
                    btn.addEventListener('click', () => {
                        dt.page(page1 - 1).draw('page');
                    });
                }

                li.appendChild(btn);
                return li;
            };

            const mkEllipsis = (dir) => {
                const li = document.createElement('li');
                li.className = 'page-item';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'page-link';
                btn.textContent = '…';
                btn.addEventListener('click', () => {
                    const nextPage = dir === 'right'
                        ? Math.min(totalPages, current1 + jumpSize)
                        : Math.max(1, current1 - jumpSize);
                    dt.page(nextPage - 1).draw('page');
                });

                li.appendChild(btn);
                return li;
            };

            const ul = document.createElement('ul');
            ul.className = 'pagination pagination-sm mb-0';

            if (start > 1) {
                ul.appendChild(mkEllipsis('left'));
            }

            for (const p of pages) {
                ul.appendChild(mkBtn(String(p), p, { active: p === current1 }));
            }

            if (end < totalPages) {
                ul.appendChild(mkEllipsis('right'));
            }

            paginationContainer.innerHTML = '';
            paginationContainer.appendChild(ul);
        };

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

        if (paginationContainer) {
            dt.on('draw', renderPagination);
            renderPagination();
        }

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                const checked = !!selectAll.checked;
                const nodes = dt.rows({ page: 'current', search: 'applied' }).nodes().toArray();
                for (const row of nodes) {
                    const cb = row.querySelector(rowCheckboxSelector);
                    if (cb) cb.checked = checked;
                }
            });

            dt.on('draw', () => {
                if (!selectAll) return;
                const nodes = dt.rows({ page: 'current', search: 'applied' }).nodes().toArray();
                const cbs = nodes
                    .map((row) => row.querySelector(rowCheckboxSelector))
                    .filter((cb) => cb instanceof HTMLInputElement);

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
                if (!target.matches(rowCheckboxSelector)) return;
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

    const vanillaCfg = Object.assign({
        defaultSortCol: 1,
        defaultSortDir: 'asc',
        excludedSortCols: [0, headers.length - 1],
        searchCols: [1, 2],
    }, cfg.vanilla || {});

    let sortCol = vanillaCfg.defaultSortCol;
    let sortDir = vanillaCfg.defaultSortDir;

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
            const cols = Array.isArray(vanillaCfg.searchCols) ? vanillaCfg.searchCols : [];
            for (const idx of cols) {
                const v = getCellKey(row, idx).toLowerCase();
                if (v.includes(term)) return true;
            }
            return false;
        });

        filtered.sort((a, b) => {
            const ak = getCellKey(a, sortCol);
            const bk = getCellKey(b, sortCol);

            const cmp = ak.localeCompare(bk, undefined, { numeric: true, sensitivity: 'base' });
            return sortDir === 'asc' ? cmp : -cmp;
        });

        for (const row of filtered) {
            tbody.appendChild(row);
        }

        const visibleSet = new Set(filtered);
        for (const row of allRows) {
            if (!visibleSet.has(row)) {
                row.style.display = 'none';
            }
        }

        const limit = parseInt(limitSelect?.value || String(dtCfg.pageLength || 10), 10);
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
            if (Array.isArray(vanillaCfg.excludedSortCols) && vanillaCfg.excludedSortCols.includes(idx)) {
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
        const excluded = Array.isArray(vanillaCfg.excludedSortCols) && vanillaCfg.excludedSortCols.includes(idx);
        if (excluded) return;

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
                .map((row) => row.querySelector(rowCheckboxSelector))
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
                const cb = row.querySelector(rowCheckboxSelector);
                if (cb instanceof HTMLInputElement) {
                    cb.checked = checked;
                }
            }
            syncSelectAll();
        });

        tableEl.addEventListener('change', (e) => {
            const target = e.target;
            if (!(target instanceof HTMLInputElement)) return;
            if (!target.matches(rowCheckboxSelector)) return;
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
