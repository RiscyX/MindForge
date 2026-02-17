(() => {
    const selectAllCheckbox = document.getElementById('mfDeviceLogsSelectAll');
    const selectAllLink = document.getElementById('mfDeviceLogsSelectAllLink');
    const bulkForm = document.getElementById('mfDeviceLogsBulkForm');

    if (!bulkForm || !selectAllCheckbox) {
        return;
    }

    const rowSelector = '.mf-row-select';

    const getRowCheckboxes = () => Array.from(bulkForm.querySelectorAll(rowSelector));

    const syncSelectAllState = () => {
        const boxes = getRowCheckboxes();
        if (boxes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;

            return;
        }

        const checked = boxes.filter((box) => box.checked).length;
        selectAllCheckbox.checked = checked === boxes.length;
        selectAllCheckbox.indeterminate = checked > 0 && checked < boxes.length;
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

    syncSelectAllState();
})();
