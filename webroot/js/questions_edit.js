(() => {
    const container = document.getElementById('answersEditor');
    const template = document.getElementById('answerRowTemplate');
    const addButton = document.getElementById('addAnswerRow');
    if (!container || !template || !addButton) {
        return;
    }

    let index = container.querySelectorAll('.answer-row').length;

    const updateRemoveButtons = () => {
        const rows = container.querySelectorAll('.answer-row');
        rows.forEach((row) => {
            const button = row.querySelector('.remove-answer-row');
            if (button) {
                button.disabled = rows.length <= 1;
            }
        });
    };

    addButton.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(index));
        container.insertAdjacentHTML('beforeend', html);
        index += 1;
        updateRemoveButtons();
    });

    container.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement) || !target.classList.contains('remove-answer-row')) {
            return;
        }

        const row = target.closest('.answer-row');
        if (!row) {
            return;
        }

        if (container.querySelectorAll('.answer-row').length <= 1) {
            return;
        }

        row.remove();
        updateRemoveButtons();
    });

    updateRemoveButtons();
})();
