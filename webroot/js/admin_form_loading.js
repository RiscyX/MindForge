/**
 * Admin Form Loading State
 *
 * Automatically handles submit loading state for all admin forms:
 * - Disables the submit button on submit
 * - Replaces button text with a spinner + "Saving..." label
 * - Re-enables if the page returns with validation errors (form is still present)
 *
 * Targets any <form> that contains a [type=submit] or <button type="submit">
 * with the class `mf-admin-btn` and optionally `data-loading-text` attribute.
 *
 * Usage:
 *   <button type="submit" class="btn btn-primary mf-admin-btn" data-loading-text="Saving…">Save</button>
 *   If data-loading-text is omitted the script auto-generates one from the button's current label.
 *
 * Skip a button by adding data-no-loading:
 *   <button type="submit" class="btn btn-primary mf-admin-btn" data-no-loading>Save</button>
 */
(() => {
    'use strict';

    const isHu = (document.documentElement.lang || '').toLowerCase().startsWith('hu');

    const SPINNER_HTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>';

    /**
     * Build a loading label from the original button text.
     * e.g. "Save" → "Saving…"  |  "Create" → "Creating…"  |  "Delete" → "Deleting…"
     */
    const makeLoadingText = (btn) => {
        const custom = (btn.dataset.loadingText || '').trim();
        if (custom) return custom;

        const text = (btn.textContent || '').trim();
        if (!text) return isHu ? 'Feldolgozás…' : 'Processing…';

        // Common verb mappings
        const hu = { 'Mentés': 'Mentés…', 'Létrehozás': 'Mentés…', 'Küldés': 'Küldés…' };
        const en = { 'Save': 'Saving…', 'Create': 'Creating…', 'Send': 'Sending…', 'Submit': 'Submitting…', 'Apply': 'Applying…', 'Update': 'Updating…', 'Delete': 'Deleting…' };
        const map = isHu ? hu : en;

        return map[text] || (isHu ? `${text}…` : `${text}…`);
    };

    const applyLoadingState = (btn) => {
        if (btn.dataset.noLoading !== undefined) return;
        btn.disabled = true;
        btn.setAttribute('aria-disabled', 'true');
        btn.dataset.mfOriginalHtml = btn.innerHTML;
        btn.innerHTML = SPINNER_HTML + makeLoadingText(btn);
    };

    const restoreButton = (btn) => {
        if (!btn.dataset.mfOriginalHtml) return;
        btn.disabled = false;
        btn.removeAttribute('aria-disabled');
        btn.innerHTML = btn.dataset.mfOriginalHtml;
        delete btn.dataset.mfOriginalHtml;
    };

    /**
     * Find the primary submit button in a form.
     * Prefers [type=submit].mf-admin-btn, falls back to first [type=submit].
     */
    const findSubmitBtn = (form) => {
        return (
            form.querySelector('button[type="submit"].mf-admin-btn:not([data-no-loading])') ||
            form.querySelector('input[type="submit"].mf-admin-btn:not([data-no-loading])')
        );
    };

    const bindForm = (form) => {
        if (form.dataset.mfLoadingBound === '1') return;
        form.dataset.mfLoadingBound = '1';

        form.addEventListener('submit', (e) => {
            const btn = e.submitter instanceof HTMLButtonElement
                ? e.submitter
                : findSubmitBtn(form);

            if (!btn) return;
            if (btn.dataset.noLoading !== undefined) return;

            // Skip bulk-action forms (they have their own SweetAlert confirm flow)
            if (form.id && form.id.toLowerCase().includes('bulk')) return;

            // Skip CakePHP postLink hidden forms (they have id like "post_NNN" or contain _method=DELETE/POST with no submit btn class)
            // postLink forms have no mf-admin-btn submit button — they just have a hidden submit
            if (!findSubmitBtn(form)) return;

            // For postLink-generated confirm actions (onclick="return confirm(...)"), skip
            const onclickAttr = (btn.getAttribute('onclick') || '');
            if (onclickAttr.includes('confirm(')) return;

            applyLoadingState(btn);
        });

        // Restore on pageshow (browser back/forward cache)
        window.addEventListener('pageshow', (ev) => {
            if (ev.persisted) {
                const btn = findSubmitBtn(form);
                if (btn) restoreButton(btn);
            }
        });
    };

    const bindAll = () => {
        document.querySelectorAll('form').forEach(bindForm);
    };

    // Initial bind
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindAll);
    } else {
        bindAll();
    }

    // Re-bind for dynamically inserted forms (e.g. after SweetAlert confirm injects a hidden form)
    const observer = new MutationObserver(() => {
        document.querySelectorAll('form:not([data-mf-loading-bound="1"])').forEach(bindForm);
    });
    observer.observe(document.body, { childList: true, subtree: true });
})();
