(() => {
    const filterForm = document.getElementById('mfUsersFilterForm');
    const searchInput = document.getElementById('mfUsersSearch');
    const limitSelect = document.getElementById('mfUsersLimit');

    if (filterForm) {
        let searchTimer = null;

        if (searchInput instanceof HTMLInputElement) {
            searchInput.addEventListener('input', () => {
                if (searchTimer) {
                    window.clearTimeout(searchTimer);
                }
                searchTimer = window.setTimeout(() => {
                    filterForm.submit();
                }, 320);
            });
        }

        if (limitSelect instanceof HTMLSelectElement) {
            limitSelect.addEventListener('change', () => {
                filterForm.submit();
            });
        }
    }

    const selectAllCheckbox = document.getElementById('mfUsersSelectAll');
    const selectAllLink = document.getElementById('mfUsersSelectAllLink');
    const bulkForm = document.getElementById('mfUsersBulkForm');

    if (!bulkForm || !selectAllCheckbox) {
        return;
    }

    const rowSelector = '.mf-user-select';
    const selectRequiredMessage = bulkForm.getAttribute('data-select-required') || 'Select at least one user.';
    const deleteConfirmMessage = bulkForm.getAttribute('data-delete-confirm') || 'Are you sure you want to delete the selected users?';

    const getRowCheckboxes = () => Array.from(bulkForm.querySelectorAll(rowSelector));

    const syncSelectAllState = () => {
        const boxes = getRowCheckboxes();
        if (boxes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;

            return;
        }

        const checkedCount = boxes.filter((box) => box.checked).length;
        selectAllCheckbox.checked = checkedCount === boxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
    };

    selectAllCheckbox.addEventListener('change', () => {
        const checked = selectAllCheckbox.checked;
        getRowCheckboxes().forEach((box) => {
            box.checked = checked;
        });
        syncSelectAllState();
    });

    if (selectAllLink) {
        selectAllLink.addEventListener('click', (event) => {
            event.preventDefault();
            selectAllCheckbox.checked = !selectAllCheckbox.checked;
            selectAllCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    bulkForm.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || !target.matches(rowSelector)) {
            return;
        }
        syncSelectAllState();
    });

    bulkForm.addEventListener('submit', (event) => {
        const submitter = event.submitter;
        const checkedAny = getRowCheckboxes().some((box) => box.checked);
        if (!checkedAny) {
            event.preventDefault();
            window.alert(selectRequiredMessage);

            return;
        }

        if (submitter instanceof HTMLButtonElement && submitter.value === 'delete') {
            const ok = window.confirm(deleteConfirmMessage);
            if (!ok) {
                event.preventDefault();
            }
        }
    });

    syncSelectAllState();
})();
