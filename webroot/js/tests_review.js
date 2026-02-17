(() => {
    const steps = Array.from(document.querySelectorAll('[data-mf-step]'));
    const prevBtn = document.querySelector('[data-mf-prev]');
    const nextBtn = document.querySelector('[data-mf-next]');
    if (!steps.length || !prevBtn || !nextBtn) {
        return;
    }

    let idx = 0;
    const render = () => {
        steps.forEach((el, i) => {
            el.hidden = i !== idx;
        });
        prevBtn.disabled = idx === 0;
        nextBtn.disabled = idx === steps.length - 1;
        steps[idx].scrollIntoView({ block: 'start', behavior: 'smooth' });
    };

    prevBtn.addEventListener('click', () => {
        if (idx > 0) {
            idx -= 1;
            render();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (idx < steps.length - 1) {
            idx += 1;
            render();
        }
    });

    const csrfSource = document.querySelector('[data-mf-csrf-token]');
    const csrfToken = csrfSource ? (csrfSource.getAttribute('data-mf-csrf-token') || '') : '';
    const explainButtons = Array.from(document.querySelectorAll('[data-mf-ai-explain]'));
    explainButtons.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.getAttribute('data-url');
            const targetSelector = btn.getAttribute('data-target');
            if (!url || !targetSelector) return;
            const target = document.querySelector(targetSelector);
            if (!target) return;

            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = btn.getAttribute('data-loading-label') || 'Generating...';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify({ force: false }),
                    credentials: 'same-origin',
                });

                const payload = await response.json();
                if (!response.ok || !payload || !payload.success) {
                    throw new Error(payload && payload.message ? payload.message : 'Request failed');
                }

                target.hidden = false;
                target.textContent = String(payload.explanation || '');
            } catch (err) {
                target.hidden = false;
                target.textContent = String(err);
            } finally {
                btn.disabled = false;
                btn.textContent = original;
            }
        });
    });

    render();
})();
