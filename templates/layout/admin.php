<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\I18n\I18n;

$cakeDescription = 'MindForge';

$request = $this->getRequest();
$isAuthPage = $request->getParam('controller') === 'Users'
    && in_array($request->getParam('action'), ['login', 'register', 'forgotPassword', 'resetPassword'], true);
$hideAdminSidebar = $this->fetch('hideAdminSidebar') === '1';
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="<?= I18n::getLocale() === 'hu_HU' ? 'hu' : 'en'; ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= h($cakeDescription) ?> -
        <?= $this->fetch('title') ?>
    </title>

    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css('vendor/fonts/solway/solway.css') ?>
    <?= $this->Html->css('vendor/bootstrap/bootstrap.min.css') ?>
    <?= $this->Html->css('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>
    <?= $this->Html->css('vendor/datatables/dataTables.bootstrap5.min.css') ?>
    <?= $this->Html->css('index.css?v=39') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<?php
$bodyClass = 'mf-auth d-flex flex-column';
if ($isAuthPage) {
    $bodyClass .= ' vh-100 overflow-hidden mf-auth-page';
} else {
    $bodyClass .= ' mf-admin-body';
}
?>
<body class="<?= h($bodyClass) ?>">
    <a class="visually-hidden-focusable" href="#mf-admin-main"><?= __('Skip to content') ?></a>
    <?= $this->element('navbar') ?>

    <main class="flex-grow-1 d-flex flex-column mf-admin-wrapper">
        <div class="container-fluid px-0 mf-admin-shell">
            <div class="d-flex mf-admin-layout<?= $hideAdminSidebar ? ' w-100' : '' ?>">
                <?php if (!$hideAdminSidebar) : ?>
                    <?= $this->element('admin_sidebar') ?>
                <?php endif; ?>

                <section class="flex-grow-1 mf-admin-main" id="mf-admin-main">
                    <div class="<?= $hideAdminSidebar ? 'container py-4' : 'container-fluid px-3 px-lg-4 py-4' ?>">
                        <?= $this->fetch('content') ?>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <?= $this->element('functions/scroll_to_top', [
        'config' => [
            'scrollContainerSelector' => '.mf-admin-main',
            'showAfterPx' => 120,
            'zIndex' => 2000,
        ],
    ]) ?>

    <div class="mf-flash-stack" data-mf-flash-stack aria-live="polite">
        <?= $this->Flash->render() ?>
    </div>

    <?= $this->Html->script('vendor/bootstrap/bootstrap.bundle.min.js') ?>
    <?= $this->Html->script('vendor/jquery/jquery-3.7.1.min.js') ?>
    <?= $this->Html->script('vendor/datatables/jquery.dataTables.min.js') ?>
    <?= $this->Html->script('vendor/datatables/dataTables.bootstrap5.min.js') ?>
    <?= $this->Html->script('vendor/sweetalert2/sweetalert2.all.min.js') ?>
    <?= $this->element('js_translations') ?>
    <?= $this->Html->script('flash.js?v=1') ?>
    <?= $this->Html->script('logout_confirmation.js?v=3') ?>
    <?= $this->Html->script('admin_form_loading.js?v=1') ?>
    <?= $this->fetch('script') ?>
    <?= $this->fetch('scriptBottom') ?>
</body>
</html>
