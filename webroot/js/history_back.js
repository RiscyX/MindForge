(() => {
    const bind = () => {
        document.querySelectorAll('[data-mf-history-back]').forEach((button) => {
            if (!(button instanceof HTMLElement)) {
                return;
            }
            if (button.dataset.mfHistoryBackBound === '1') {
                return;
            }
            button.dataset.mfHistoryBackBound = '1';
            button.addEventListener('click', (event) => {
                event.preventDefault();
                window.history.back();
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
