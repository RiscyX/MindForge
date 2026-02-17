(() => {
    const initAdminTableOperations = (cfg) => {
        const tableId = cfg.tableId || 'mfTable';
        const tableEl = document.getElementById(tableId);
        if (!tableEl) return;

        const isMobileView = () => window.matchMedia('(max-width: 767.98px)').matches;

        const applyCellLabels = () => {
            const ths = tableEl.querySelectorAll('thead th');
            if (!ths.length) return;
            const labels = Array.from(ths).map((th) => (th.textContent || '').trim());
            tableEl.querySelectorAll('tbody tr').forEach((row) => {
                Array.from(row.cells).forEach((td, i) => {
                    if (i < labels.length && labels[i]) td.setAttribute('data-label', labels[i]);
                });
            });

            if (isMobileView()) {
                tableEl.style.width = '';
                tableEl.querySelectorAll('th, td').forEach((cell) => {
                    cell.style.width = '';
                });
            }
        };

        const searchInput = cfg.searchInputId ? document.getElementById(cfg.searchInputId) : null;
        const limitSelect = cfg.limitSelectId ? document.getElementById(cfg.limitSelectId) : null;

        const bulkForm = cfg.bulkFormId ? document.getElementById(cfg.bulkFormId) : null;
        const rowCheckboxSelector = cfg.rowCheckboxSelector || '.mf-row-select';

        const selectAll = cfg.selectAllCheckboxId ? document.getElementById(cfg.selectAllCheckboxId) : null;
        const selectAllLink = cfg.selectAllLinkId ? document.getElementById(cfg.selectAllLinkId) : null;

        const paginationContainer = cfg.paginationContainerId ? document.getElementById(cfg.paginationContainerId) : null;

        const isHu = (document.documentElement.lang || '').toLowerCase().startsWith('hu');

        const strings = Object.assign({
            selectAtLeastOne: isHu ? 'Jelolj ki legalabb egy elemet.' : 'Select at least one item.',
            confirmDelete: isHu ? 'Biztosan torolni szeretned a kijelolt elemeket?' : 'Are you sure you want to delete the selected items?',
            actionRequiredTitle: isHu ? 'Szukseges muvelet' : 'Action required',
            confirmDeleteTitle: isHu ? 'Torles megerositese' : 'Confirm delete',
            ok: 'OK',
            delete: isHu ? 'Torles' : 'Delete',
            cancel: isHu ? 'Megse' : 'Cancel',
        }, cfg.strings || {});

        const bulkDeleteValues = Array.isArray(cfg.bulkDeleteValues) ? cfg.bulkDeleteValues : ['delete'];

        const swalClasses = {
            container: 'mf-swal2-container',
            popup: 'mf-swal2-popup',
            title: 'mf-swal2-title',
            htmlContainer: 'mf-swal2-html',
            actions: 'mf-swal2-actions',
            confirmButton: 'btn btn-primary mf-swal2-confirm',
            cancelButton: 'btn btn-outline-light mf-swal2-cancel',
            icon: 'mf-swal2-icon',
        };

        const runSwal = (options) => {
            if (!window.Swal) {
                return null;
            }

            return window.Swal.fire(Object.assign({
                buttonsStyling: false,
                reverseButtons: true,
                customClass: swalClasses,
                showClass: { popup: 'mf-swal2-animate-in' },
                hideClass: { popup: 'mf-swal2-animate-out' },
            }, options || {}));
        };

        const detectActionIntent = (button, text) => {
            const cls = button.className || '';
            const value = (button.value || '').toLowerCase();
            const t = (text || '').toLowerCase();

            if (cls.includes('danger') || value.includes('delete') || t.includes('delete') || (t.includes('ban') && !t.includes('unban'))) {
                return 'danger';
            }
            if (cls.includes('warning') || value.includes('deactivate') || t.includes('deactivate')) {
                return 'warning';
            }
            if (cls.includes('success') || value.includes('activate') || value.includes('unban') || t.includes('activate') || t.includes('unban')) {
                return 'success';
            }
            if (cls.includes('primary')) {
                return 'primary';
            }

            return 'neutral';
        };

        const detectActionIcon = (text, value, intent) => {
            const t = (text || '').toLowerCase();
            const v = (value || '').toLowerCase();

            if (v.includes('delete') || t.includes('delete')) return 'bi-trash3';
            if (v.includes('activate') || t.includes('activate')) return 'bi-check-circle';
            if (v.includes('deactivate') || t.includes('deactivate')) return 'bi-slash-circle';
            if (v.includes('unban') || t.includes('unban')) return 'bi-person-check';
            if (v.includes('ban') || t.includes('ban')) return 'bi-person-x';
            if (t.includes('edit')) return 'bi-pencil-square';
            if (t.includes('view') || t.includes('details') || t.includes('review') || t.includes('result')) return 'bi-eye';
            if (t.includes('select')) return 'bi-check2-square';
            if (intent === 'danger') return 'bi-exclamation-triangle';
            if (intent === 'warning') return 'bi-exclamation-circle';
            if (intent === 'success') return 'bi-check2';

            return 'bi-gear';
        };

        const getInlineConfirmMessage = (element) => {
            const direct = (element.dataset.mfConfirm || '').trim();
            if (direct !== '') {
                return direct;
            }

            const onClick = element.getAttribute('onclick') || '';
            if (!onClick.includes('confirm(')) {
                return '';
            }

            const match = onClick.match(/confirm\((['"])(.*?)\1\)/);
            if (!match || typeof match[2] !== 'string') {
                return '';
            }

            return match[2]
                .replace(/\\'/g, "'")
                .replace(/\\"/g, '"')
                .replace(/\\n/g, '\n')
                .trim();
        };

        const withNativeConfirmBypass = (callback) => {
            const original = window.confirm;
            window.confirm = () => true;
            try {
                callback();
            } finally {
                window.confirm = original;
            }
        };

        const bindRowActionConfirms = () => {
            const targets = Array.from(document.querySelectorAll('td .btn.btn-sm, td button.btn.btn-sm, td a.btn.btn-sm'));
            for (const el of targets) {
                if (!(el instanceof HTMLButtonElement || el instanceof HTMLAnchorElement)) continue;
                if (el.dataset.mfConfirmBound === '1') continue;

                const confirmMessage = getInlineConfirmMessage(el);
                if (confirmMessage === '') {
                    el.dataset.mfConfirmBound = '1';
                    continue;
                }

                el.dataset.mfConfirmBound = '1';
                el.addEventListener('click', (event) => {
                    if (el.dataset.mfSwalConfirmed === '1') {
                        el.dataset.mfSwalConfirmed = '0';

                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();

                    const actionLabel = (el.textContent || '').trim();
                    const intent = detectActionIntent(el, actionLabel);
                    const confirmLabel = intent === 'danger'
                        ? strings.delete
                        : (actionLabel || strings.ok);
                    const iconClass = intent === 'danger'
                        ? 'bi-trash3'
                        : 'bi-check2';

                    const dialog = runSwal({
                        title: strings.actionRequiredTitle,
                        text: confirmMessage,
                        icon: intent === 'danger' ? 'warning' : 'question',
                        showCancelButton: true,
                        confirmButtonText: `<i class="bi ${iconClass}"></i><span>${confirmLabel}</span>`,
                        cancelButtonText: `<i class="bi bi-arrow-left"></i><span>${strings.cancel}</span>`,
                    });

                    if (!dialog) {
                        const ok = confirm(confirmMessage);
                        if (!ok) {
                            return;
                        }

                        el.dataset.mfSwalConfirmed = '1';
                        withNativeConfirmBypass(() => el.click());

                        return;
                    }

                    dialog.then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        el.dataset.mfSwalConfirmed = '1';
                        withNativeConfirmBypass(() => el.click());
                    });
                }, true);
            }
        };

        const decorateActionButtons = () => {
            const targets = Array.from(document.querySelectorAll('td .btn.btn-sm, .mf-admin-bulkbar .btn.btn-sm'));
            for (const button of targets) {
                if (!(button instanceof HTMLButtonElement || button instanceof HTMLAnchorElement)) continue;
                if (button.dataset.mfActionEnhanced === '1') continue;

                const text = (button.textContent || '').trim();
                const isSelectAll = selectAllLink && button.id === selectAllLink.id;
                const intent = isSelectAll ? 'neutral' : detectActionIntent(button, text);

                button.classList.add('mf-admin-action', `mf-admin-action--${intent}`);

                const parent = button.parentElement;
                if (parent && parent.matches('.d-flex')) {
                    parent.classList.add('mf-admin-actions');
                }

                if (!isSelectAll && !button.querySelector('i.bi')) {
                    const icon = document.createElement('i');
                    icon.className = `bi ${detectActionIcon(text, button.value, intent)}`;
                    icon.setAttribute('aria-hidden', 'true');
                    button.prepend(icon);
                }

                button.dataset.mfActionEnhanced = '1';
            }
        };

        decorateActionButtons();
        bindRowActionConfirms();

        if (bulkForm) {
            bulkForm.addEventListener('submit', (e) => {
                const submitter = e.submitter;
                if (!(submitter instanceof HTMLButtonElement)) {
                    return;
                }

                if (submitter.dataset.mfSwalConfirmed === '1') {
                    submitter.dataset.mfSwalConfirmed = '0';

                    return;
                }

                const anyChecked = !!bulkForm.querySelector(`${rowCheckboxSelector}:checked`);
                if (!anyChecked) {
                    e.preventDefault();
                    const alertPopup = runSwal({
                        title: strings.actionRequiredTitle,
                        text: strings.selectAtLeastOne,
                        icon: 'warning',
                        confirmButtonText: `<i class="bi bi-check2"></i><span>${strings.ok}</span>`,
                    });
                    if (!alertPopup) {
                        alert(strings.selectAtLeastOne);
                    }

                    return;
                }

                const isDelete = bulkDeleteValues.includes(submitter.value) || submitter.hasAttribute('data-mf-bulk-delete');
                if (isDelete) {
                    const confirmPopup = runSwal({
                        title: strings.confirmDeleteTitle,
                        text: strings.confirmDelete,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: `<i class="bi bi-trash3"></i><span>${strings.delete}</span>`,
                        cancelButtonText: `<i class="bi bi-arrow-left"></i><span>${strings.cancel}</span>`,
                    });

                    if (confirmPopup) {
                        e.preventDefault();
                        confirmPopup.then((result) => {
                            if (!result.isConfirmed) {
                                return;
                            }

                            submitter.dataset.mfSwalConfirmed = '1';
                            if (typeof bulkForm.requestSubmit === 'function') {
                                bulkForm.requestSubmit(submitter);
                            } else {
                                submitter.click();
                            }
                        });

                        return;
                    }

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

            const dtLang = isHu ? {
                emptyTable: 'Nincs elérhető adat',
                info: '_TOTAL_ bejegyzésből _START_ - _END_ megjelenítése',
                infoEmpty: 'Nincs megjeleníthető bejegyzés',
                infoFiltered: '(szűrve _MAX_ összes bejegyzésből)',
                lengthMenu: '_MENU_ bejegyzés megjelenítése',
                loadingRecords: 'Betöltés...',
                processing: 'Feldolgozás...',
                search: 'Keresés:',
                zeroRecords: 'Nincs egyező bejegyzés',
                paginate: { first: 'Első', last: 'Utolsó', next: 'Következő', previous: 'Előző' },
                aria: { sortAscending: ': növekvő rendezés', sortDescending: ': csökkenő rendezés' },
            } : {};

            const dt = $table.DataTable({
                searching: !!dtCfg.searching,
                lengthChange: !!dtCfg.lengthChange,
                pageLength: Number.isFinite(dtCfg.pageLength) ? dtCfg.pageLength : 10,
                order: dtCfg.order || [[1, 'asc']],
                columnDefs,
                autoWidth: false,
                language: dtLang,
                dom: paginationContainer ? 'rt' : dtCfg.dom,
            });

            const renderPagination = () => {
                if (!paginationContainer) return;

                const info = dt.page.info();
                const totalPages = Number(info.pages || 0);
                const currentPage = Number(info.page || 0);

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

                const current1 = currentPage + 1;

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
                    li.className = `page-item${active ? ' active' : ''}${disabled ? ' disabled' : ''}`;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `page-link${active ? ' fw-semibold' : ''}`;
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
                    btn.textContent = '...';
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
                ul.className = 'pagination pagination-sm mb-0 mf-admin-pagination';

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

            applyCellLabels();
            dt.on('draw.labels', applyCellLabels);
            dt.on('draw.mfActions', decorateActionButtons);
            decorateActionButtons();
            dt.on('draw.mfConfirms', bindRowActionConfirms);
            bindRowActionConfirms();

            return;
        }

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

            decorateActionButtons();
            bindRowActionConfirms();
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

            applyCellLabels();
            return;
        }

        applyView();
        applyCellLabels();
        decorateActionButtons();
        bindRowActionConfirms();
    };

    const parseConfig = (node) => {
        try {
            return JSON.parse(node.textContent || '{}');
        } catch {
            return {};
        }
    };

    const init = () => {
        document.querySelectorAll('script[data-mf-admin-table-config]').forEach((node) => {
            if (node.dataset.mfAdminTableBound === '1') {
                return;
            }
            node.dataset.mfAdminTableBound = '1';
            initAdminTableOperations(parseConfig(node));
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
