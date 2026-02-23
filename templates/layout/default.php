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
<html data-bs-theme="dark" lang="<?= I18n::getLocale() === 'hu_HU' ? 'hu' : 'en'; ?>">
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
    <?= $this->Html->css('index.css?v=33') ?>

    <?php if ($isAuthPage) : ?>
        <?= $this->Html->script('auth_page_flag') ?>
        <?= $this->Html->css('auth_transitions.css?v=1') ?>
    <?php endif; ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="mf-auth d-flex flex-column min-vh-100<?= $isAuthPage ? ' mf-auth-page' : '' ?>">
    <?= $this->element('navbar') ?>
    <main class="flex-grow-1 d-flex flex-column">
        <?php
        $fullBleed = trim((string)$this->fetch('mfFullBleed')) !== '';
        $containerClass = $fullBleed ? 'container-fluid p-0' : 'container py-4 intro-y';
        ?>
        <div class="<?= h($containerClass) ?>">
            <?= $this->fetch('content') ?>
        </div>
    </main>

    <div class="mf-flash-stack" data-mf-flash-stack>
        <?= $this->Flash->render() ?>
    </div>

    <?= $this->element('functions/scroll_to_top', [
        'config' => [
            'showAfterPx' => 240,
            'zIndex' => 2000,
        ],
    ]) ?>

    <?= $this->Html->script('vendor/bootstrap/bootstrap.bundle.min.js') ?>
    <?= $this->Html->script('vendor/sweetalert2/sweetalert2.all.min.js') ?>
    <?= $this->element('js_translations') ?>
    <?= $this->Html->script('flash.js?v=1') ?>
    <?= $this->Html->script('logout_confirmation.js?v=3') ?>
    <?= $this->Html->script('history_back.js?v=1') ?>
    <?php if ($isAuthPage) : ?>
        <?= $this->Html->script('auth_transitions.js?v=1') ?>
    <?php endif; ?>
    <?= $this->fetch('script') ?>
    <?= $this->fetch('scriptBottom') ?>
</body>
</html>
