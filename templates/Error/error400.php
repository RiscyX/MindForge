<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 */
use Cake\Core\Configure;

$this->layout = 'error';

if (Configure::read('debug')) :
    $this->layout = 'dev_error';

    $this->assign('title', $message);
    $this->assign('templateName', 'error400.php');

    $this->start('file');
    echo $this->element('auto_table_warning');
    $this->end();

    // In debug mode fall through to the default CakePHP rendering
    return;
endif;

$this->assign('title', '404');

$code = $code ?? 404;
?>
<div class="text-center mf-error-page">
    <div class="mf-error-glow" aria-hidden="true"></div>

    <h1 class="mf-error-code"><?= (int)$code ?></h1>
    <h2 class="mf-error-heading mb-3"><?= __('Page Not Found') ?></h2>
    <p class="mf-error-text mb-4">
        <?= __('The page you\'re looking for doesn\'t exist or has been moved.') ?>
    </p>

    <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="<?= $this->Url->build('/') ?>" class="btn btn-primary px-4 py-2">
            <i class="bi bi-house-door me-1"></i> <?= __('Go Home') ?>
        </a>
        <button type="button" data-mf-history-back class="btn btn-outline-light px-4 py-2">
            <i class="bi bi-arrow-left me-1"></i> <?= __('Go Back') ?>
        </button>
    </div>
</div>
