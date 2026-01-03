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
    <?= $this->Html->css('index.css?v=9') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="mf-auth d-flex flex-column min-vh-100<?= $isAuthPage ? ' mf-auth-page' : '' ?>">
    <?= $this->element('navbar') ?>

    <main class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid px-0 mf-admin-shell">
            <div class="d-flex mf-admin-layout">
                <?= $this->element('admin_sidebar') ?>

                <section class="flex-grow-1 mf-admin-main">
                    <div class="container-fluid px-3 px-lg-4 py-4">
                        <?= $this->fetch('content') ?>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <div class="mf-flash-stack" data-mf-flash-stack>
        <?= $this->Flash->render() ?>
    </div>

    <?= $this->Html->script('https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
