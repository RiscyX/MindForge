<?php
/**
 * MindForge custom error layout – dark theme, Bootstrap 5.
 *
 * @var \App\View\AppView $this
 */
use Cake\I18n\I18n;

$locale = I18n::getLocale();
$htmlLang = str_starts_with($locale, 'hu') ? 'hu' : 'en';
?>
<!DOCTYPE html>
<html data-bs-theme="dark" lang="<?= $htmlLang ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MindForge – <?= $this->fetch('title') ?: __('Error') ?></title>

    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css('vendor/fonts/solway/solway.css') ?>
    <?= $this->Html->css('vendor/bootstrap/bootstrap.min.css') ?>
    <?= $this->Html->css('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>
    <?= $this->Html->css('index.css?v=33') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="mf-auth d-flex flex-column min-vh-100">
    <!-- Minimal navbar (brand only) -->
    <nav class="navbar mf-navbar py-2">
        <div class="container justify-content-center">
            <a class="navbar-brand mf-brand" href="<?= $this->Url->build('/') ?>">
                <?= $this->Html->image('favicon-128x128.png', [
                    'alt' => 'MindForge',
                    'class' => 'mf-logo',
                ]) ?>
            </a>
        </div>
    </nav>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center px-3 py-5">
        <?= $this->fetch('content') ?>
    </main>

    <div class="mf-flash-stack" data-mf-flash-stack>
        <?= $this->Flash->render() ?>
    </div>

    <?= $this->Html->script('vendor/bootstrap/bootstrap.bundle.min.js') ?>
    <?= $this->Html->script('flash.js?v=1') ?>
    <?= $this->Html->script('history_back.js?v=1') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
