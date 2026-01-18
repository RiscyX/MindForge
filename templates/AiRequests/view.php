<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AiRequest $aiRequest
 */

$lang = $this->request->getParam('lang', 'en');

$this->assign('title', __('AI Request #{0}', $aiRequest->id));

$status = (string)($aiRequest->status ?? '');
$statusClass = 'mf-admin-pill';
if ($status === 'success') {
    $statusClass .= ' border-success text-success';
} elseif ($status !== '') {
    $statusClass .= ' border-warning text-warning';
}
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= h(__('AI Request #{0}', $aiRequest->id)) ?></h1>
        <div class="mf-muted"><?= h((string)($aiRequest->type ?? '')) ?></div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?= $this->Html->link(
            __('Back'),
            ['action' => 'index', 'lang' => $lang],
            ['class' => 'btn btn-sm btn-outline-light'],
        ) ?>
        <?= $this->Form->postLink(
            __('Delete'),
            ['action' => 'delete', $aiRequest->id, 'lang' => $lang],
            [
                'confirm' => __('Are you sure you want to delete # {0}?', $aiRequest->id),
                'class' => 'btn btn-sm btn-outline-danger',
            ],
        ) ?>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-12">
        <div class="mf-admin-card p-3">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('Created') ?></div>
                    <div><?= $aiRequest->created_at ? h($aiRequest->created_at->i18nFormat('yyyy-MM-dd HH:mm:ss')) : '—' ?></div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('Status') ?></div>
                    <div><span class="<?= h($statusClass) ?>"><?= h($status !== '' ? $status : __('Unknown')) ?></span></div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('User') ?></div>
                    <div>
                        <?php if ($aiRequest->user !== null) : ?>
                            <?= $this->Html->link(
                                h((string)($aiRequest->user->email ?? ('User #' . (string)$aiRequest->user_id))),
                                ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $aiRequest->user->id, 'lang' => $lang],
                                ['class' => 'link-light link-underline-opacity-0 link-underline-opacity-100-hover'],
                            ) ?>
                        <?php elseif ($aiRequest->user_id !== null) : ?>
                            <span class="mf-muted"><?= h('User #' . (string)$aiRequest->user_id) ?></span>
                        <?php else : ?>
                            <span class="mf-muted"><?= __('System') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('Language') ?></div>
                    <div>
                        <?php if ($aiRequest->language !== null) : ?>
                            <?= $this->Html->link(
                                h((string)($aiRequest->language->code ?? $aiRequest->language->name ?? '—')),
                                ['prefix' => false, 'controller' => 'Languages', 'action' => 'edit', $aiRequest->language->id, 'lang' => $lang],
                                ['class' => 'link-light link-underline-opacity-0 link-underline-opacity-100-hover'],
                            ) ?>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('Source') ?></div>
                    <div><?= h((string)($aiRequest->source_medium ?? '—')) ?></div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('Reference') ?></div>
                    <div><?= h((string)($aiRequest->source_reference ?? '—')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="mf-admin-card p-3 h-100">
            <div class="mf-muted mb-2"><?= __('Input Payload') ?></div>
            <pre class="mb-0 small" style="white-space:pre-wrap; word-break:break-word; max-height:360px; overflow:auto;"><?= h((string)($aiRequest->input_payload ?? '')) ?></pre>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="mf-admin-card p-3 h-100">
            <div class="mf-muted mb-2"><?= __('Output Payload') ?></div>
            <pre class="mb-0 small" style="white-space:pre-wrap; word-break:break-word; max-height:360px; overflow:auto;"><?= h((string)($aiRequest->output_payload ?? '')) ?></pre>
        </div>
    </div>
</div>