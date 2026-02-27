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
$isCreator = $isLoggedIn && (int)$identity->get('role_id') === Role::CREATOR;

$isOnAdminDashboard = $currentPrefix === 'Admin'
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
        'prefix' => 'QuizCreator',
        'controller' => 'Dashboard',
        'action' => 'index',
        'lang' => $lang,
    ];

$isDashboardActive = $isAdmin
    ? $isOnAdminDashboard
    : ($currentPrefix === 'QuizCreator' && $currentController === 'Dashboard' && $currentAction === 'index');

$quizzesUrl = [
    'prefix' => false,
    'controller' => 'Tests',
    'action' => 'index',
    'lang' => $lang,
];

$isQuizzesActive = ($currentPrefix === null || $currentPrefix === '')
    && $currentController === 'Tests'
    && $currentAction === 'index';

$favoritesUrl = [
    'prefix' => false,
    'controller' => 'Tests',
    'action' => 'favorites',
    'lang' => $lang,
];

$isFavoritesActive = ($currentPrefix === null || $currentPrefix === '')
    && $currentController === 'Tests'
    && $currentAction === 'favorites';

$trainingUrl = [
    'prefix' => false,
    'controller' => 'Training',
    'action' => 'index',
    'lang' => $lang,
];

$isTrainingActive = ($currentPrefix === null || $currentPrefix === '')
    && $currentController === 'Training'
    && $currentAction === 'index';

$profileUrl = [
    'prefix' => false,
    'controller' => 'Users',
    'action' => 'profile',
    'lang' => $lang,
];

$isProfileActive = ($currentPrefix === null || $currentPrefix === '')
    && $currentController === 'Users'
    && $currentAction === 'profile';

$pass = (array)$this->request->getParam('pass', []);
$queryParams = (array)$this->request->getQueryParams();
$routePrefix = $currentPrefix === null || $currentPrefix === '' ? false : $currentPrefix;

$buildLangRoute = static function (string $newLang) use ($routePrefix, $currentController, $currentAction, $pass, $queryParams): array {
    $route = [
        'prefix' => $routePrefix,
        'controller' => $currentController,
        'action' => $currentAction,
        'lang' => $newLang,
    ];

    foreach ($pass as $p) {
        $route[] = $p;
    }

    if ($queryParams) {
        $route['?'] = $queryParams;
    }

    return $route;
};

$langRouteEn = $buildLangRoute('en');
$langRouteHu = $buildLangRoute('hu');

?>

<nav class="navbar navbar-expand-lg navbar-dark mf-navbar" data-mf-navbar>
    <div class="container-fluid px-3 px-lg-5">
        <!-- Brand -->
        <?php
        $brandUrl = ['controller' => 'Pages', 'action' => 'display', 'home', 'lang' => $lang];
        if ($isAdmin) {
            $brandUrl = ['prefix' => false, 'controller' => 'Pages', 'action' => 'redirectToAdmin', '?' => ['lang' => $lang]];
        } elseif ($isCreator) {
            $brandUrl = ['prefix' => false, 'controller' => 'Pages', 'action' => 'redirectToQuizCreator', '?' => ['lang' => $lang]];
        }
        ?>
        <a class="navbar-brand mf-brand" href="<?= $this->Url->build($brandUrl) ?>">
            <?= $this->Html->image('favicon-128x128.png', [
                'alt' => 'MindForge',
                'class' => 'mf-logo',
            ]) ?>
        </a>

        <!-- Hamburger Toggle -->
        <div class="d-flex align-items-center gap-2 ms-auto d-lg-none">
            <!-- Mobile-only language selector (always visible) -->
            <div class="dropdown mf-mobile-lang">
                <button class="btn btn-sm btn-link nav-link dropdown-toggle px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= strtoupper(h($lang)) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item<?= $lang === 'en' ? ' active' : '' ?>" href="<?= $this->Url->build($langRouteEn) ?>">
                            <?= __('English') ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item<?= $lang === 'hu' ? ' active' : '' ?>" href="<?= $this->Url->build($langRouteHu) ?>">
                            <?= __('Hungarian') ?>
                        </a>
                    </li>
                </ul>
            </div>

            <button class="navbar-toggler" type="button" aria-controls="navbarNav" aria-expanded="false"
                aria-label="<?= __('Toggle navigation') ?>" data-mf-navbar-toggle>
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <!-- Navbar Links -->
        <div class="navbar-collapse" id="navbarNav" data-mf-navbar-menu>
            <ul class="navbar-nav ms-auto">
                <?php if (!$isLoggedIn) : ?>
                    <!-- Guest -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isQuizzesActive ? ' active' : '' ?>"
                           href="<?= $this->Url->build($quizzesUrl) ?>">
                            <?= __('Quizzes') ?>
                        </a>
                    </li>
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

                    <li class="nav-item dropdown d-none d-lg-block">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= strtoupper(h($lang)) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item<?= $lang === 'en' ? ' active' : '' ?>" href="<?= $this->Url->build($langRouteEn) ?>">
                                    <?= __('English') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item<?= $lang === 'hu' ? ' active' : '' ?>" href="<?= $this->Url->build($langRouteHu) ?>">
                                    <?= __('Hungarian') ?>
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php else : ?>
                    <!-- Logged in -->
                    <?php if ($isAdmin || $isCreator) : ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $isDashboardActive ? ' active' : '' ?>"
                               href="<?= $this->Url->build($dashboardUrl) ?>">
                                <?= __('Dashboard') ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link<?= $isQuizzesActive ? ' active' : '' ?>"
                           href="<?= $this->Url->build($quizzesUrl) ?>">
                            <?= __('Quizzes') ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link<?= $isFavoritesActive ? ' active' : '' ?>"
                           href="<?= $this->Url->build($favoritesUrl) ?>">
                            <?= __('Favorites') ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link<?= $isTrainingActive ? ' active' : '' ?>"
                           href="<?= $this->Url->build($trainingUrl) ?>">
                            <i class="bi bi-infinity me-1"></i><?= __('Training') ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link<?= $isProfileActive ? ' active' : '' ?>"
                           href="<?= $this->Url->build($profileUrl) ?>">
                            <?= __('My Profile') ?>
                        </a>
                    </li>

                    <li class="nav-item dropdown d-none d-lg-block">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= strtoupper(h($lang)) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item<?= $lang === 'en' ? ' active' : '' ?>" href="<?= $this->Url->build($langRouteEn) ?>">
                                    <?= __('English') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item<?= $lang === 'hu' ? ' active' : '' ?>" href="<?= $this->Url->build($langRouteHu) ?>">
                                    <?= __('Hungarian') ?>
                                </a>
                            </li>
                        </ul>
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

            <?php if ($isLoggedIn && ($isAdmin || $isCreator)) : ?>
            <!-- Admin sidebar nav merged into mobile navbar -->
            <div class="d-lg-none mf-navbar-admin-nav" data-mf-admin-mobile-nav>
                <hr class="my-2" style="border-color: rgba(var(--mf-text-rgb), 0.10);">
                <?= $this->element('admin_sidebar_links') ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</nav>

<?= $this->Html->script('navbar_mobile') ?>
