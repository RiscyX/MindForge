<?php
/**
 * @var \App\View\AppView $this
 */

use App\Model\Entity\Role;

$lang = $this->request->getParam('lang', 'en');
$currentPrefix = $this->request->getParam('prefix');
$currentController = $this->request->getParam('controller');
$currentAction = $this->request->getParam('action');

$identity = $this->request->getAttribute('identity');
$roleId = $identity ? (int)$identity->get('role_id') : null;
$isAdmin = $roleId === Role::ADMIN;
$isCreator = $roleId === Role::CREATOR;

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

$renderNav = function () use (
    $isAdmin,
    $isCreator,
    $isRoute,
    $usersRoute,
    $categoriesRoute,
    $difficultiesRoute,
    $testsRoute,
    $questionsRoute,
    $answersRoute,
    $languagesRoute,
    $deviceLogsRoute,
    $aiRequestsRoute
): void {
    ?>

    <?php if ($isAdmin) : ?>
        <div class="mf-admin-sidebar__section">
            <div class="mf-admin-sidebar__label"><?= __('Management') ?></div>
            <nav class="mf-admin-nav" aria-label="<?= h(__('Admin navigation')) ?>">
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
            <nav class="mf-admin-nav" aria-label="<?= h(__('System navigation')) ?>">
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
    <?php elseif ($isCreator) : ?>
        <div class="mf-admin-sidebar__section">
            <div class="mf-admin-sidebar__label"><?= __('Quiz Tools') ?></div>
            <nav class="mf-admin-nav" aria-label="<?= h(__('Quiz tools navigation')) ?>">
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
                    __('Quizzes'),
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
            </nav>
        </div>
    <?php endif; ?>

    <?php
};
?>

<aside class="mf-admin-sidebar d-none d-lg-flex flex-column">
    <?php $renderNav(); ?>
</aside>

<div
    class="offcanvas offcanvas-start mf-admin-offcanvas d-lg-none"
    tabindex="-1"
    id="mfAdminNav"
    aria-labelledby="mfAdminNavLabel"
>
    <div class="offcanvas-header">
        <div class="offcanvas-title" id="mfAdminNavLabel"><?= __('Navigation') ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?= h(__('Close')) ?>"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php $renderNav(); ?>
    </div>
</div>
