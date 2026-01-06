<?php
/**
 * Navbar element
 *
 * @var \App\View\AppView $this
 */
use App\Model\Entity\Role;

$lang = $this->request->getParam('lang', 'en');
$currentController = $this->request->getParam('controller');
$currentPrefix = $this->request->getParam('prefix');
$currentAction = $this->request->getParam('action');
$identity = $this->request->getAttribute('identity');
$isLoggedIn = $identity !== null;
$isAdmin = $isLoggedIn
    && (int)$identity->get('role_id') === Role::ADMIN;

$isOnAdminDashboard = $currentPrefix === 'Admin'
    && $currentController === 'Dashboard'
    && $currentAction === 'index';

$isOnUserDashboard = ($currentPrefix === null || $currentPrefix === '')
    && $currentController === 'Dashboard'
    && $currentAction === 'index';

$dashboardUrl = $isAdmin
    ? [
        'prefix' => 'Admin',
        'controller' => 'Dashboard',
        'action' => 'index',
        'lang' => $lang,
    ]
    : [
        'controller' => 'Dashboard',
        'action' => 'index',
        'lang' => $lang,
    ];

$isDashboardActive = $isAdmin ? $isOnAdminDashboard : $isOnUserDashboard;

?>

<nav class="navbar navbar-expand-lg navbar-dark mf-navbar" data-mf-navbar>
    <div class="container-fluid px-3 px-lg-5">
        <!-- Brand -->
        <a class="navbar-brand mf-brand" href="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'home', 'lang' => $lang]) ?>">
            <?= $this->Html->image('favicon-128x128.png', [
                'alt' => 'MindForge',
                'class' => 'mf-logo',
            ]) ?>
        </a>

        <!-- Hamburger Toggle -->
        <button class="navbar-toggler" type="button" aria-controls="navbarNav" aria-expanded="false"
            aria-label="<?= __('Toggle navigation') ?>" data-mf-navbar-toggle>
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="navbar-collapse" id="navbarNav" data-mf-navbar-menu>
            <ul class="navbar-nav ms-auto">
                <?php if (!$isLoggedIn) : ?>
                    <!-- Guest -->
                    <li class="nav-item">
                        <a class="nav-link<?= $currentAction === 'login' ? ' active' : '' ?>"
                           href="<?= $this->Url->build([
                               'controller' => 'Users',
                               'action' => 'login',
                               'lang' => $lang,
                           ]) ?>">
                            <?= __('Log In') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $currentAction === 'register' ? ' active' : '' ?>"
                           href="<?= $this->Url->build([
                               'controller' => 'Users',
                               'action' => 'register',
                               'lang' => $lang,
                           ]) ?>">
                            <?= __('Sign Up') ?>
                        </a>
                    </li>

                <?php else : ?>
                    <!-- Logged in -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboardActive ? ' active' : '' ?>"
                           href="<?= $this->Url->build($dashboardUrl) ?>">
                            <?= __('Dashboard') ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link<?= $currentAction === 'profile' && $this->request->getParam('controller') === 'Users' ? ' active' : '' ?>"
                           href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'profile', 'lang' => $lang]) ?>">
                            <?= __('My Profile') ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link" id="mf-logout-link"><?= __('Logout') ?></a>
                        <?= $this->Form->create(null, [
                            'url' => ['prefix' => false, 'controller' => 'Users', 'action' => 'logout', 'lang' => $lang],
                            'style' => 'display:none;',
                            'id' => 'mf-logout-form',
                        ]) ?>
                        <?= $this->Form->end() ?>
                    </li>
                <?php endif; ?>
            </ul>

        </div>
    </div>
</nav>

<script>
(() => {
    const nav = document.querySelector('[data-mf-navbar]');
    if (!nav) return;

    const toggle = nav.querySelector('[data-mf-navbar-toggle]');
    const menu = nav.querySelector('[data-mf-navbar-menu]');
    if (!toggle || !menu) return;

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
        if (!nav.classList.contains(OPEN_CLASS)) return;

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
        if (nav.classList.contains(OPEN_CLASS)) return;
        nav.classList.remove(CLOSING_CLASS);
        nav.classList.add(OPEN_CLASS);
        setExpanded(true);

        window.requestAnimationFrame(updateMainOffset);
    };

    toggle.addEventListener('click', () => {
        if (!isMobile()) return;
        if (nav.classList.contains(OPEN_CLASS)) close();
        else open();
    });

    // Close when a link inside the menu is clicked.
    menu.addEventListener('click', (event) => {
        const a = event.target.closest('a');
        if (!a) return;
        close();
    });

    // Close on outside click (mobile only)
    document.addEventListener('click', (event) => {
        if (!isMobile()) return;
        if (!nav.classList.contains(OPEN_CLASS)) return;
        const target = event.target;
        if (!(target instanceof Element)) return;
        if (nav.contains(target)) return;
        close();
    });

    // Close on Escape
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        close();
    });

    // Reset on desktop
    window.addEventListener('resize', () => {
        if (!isMobile()) close();
        updateMainOffset();
    });
})();
</script>
