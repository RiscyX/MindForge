<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DeviceLog $deviceLog
 * @var array{country:?string,city:?string,isp:?string,provider:?string,cached:bool}|null $enrichment
 */

$lang = $this->request->getParam('lang', 'en');
$this->assign('title', __('Device Log #{0}', $deviceLog->id));

$deviceTypeLabels = [
    0 => __('Mobile'),
    1 => __('Tablet'),
    2 => __('Desktop'),
];

$country = trim((string)($deviceLog->country ?? $enrichment['country'] ?? ''));
$city = trim((string)($deviceLog->city ?? $enrichment['city'] ?? ''));
$isp = trim((string)($deviceLog->isp ?? $enrichment['isp'] ?? ''));
$provider = trim((string)($enrichment['provider'] ?? ''));
$providerLabel = $provider !== '' ? strtoupper($provider) : '—';
$providerCached = (bool)($enrichment['cached'] ?? false);

$deviceType = (int)($deviceLog->device_type ?? 0);
$deviceTypeLabel = $deviceTypeLabels[$deviceType] ?? __('Unknown');
?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-1"><?= h(__('Device Log #{0}', $deviceLog->id)) ?></h1>
        <div class="mf-muted"><?= __('Detailed login and device record') ?></div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?= $this->Html->link(
            __('Back'),
            ['action' => 'index', 'lang' => $lang],
            ['class' => 'btn btn-sm btn-outline-light'],
        ) ?>
        <?= $this->Form->postLink(
            __('Delete'),
            ['action' => 'delete', $deviceLog->id, 'lang' => $lang],
            [
                'confirm' => __('Are you sure you want to delete # {0}?', $deviceLog->id),
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
                    <div><?= $deviceLog->created_at ? h($deviceLog->created_at->i18nFormat('yyyy-MM-dd HH:mm:ss')) : '—' ?></div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('User') ?></div>
                    <div>
                        <?php if ($deviceLog->user !== null) : ?>
                            <?= $this->Html->link(
                                h((string)($deviceLog->user->email ?? ('User #' . (string)$deviceLog->user_id))),
                                ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'edit', $deviceLog->user->id, 'lang' => $lang],
                                ['class' => 'link-light link-underline-opacity-0 link-underline-opacity-100-hover'],
                            ) ?>
                        <?php elseif ($deviceLog->user_id !== null) : ?>
                            <span class="mf-muted"><?= h('User #' . (string)$deviceLog->user_id) ?></span>
                        <?php else : ?>
                            <span class="mf-muted"><?= __('Guest') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('IP Address') ?></div>
                    <div><?= h((string)($deviceLog->ip_address ?? '—')) ?></div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('Device') ?></div>
                    <div><span class="mf-admin-pill"><?= h($deviceTypeLabel) ?></span></div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="mf-muted mb-1"><?= __('Country') ?></div>
                    <div><?= $country !== '' ? h($country) : '—' ?></div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="mf-muted mb-1"><?= __('City') ?></div>
                    <div><?= $city !== '' ? h($city) : '—' ?></div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="mf-muted mb-1"><?= __('ISP') ?></div>
                    <div><?= $isp !== '' ? h($isp) : '—' ?></div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('Enrichment Provider') ?></div>
                    <div><?= h($providerLabel) ?></div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="mf-muted mb-1"><?= __('Provider Response') ?></div>
                    <div>
                        <span class="mf-admin-pill <?= $providerCached ? 'border-success text-success' : 'border-warning text-warning' ?>">
                            <?= $providerCached ? __('Cache hit') : __('Live lookup') ?>
                        </span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="mf-muted mb-1"><?= __('User Agent') ?></div>
                    <pre class="mb-0 small" style="white-space:pre-wrap; word-break:break-word;"><?= h((string)($deviceLog->user_agent ?? '')) ?></pre>
                </div>
            </div>
        </div>
    </div>
</div>
