<?php
/**
 * @var \App\View\AppView $this
 */

$lang = $this->request->getParam('lang', 'en');
$currentPrefix = $this->request->getParam('prefix');
$currentController = $this->request->getParam('controller');
$currentAction = $this->request->getParam('action');

$isRoute = static function (array $route) use ($currentPrefix, $currentController, $currentAction): bool {
    $prefix = $route['prefix'] ?? null;
    $controller = $route['controller'] ?? null;
    $action = $route['action'] ?? null;

    if ($controller !== null && (string)$controller !== (string)$currentController) {
        return false;
    }
    if ($action !== null && (string)$action !== (string)$currentAction) {
        return false;
    }

    if ($prefix === false || $prefix === null || $prefix === '') {
        return $currentPrefix === null || $currentPrefix === '';
    }

    return (string)$currentPrefix === (string)$prefix;
};

$usersRoute = [
    'prefix' => 'Admin',
    'controller' => 'Users',
    'action' => 'index',
    'lang' => $lang,
];
$categoriesRoute = [
    'prefix' => false,
    'controller' => 'Categories',
    'action' => 'index',
    'lang' => $lang,
];
$difficultiesRoute = [
    'prefix' => false,
    'controller' => 'Difficulties',
    'action' => 'index',
    'lang' => $lang,
];
$languagesRoute = [
    'prefix' => false,
    'controller' => 'Languages',
    'action' => 'index',
    'lang' => $lang,
];

$testsRoute = [
    'prefix' => false,
    'controller' => 'Tests',
    'action' => 'index',
    'lang' => $lang,
];
$questionsRoute = [
    'prefix' => false,
    'controller' => 'Questions',
    'action' => 'index',
    'lang' => $lang,
];
$answersRoute = [
    'prefix' => false,
    'controller' => 'Answers',
    'action' => 'index',
    'lang' => $lang,
];

$deviceLogsRoute = [
    'prefix' => false,
    'controller' => 'DeviceLogs',
    'action' => 'index',
    'lang' => $lang,
];

$aiRequestsRoute = [
    'prefix' => false,
    'controller' => 'AiRequests',
    'action' => 'index',
    'lang' => $lang,
];
?>

<aside class="mf-admin-sidebar d-none d-lg-flex flex-column">
    <div class="mf-admin-sidebar__section">
        <div class="mf-admin-sidebar__label"><?= __('Management') ?></div>
        <nav class="mf-admin-nav">
            <?= $this->Html->link(
                __('Users'),
                $usersRoute,
                ['class' => 'mf-admin-nav__link' . ($isRoute($usersRoute) ? ' active' : '')],
            ) ?>
            <?= $this->Html->link(
                __('Categories'),
                $categoriesRoute,
                ['class' => 'mf-admin-nav__link' . ($isRoute($categoriesRoute) ? ' active' : '')],
            ) ?>
            <?= $this->Html->link(
                __('Difficulties'),
                $difficultiesRoute,
                ['class' => 'mf-admin-nav__link' . ($isRoute($difficultiesRoute) ? ' active' : '')],
            ) ?>
                <?= $this->Html->link(
                    __('Tests'),
                    $testsRoute,
                    ['class' => 'mf-admin-nav__link' . ($isRoute($testsRoute) ? ' active' : '')],
                ) ?>
                <?= $this->Html->link(
                    __('Questions'),
                    $questionsRoute,
                    ['class' => 'mf-admin-nav__link' . ($isRoute($questionsRoute) ? ' active' : '')],
                ) ?>
                <?= $this->Html->link(
                    __('Answers'),
                    $answersRoute,
                    ['class' => 'mf-admin-nav__link' . ($isRoute($answersRoute) ? ' active' : '')],
                ) ?>
            <?= $this->Html->link(
                __('Languages'),
                $languagesRoute,
                ['class' => 'mf-admin-nav__link' . ($isRoute($languagesRoute) ? ' active' : '')],
            ) ?>
        </nav>
    </div>

    <div class="mf-admin-sidebar__section mt-2">
        <div class="mf-admin-sidebar__label"><?= __('System') ?></div>
        <nav class="mf-admin-nav">
            <?= $this->Html->link(
                __('Device Logs'),
                $deviceLogsRoute,
                ['class' => 'mf-admin-nav__link' . ($isRoute($deviceLogsRoute) ? ' active' : '')],
            ) ?>
            <?= $this->Html->link(
                __('AI Requests'),
                $aiRequestsRoute,
                ['class' => 'mf-admin-nav__link' . ($isRoute($aiRequestsRoute) ? ' active' : '')],
            ) ?>
        </nav>
    </div>
</aside>
