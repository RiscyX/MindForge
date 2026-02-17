(() => {
    const configNode = document.getElementById('mf-tests-catalog-filter-config');
    const root = document.getElementById('mf-category-combobox');
    const input = document.getElementById('mf-category-combobox-input');
    const panel = document.getElementById('mf-category-combobox-list');
    const hidden = document.getElementById('mf-category-id-hidden');

    if (!configNode || !root || !input || !panel || !hidden) {
        return;
    }

    let payload = {};
    try {
        payload = JSON.parse(configNode.textContent || '{}');
    } catch {
        payload = {};
    }

    const rawMap = (payload.categoryComboboxMap && typeof payload.categoryComboboxMap === 'object')
        ? payload.categoryComboboxMap
        : {};
    const selectedId = Number(payload.categoryComboboxSelectedId || hidden.value || 0);
    const allLabel = typeof payload.categoryComboboxAllLabel === 'string'
        ? payload.categoryComboboxAllLabel
        : 'All categories';
    const noResultsLabel = typeof payload.categoryComboboxNoResults === 'string'
        ? payload.categoryComboboxNoResults
        : 'No category found';
    const invalidMessage = typeof payload.categoryComboboxInvalid === 'string'
        ? payload.categoryComboboxInvalid
        : 'Please choose a category from the list.';

    const normalize = (value) => String(value || '').trim().toLocaleLowerCase();

    const options = Object.entries(rawMap)
        .map(([idRaw, labelRaw]) => ({ id: Number(idRaw), label: String(labelRaw || '').trim() }))
        .filter((row) => row.id > 0 && row.label !== '')
        .sort((a, b) => a.label.localeCompare(b.label));

    const allOption = { id: 0, label: allLabel };

    const findById = (id) => {
        if (id === 0) {
            return allOption;
        }

        return options.find((row) => row.id === id) || null;
    };

    const findByLabel = (label) => {
        const normalized = normalize(label);
        if (normalized === '' || normalized === normalize(allOption.label)) {
            return allOption;
        }

        return options.find((row) => normalize(row.label) === normalized) || null;
    };

    let visibleOptions = [];
    let activeIndex = -1;

    const closeList = () => {
        root.classList.remove('is-open');
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    };

    const openList = () => {
        root.classList.add('is-open');
        input.setAttribute('aria-expanded', 'true');
    };

    const syncInputWithSelected = () => {
        const selected = findById(Number(hidden.value || 0));
        if (!selected || selected.id === 0) {
            input.value = '';

            return;
        }

        input.value = selected.label;
    };

    const setActiveOption = (nextIndex) => {
        const optionNodes = Array.from(panel.querySelectorAll('.mf-test-combobox__option'));
        optionNodes.forEach((node) => node.classList.remove('is-active'));

        if (nextIndex < 0 || nextIndex >= optionNodes.length) {
            activeIndex = -1;

            return;
        }

        activeIndex = nextIndex;
        const node = optionNodes[activeIndex];
        node.classList.add('is-active');
        node.scrollIntoView({ block: 'nearest' });
    };

    const selectOption = (row) => {
        hidden.value = row.id > 0 ? String(row.id) : '';
        input.value = row.id > 0 ? row.label : '';
        input.setCustomValidity('');
        closeList();
    };

    const renderList = (term) => {
        const key = normalize(term);
        const filtered = key === ''
            ? [allOption, ...options]
            : options.filter((row) => normalize(row.label).includes(key));

        visibleOptions = filtered;
        panel.innerHTML = '';

        if (filtered.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'mf-test-combobox__empty';
            empty.textContent = noResultsLabel;
            panel.appendChild(empty);
            activeIndex = -1;

            return;
        }

        filtered.forEach((row) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'mf-test-combobox__option';
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            if (Number(hidden.value || 0) === row.id) {
                btn.classList.add('is-selected');
            }
            btn.addEventListener('click', () => selectOption(row));
            panel.appendChild(btn);
        });

        setActiveOption(0);
    };

    if (selectedId > 0) {
        const preselected = findById(selectedId);
        if (preselected) {
            hidden.value = String(preselected.id);
            input.value = preselected.label;
        }
    } else {
        hidden.value = '';
        input.value = '';
    }

    renderList(input.value);

    input.addEventListener('focus', () => {
        renderList(input.value);
        openList();
    });

    input.addEventListener('input', () => {
        const exact = findByLabel(input.value);
        hidden.value = exact && exact.id > 0 ? String(exact.id) : '';
        input.setCustomValidity('');
        renderList(input.value);
        openList();
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeList();
            syncInputWithSelected();

            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (!root.classList.contains('is-open')) {
                renderList(input.value);
                openList();

                return;
            }

            setActiveOption(Math.min(activeIndex + 1, visibleOptions.length - 1));

            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (!root.classList.contains('is-open')) {
                renderList(input.value);
                openList();

                return;
            }

            setActiveOption(Math.max(activeIndex - 1, 0));

            return;
        }

        if (event.key === 'Enter') {
            if (!root.classList.contains('is-open')) {
                return;
            }

            event.preventDefault();
            const row = visibleOptions[activeIndex] || visibleOptions[0] || null;
            if (row) {
                selectOption(row);
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            closeList();
            syncInputWithSelected();
        }
    });

    panel.addEventListener('wheel', (event) => {
        const canScroll = panel.scrollHeight > panel.clientHeight;
        if (!canScroll) {
            return;
        }

        event.preventDefault();
        panel.scrollTop += event.deltaY;
    }, { passive: false });

    const form = input.closest('form');
    if (form) {
        form.addEventListener('submit', (event) => {
            const rawValue = String(input.value || '').trim();
            if (rawValue === '') {
                hidden.value = '';
                input.setCustomValidity('');

                return;
            }

            const exact = findByLabel(rawValue);
            if (exact) {
                hidden.value = exact.id > 0 ? String(exact.id) : '';
                input.value = exact.id > 0 ? exact.label : '';
                input.setCustomValidity('');

                return;
            }

            input.setCustomValidity(invalidMessage);
            input.reportValidity();
            openList();
            event.preventDefault();
        });
    }
})();
