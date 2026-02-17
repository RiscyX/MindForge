(() => {
    const parseNumber = (value, fallback) => {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    };

    const initButton = (button) => {
        const showAfterPx = parseNumber(button.dataset.mfShowAfterPx, 240);
        const scrollBehavior = button.dataset.mfScrollBehavior || 'smooth';
        const selector = button.dataset.mfScrollContainerSelector || '';
        const primaryContainer = selector ? (document.querySelector(selector) || null) : null;

        const getWindowScrollTop = () => window.scrollY || document.documentElement.scrollTop || 0;
        const getElementScrollTop = (el) => (el && typeof el.scrollTop === 'number') ? el.scrollTop : 0;

        const getEffectiveScrollTop = () => Math.max(getWindowScrollTop(), getElementScrollTop(primaryContainer));

        const updateVisibility = () => {
            const top = getEffectiveScrollTop();
            button.classList.toggle('is-visible', top > showAfterPx);
        };

        const scrollElementToTop = (el) => {
            if (!el || getElementScrollTop(el) <= 0) {
                return false;
            }

            try {
                el.scrollTo({ top: 0, behavior: scrollBehavior });
            } catch {
                el.scrollTop = 0;
            }

            return true;
        };

        window.addEventListener('scroll', updateVisibility, { passive: true });
        if (primaryContainer) {
            primaryContainer.addEventListener('scroll', updateVisibility, { passive: true });
        }

        const pollId = window.setInterval(updateVisibility, 350);
        window.setTimeout(() => window.clearInterval(pollId), 12000);

        button.addEventListener('click', (event) => {
            event.preventDefault();

            if (scrollElementToTop(primaryContainer)) {
                return;
            }
            window.scrollTo({ top: 0, behavior: scrollBehavior });
        });

        updateVisibility();
    };

    const init = () => {
        document.querySelectorAll('[data-mf-scroll-top="1"]').forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }
            if (button.dataset.mfScrollTopBound === '1') {
                return;
            }

            button.dataset.mfScrollTopBound = '1';
            initButton(button);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
