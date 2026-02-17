(() => {
    const nav = document.querySelector('[data-mf-navbar]');
    if (!nav) {
        return;
    }

    const toggle = nav.querySelector('[data-mf-navbar-toggle]');
    const menu = nav.querySelector('[data-mf-navbar-menu]');
    if (!toggle || !menu) {
        return;
    }

    const OPEN_CLASS = 'mf-nav-open';
    const CLOSING_CLASS = 'mf-nav-closing';
    const MOBILE_QUERY = '(max-width: 991.98px)';
    const isMobile = () => window.matchMedia(MOBILE_QUERY).matches;

    const main = document.querySelector('main');

    const DURATION_MS = 260;
    let closeTimer = null;

    const updateMainOffset = () => {
        if (!main) {
            return;
        }

        if (!isMobile()) {
            main.style.paddingTop = '';

            return;
        }

        const menuVisible = nav.classList.contains(OPEN_CLASS) || nav.classList.contains(CLOSING_CLASS);
        if (!menuVisible) {
            main.style.paddingTop = '';

            return;
        }

        const height = Math.ceil(menu.getBoundingClientRect().height);
        main.style.paddingTop = height > 0 ? `${height}px` : '';
    };

    const setExpanded = (expanded) => {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    };

    const close = () => {
        if (!nav.classList.contains(OPEN_CLASS)) {
            return;
        }

        nav.classList.remove(OPEN_CLASS);
        nav.classList.add(CLOSING_CLASS);
        setExpanded(false);

        updateMainOffset();

        if (closeTimer) {
            window.clearTimeout(closeTimer);
        }
        closeTimer = window.setTimeout(() => {
            nav.classList.remove(CLOSING_CLASS);
            updateMainOffset();
            closeTimer = null;
        }, DURATION_MS);
    };

    const open = () => {
        if (nav.classList.contains(OPEN_CLASS)) {
            return;
        }
        nav.classList.remove(CLOSING_CLASS);
        nav.classList.add(OPEN_CLASS);
        setExpanded(true);

        window.requestAnimationFrame(updateMainOffset);
    };

    toggle.addEventListener('click', () => {
        if (!isMobile()) {
            return;
        }
        if (nav.classList.contains(OPEN_CLASS)) {
            close();
        } else {
            open();
        }
    });

    menu.addEventListener('click', (event) => {
        const a = event.target.closest('a');
        if (!a) {
            return;
        }

        const toggleType = a.getAttribute('data-bs-toggle');
        if (toggleType === 'dropdown') {
            return;
        }

        const href = (a.getAttribute('href') || '').trim();
        if (href === '' || href === '#') {
            return;
        }

        close();
    });

    document.addEventListener('click', (event) => {
        if (!isMobile()) {
            return;
        }
        if (!nav.classList.contains(OPEN_CLASS)) {
            return;
        }
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }
        if (nav.contains(target)) {
            return;
        }
        close();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        close();
    });

    window.addEventListener('resize', () => {
        if (!isMobile()) {
            close();
        }
        updateMainOffset();
    });
})();
