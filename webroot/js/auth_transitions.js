(() => {
    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;

    const isAuthPage = document.body.classList.contains('mf-auth-page');
    if (!isAuthPage || prefersReducedMotion) {
        return;
    }

    const main = document.querySelector('body > main');
    if (!main) {
        return;
    }

    const DURATION_MS = 1000;
    let isTransitioning = false;

    const shouldHandleClick = (event, anchor) => {
        if (!anchor || !(anchor instanceof HTMLAnchorElement)) return false;

        // Ignore modified clicks/new tab behaviors
        if (event.defaultPrevented) return false;
        if (event.button !== 0) return false;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
        if (anchor.target && anchor.target !== '_self') return false;
        if (anchor.hasAttribute('download')) return false;

        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#')) return false;

        // Only same-origin navigation
        let url;
        try {
            url = new URL(anchor.href, window.location.href);
        } catch {
            return false;
        }

        if (url.origin !== window.location.origin) return false;

        // Only transitions between auth pages
        // Supports /en/login, /hu/register, /en/forgot-password, /en/reset-password?token=...
        const path = url.pathname.replace(/\/+$/, '');
        return /\/(en|hu)\/(login|register|forgot-password|reset-password)$/.test(path);
    };

    const playEnter = () => {
        main.classList.remove('mf-auth-leave');
        main.classList.remove('mf-auth-scroll');

        // Trigger transition from the CSS initial hidden state.
        requestAnimationFrame(() => {
            main.classList.add('mf-auth-ready');
        });

        // Only enable scrolling after the fade-in completes.
        window.setTimeout(() => {
            main.classList.add('mf-auth-scroll');
        }, DURATION_MS + 20);
    };

    const playLeaveAndNavigate = (href) => {
        if (isTransitioning) {
            return;
        }

        isTransitioning = true;
        main.classList.remove('mf-auth-scroll');
        main.classList.add('mf-auth-leave');

        const navigate = () => {
            window.location.href = href;
        };

        // Navigate after the fade-out completes.
        window.setTimeout(navigate, DURATION_MS);
    };

    // Enter animation on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', playEnter, { once: true });
    } else {
        playEnter();
    }

    // If the page is restored from bfcache, ensure it's visible.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            isTransitioning = false;
            main.classList.remove('mf-auth-leave');
            main.classList.add('mf-auth-ready');
            main.classList.add('mf-auth-scroll');
        }
    });

    // Intercept clicks within the main content
    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const anchor = target.closest('a');
        if (!shouldHandleClick(event, anchor)) return;

        event.preventDefault();
        playLeaveAndNavigate(anchor.href);
    });
})();
