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

<script>
(() => {
    const buttonId = <?= json_encode($buttonId, JSON_UNESCAPED_SLASHES) ?>;
    const showAfterPx = <?= json_encode($showAfterPx, JSON_UNESCAPED_SLASHES) ?>;
    const scrollBehavior = <?= json_encode($scrollBehavior, JSON_UNESCAPED_SLASHES) ?>;
    const scrollContainerSelector = <?= json_encode($scrollContainerSelector, JSON_UNESCAPED_SLASHES) ?>;

    const button = document.getElementById(buttonId);
    if (!button) return;

    const primaryContainer = scrollContainerSelector ? (document.querySelector(scrollContainerSelector) || null) : null;

    const getWindowScrollTop = () => window.scrollY || document.documentElement.scrollTop || 0;
    const getElementScrollTop = (el) => (el && typeof el.scrollTop === 'number') ? el.scrollTop : 0;

    const getEffectiveScrollTop = () => {
        return Math.max(getWindowScrollTop(), getElementScrollTop(primaryContainer));
    };

    const updateVisibility = () => {
        const top = getEffectiveScrollTop();
        button.classList.toggle('is-visible', top > showAfterPx);
    };

    const scrollElementToTop = (el) => {
        if (!el) return false;
        if (getElementScrollTop(el) <= 0) return false;

        try {
            el.scrollTo({ top: 0, behavior: scrollBehavior });
        } catch {
            el.scrollTop = 0;
        }

        return true;
    };

    const bind = () => {
        // Listen to both: depending on layout, either window or a container actually scrolls.
        window.addEventListener('scroll', updateVisibility, { passive: true });
        if (primaryContainer) {
            primaryContainer.addEventListener('scroll', updateVisibility, { passive: true });
        }

        // Fallback: some layouts/browsers may not reliably fire scroll on the expected node.
        // Keep it lightweight.
        const pollId = window.setInterval(updateVisibility, 350);
        window.setTimeout(() => window.clearInterval(pollId), 12000);

        button.addEventListener('click', (e) => {
            e.preventDefault();

            // Prefer scrolling the inner container if it exists and is scrolled.
            if (scrollElementToTop(primaryContainer)) return;
            window.scrollTo({ top: 0, behavior: scrollBehavior });
        });

        updateVisibility();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
</script>
