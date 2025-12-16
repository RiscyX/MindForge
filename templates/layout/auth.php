<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\I18n\I18n;

$cakeDescription = 'MindForge';
?>
<!DOCTYPE html>
<html data-bs-theme="dark" lang="<?= I18n::getLocale() === 'hu_HU' ? 'hu' : 'en'; ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= h($cakeDescription) ?>:
        <?= $this->fetch('title') ?>
    </title>
    <?= $this->Html->meta('icon') ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Solway:wght@300;400;500;700&display=swap" rel="stylesheet">

    <?= $this->Html->css('https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css') ?>
    <?= $this->Html->css('index.css?v=3') ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="mf-auth">
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>

    <?= $this->Html->script('https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
