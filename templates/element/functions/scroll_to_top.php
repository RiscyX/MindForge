<?php
/**
 * Reusable scroll-to-top floating button.
 *
 * Usage (later):
 *   <?= $this->element('functions/scroll_to_top', ['config' => ['showAfterPx' => 320]]) ?>
 *
 * @var \App\View\AppView $this
 * @var array $config
 */

$config = $config ?? [];

$buttonId = (string)($config['buttonId'] ?? 'mfScrollToTop');
$showAfterPx = (int)($config['showAfterPx'] ?? 240);
$scrollBehavior = (string)($config['scrollBehavior'] ?? 'smooth');

$bottom = (string)($config['bottom'] ?? '1.25rem');
$right = (string)($config['right'] ?? '1.25rem');
$zIndex = (int)($config['zIndex'] ?? 1050);

// Optional: when provided, listens to scroll on the element matched by selector.
// Example: '#myScrollableDiv'.
$scrollContainerSelector = $config['scrollContainerSelector'] ?? null;
$scrollContainerSelector = is_string($scrollContainerSelector) && $scrollContainerSelector !== '' ? $scrollContainerSelector : null;
?>

<button
    id="<?= h($buttonId) ?>"
    type="button"
    class="mf-scroll-top"
    data-mf-scroll-top="1"
    data-mf-show-after-px="<?= h((string)$showAfterPx) ?>"
    data-mf-scroll-behavior="<?= h($scrollBehavior) ?>"
    <?= $scrollContainerSelector !== null ? 'data-mf-scroll-container-selector="' . h($scrollContainerSelector) . '"' : '' ?>
    aria-label="<?= h(__('Scroll to top')) ?>"
    title="<?= h(__('Scroll to top')) ?>"
>
    <i class="bi bi-arrow-up" aria-hidden="true"></i>
</button>

<style>
.mf-scroll-top {
    position: fixed;
    bottom: <?= h($bottom) ?>;
    right: <?= h($right) ?>;
    z-index: <?= h((string)$zIndex) ?>;

    width: 44px;
    height: 44px;
    border-radius: 999px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(10, 10, 14, 0.85);
    color: rgba(255, 255, 255, 0.92);

    box-shadow:
        0 10px 25px rgba(0, 0, 0, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.06);

    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateY(10px);
    transition: opacity 140ms ease, transform 140ms ease, visibility 140ms ease;
}

.mf-scroll-top:hover {
    background: rgba(20, 20, 28, 0.92);
    color: rgba(255, 255, 255, 1);
}

.mf-scroll-top:active {
    transform: translateY(12px);
}

.mf-scroll-top.is-visible {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transform: translateY(0);
}

.mf-scroll-top i {
    font-size: 1.1rem;
    line-height: 1;
}

@media (max-width: 576px) {
    .mf-scroll-top {
        width: 42px;
        height: 42px;
        bottom: 1rem;
        right: 1rem;
    }
}
</style>

<?= $this->Html->script('scroll_to_top') ?>
