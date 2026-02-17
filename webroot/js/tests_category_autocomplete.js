(function () {
    const init = () => {
        const root = document.getElementById('category-combobox');
        const input = document.getElementById('category-combobox-input');
        const panel = document.getElementById('category-combobox-list');
        const hidden = document.getElementById('category-id-hidden');
        if (!root || !input || !panel || !hidden) {
            return;
        }

        const rawMap = (typeof categoryComboboxMap === 'object' && categoryComboboxMap) ? categoryComboboxMap : {};
        const selectedId = Number(categoryComboboxSelectedId || hidden.value || 0);
        const noResultsLabel = typeof categoryComboboxNoResults === 'string'
            ? categoryComboboxNoResults
            : 'No category found';
        const invalidMessage = typeof categoryComboboxInvalid === 'string'
            ? categoryComboboxInvalid
            : 'Please choose a category from the list.';

        const normalize = (value) => String(value || '').trim().toLocaleLowerCase();

        const options = Object.entries(rawMap)
            .map(([idRaw, labelRaw]) => ({ id: Number(idRaw), label: String(labelRaw || '').trim() }))
            .filter((row) => row.id > 0 && row.label !== '');

        const findById = (id) => options.find((row) => row.id === id) || null;
        const findByLabel = (label) => options.find((row) => normalize(row.label) === normalize(label)) || null;

        const closeList = () => {
            root.classList.remove('is-open');
            input.setAttribute('aria-expanded', 'false');
        };

        const openList = () => {
            root.classList.add('is-open');
            input.setAttribute('aria-expanded', 'true');
        };

        const selectOption = (row) => {
            hidden.value = String(row.id);
            input.value = row.label;
            input.setCustomValidity('');
            closeList();
        };

        const renderList = (term) => {
            const key = normalize(term);
            const filtered = key === '' ? options : options.filter((row) => normalize(row.label).includes(key));

            panel.innerHTML = '';
            if (filtered.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'mf-test-combobox__empty';
                empty.textContent = noResultsLabel;
                panel.appendChild(empty);

                return;
            }

            for (const row of filtered) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'mf-test-combobox__option';
                btn.dataset.id = String(row.id);
                btn.textContent = row.label;
                if (Number(hidden.value || 0) === row.id) {
                    btn.classList.add('is-selected');
                }
                btn.addEventListener('click', () => {
                    selectOption(row);
                });
                panel.appendChild(btn);
            }
        };

        if (selectedId > 0) {
            const preselected = findById(selectedId);
            if (preselected) {
                hidden.value = String(preselected.id);
                input.value = preselected.label;
            }
        }

        renderList('');

        input.addEventListener('focus', () => {
            renderList(input.value);
            openList();
        });

        input.addEventListener('input', () => {
            const exact = findByLabel(input.value);
            hidden.value = exact ? String(exact.id) : '';
            input.setCustomValidity('');
            renderList(input.value);
            openList();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeList();

                return;
            }
            if (event.key === 'Enter') {
                const first = panel.querySelector('.mf-test-combobox__option');
                if (first) {
                    event.preventDefault();
                    first.click();
                }
            }
        });

        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                closeList();
            }
        });

        const form = input.closest('form');
        if (form) {
            form.addEventListener('submit', (event) => {
                const exact = findByLabel(input.value);
                if (exact) {
                    hidden.value = String(exact.id);
                    input.setCustomValidity('');

                    return;
                }

                if (String(hidden.value).trim() === '') {
                    input.setCustomValidity(invalidMessage);
                    input.reportValidity();
                    openList();
                    event.preventDefault();
                }
            });
        }

    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);

        return;
    }

    init();
})();
