<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;

$this->layout = 'error';

if (Configure::read('debug')) :
    $this->layout = 'dev_error';

    $this->assign('title', $message);
    $this->assign('templateName', 'error500.php');

    $this->start('file');
?>
<?php if ($error instanceof Error) : ?>
    <?php $file = $error->getFile() ?>
    <?php $line = $error->getLine() ?>
    <strong>Error in: </strong>
    <?= $this->Html->link(sprintf('%s, line %s', Debugger::trimPath($file), $line), Debugger::editorUrl($file, $line)); ?>
<?php endif; ?>
<?php
    echo $this->element('auto_table_warning');

    $this->end();

    // In debug mode fall through to the default CakePHP rendering
    return;
endif;

$this->assign('title', '500');
?>
<div class="text-center mf-error-page">
    <div class="mf-error-glow mf-error-glow--danger" aria-hidden="true"></div>

    <h1 class="mf-error-code">500</h1>
    <h2 class="mf-error-heading mb-3"><?= __('Something Went Wrong') ?></h2>
    <p class="mf-error-text mb-4">
        <?= __('An unexpected error occurred. Our team has been notified. Please try again later.') ?>
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
