<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\I18n\I18n;

$cakeDescription = 'MindForge';

$request = $this->getRequest();
$isAuthPage = $request->getParam('controller') === 'Users'
    && in_array($request->getParam('action'), ['login', 'register', 'forgotPassword', 'resetPassword'], true);
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

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <?= $this->Html->meta('icon') ?>

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700&display=swap" rel="stylesheet">

    <?= $this->Html->css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css') ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?= $this->Html->css('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css') ?>
    <?= $this->Html->css('index.css?v=35') ?>

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
            <div class="d-flex mf-admin-layout">
                <?= $this->element('admin_sidebar') ?>

                <section class="flex-grow-1 mf-admin-main" id="mf-admin-main">
                    <div class="container-fluid px-3 px-lg-4 py-4">
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

    <?= $this->Html->script('https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js') ?>
    <?= $this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js') ?>
    <?= $this->Html->script('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js') ?>
    <?= $this->Html->script('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js') ?>
    <?= $this->Html->script('https://cdn.jsdelivr.net/npm/sweetalert2@11') ?>
    <?= $this->Html->script('flash.js?v=1') ?>
    <?= $this->Html->script('logout_confirmation.js?v=3') ?>
    <?= $this->fetch('script') ?>
    <?= $this->fetch('scriptBottom') ?>
</body>
</html>
