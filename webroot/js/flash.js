/**
 * MindForge – Flash message auto-dismiss.
 *
 * - Click to dismiss immediately.
 * - Auto-dismiss after 5 seconds with a fade-out animation.
 */
(() => {
    const DISMISS_MS = 5000;
    const FADE_MS = 320;

    const dismiss = (el) => {
        if (el.dataset.mfDismissed) return;
        el.dataset.mfDismissed = '1';
        el.style.transition = `opacity ${FADE_MS}ms ease, transform ${FADE_MS}ms ease`;
        el.style.opacity = '0';
        el.style.transform = 'translateY(8px)';
        setTimeout(() => el.remove(), FADE_MS);
    };

    const init = () => {
        document.querySelectorAll('[data-mf-flash]').forEach((el) => {
            // Click to dismiss
            el.addEventListener('click', () => dismiss(el));

            // Auto-dismiss
            setTimeout(() => dismiss(el), DISMISS_MS);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
