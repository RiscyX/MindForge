<?php
/**
 * Admin/Creator sidebar navigation links — mobile-only fragment.
 * Rendered inside the navbar-collapse on small screens (d-lg-none).
 *
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

$usersRoute = ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index', 'lang' => $lang];
$categoriesRoute = ['prefix' => $isAdmin ? 'Admin' : false, 'controller' => 'Categories', 'action' => 'index', 'lang' => $lang];
$difficultiesRoute = ['prefix' => $isAdmin ? 'Admin' : false, 'controller' => 'Difficulties', 'action' => 'index', 'lang' => $lang];
$languagesRoute = ['prefix' => $isAdmin ? 'Admin' : false, 'controller' => 'Languages', 'action' => 'index', 'lang' => $lang];
$testsRoute = ['prefix' => $isAdmin ? 'Admin' : false, 'controller' => 'Tests', 'action' => 'index', 'lang' => $lang];
$deviceLogsRoute = ['prefix' => 'Admin', 'controller' => 'DeviceLogs', 'action' => 'index', 'lang' => $lang];
$aiRequestsRoute = ['prefix' => 'Admin', 'controller' => 'AiRequests', 'action' => 'index', 'lang' => $lang];
?>

<?php if ($isAdmin) : ?>
    <div class="mf-admin-sidebar__section">
        <div class="mf-admin-sidebar__label"><?= __('Management') ?></div>
        <nav class="mf-admin-nav" aria-label="<?= h(__('Admin navigation')) ?>">
            <?= $this->Html->link('<i class="bi bi-people-fill" aria-hidden="true"></i><span>' . h(__('Users')) . '</span>', $usersRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($usersRoute) ? ' active' : ''), 'escape' => false]) ?>
            <?= $this->Html->link('<i class="bi bi-tag-fill" aria-hidden="true"></i><span>' . h(__('Categories')) . '</span>', $categoriesRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($categoriesRoute) ? ' active' : ''), 'escape' => false]) ?>
            <?= $this->Html->link('<i class="bi bi-bar-chart-steps" aria-hidden="true"></i><span>' . h(__('Difficulties')) . '</span>', $difficultiesRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($difficultiesRoute) ? ' active' : ''), 'escape' => false]) ?>
            <?= $this->Html->link('<i class="bi bi-journal-check" aria-hidden="true"></i><span>' . h(__('Tests')) . '</span>', $testsRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($testsRoute) ? ' active' : ''), 'escape' => false]) ?>
            <?= $this->Html->link('<i class="bi bi-translate" aria-hidden="true"></i><span>' . h(__('Languages')) . '</span>', $languagesRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($languagesRoute) ? ' active' : ''), 'escape' => false]) ?>
        </nav>
    </div>

    <div class="mf-admin-sidebar__section mt-2">
        <div class="mf-admin-sidebar__label"><?= __('System') ?></div>
        <nav class="mf-admin-nav" aria-label="<?= h(__('System navigation')) ?>">
            <?= $this->Html->link('<i class="bi bi-phone" aria-hidden="true"></i><span>' . h(__('Device Logs')) . '</span>', $deviceLogsRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($deviceLogsRoute) ? ' active' : ''), 'escape' => false]) ?>
            <?= $this->Html->link('<i class="bi bi-cpu" aria-hidden="true"></i><span>' . h(__('AI Requests')) . '</span>', $aiRequestsRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($aiRequestsRoute) ? ' active' : ''), 'escape' => false]) ?>
        </nav>
    </div>
<?php elseif ($isCreator) : ?>
    <div class="mf-admin-sidebar__section">
        <div class="mf-admin-sidebar__label"><?= __('Quiz Tools') ?></div>
        <nav class="mf-admin-nav" aria-label="<?= h(__('Quiz tools navigation')) ?>">
            <?= $this->Html->link('<i class="bi bi-tag-fill" aria-hidden="true"></i><span>' . h(__('Categories')) . '</span>', $categoriesRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($categoriesRoute) ? ' active' : ''), 'escape' => false]) ?>
            <?= $this->Html->link('<i class="bi bi-bar-chart-steps" aria-hidden="true"></i><span>' . h(__('Difficulties')) . '</span>', $difficultiesRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($difficultiesRoute) ? ' active' : ''), 'escape' => false]) ?>
            <?= $this->Html->link('<i class="bi bi-journal-check" aria-hidden="true"></i><span>' . h(__('Quizzes')) . '</span>', $testsRoute, ['class' => 'mf-admin-nav__link' . ($isRoute($testsRoute) ? ' active' : ''), 'escape' => false]) ?>
        </nav>
    </div>
<?php endif; ?>
