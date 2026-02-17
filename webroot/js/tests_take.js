(() => {
    const steps = Array.from(document.querySelectorAll('[data-mf-step]'));
    const prevBtn = document.querySelector('[data-mf-prev]');
    const nextBtn = document.querySelector('[data-mf-next]');
    const submitBtn = document.querySelector('[data-mf-submit]');
    const currentEl = document.querySelector('[data-mf-runner-current]');
    const totalEl = document.querySelector('[data-mf-runner-total]');
    const barFill = document.querySelector('[data-mf-runner-progress]');
    const abortTrigger = document.getElementById('mf-abort-attempt-trigger');
    const abortForm = document.getElementById('mf-abort-attempt-form');

    if (!steps.length || !prevBtn || !nextBtn || !submitBtn) {
        return;
    }

    let idx = 0;

    const render = () => {
        steps.forEach((el, i) => {
            el.hidden = i !== idx;
        });

        prevBtn.disabled = idx === 0;

        const isLast = idx === steps.length - 1;
        nextBtn.hidden = isLast;
        submitBtn.hidden = !isLast;

        if (currentEl) {
            currentEl.textContent = String(idx + 1);
        }
        if (totalEl) {
            totalEl.textContent = String(steps.length);
        }
        if (barFill) {
            const pct = steps.length ? Math.round(((idx + 1) / steps.length) * 100) : 0;
            barFill.style.width = `${pct}%`;
        }

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

    if (abortTrigger && abortForm && window.Swal) {
        abortTrigger.addEventListener('click', () => {
            Swal.fire({
                title: abortTrigger.dataset.mfTitle || 'Abort attempt',
                text: abortTrigger.dataset.mfText || 'Are you sure you want to abort this attempt?',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: `<i class="bi bi-x-octagon"></i><span>${abortTrigger.dataset.mfConfirm || 'Abort attempt'}</span>`,
                cancelButtonText: `<i class="bi bi-arrow-left"></i><span>${abortTrigger.dataset.mfCancel || 'Cancel'}</span>`,
                buttonsStyling: false,
                customClass: {
                    container: 'mf-swal2-container',
                    popup: 'mf-swal2-popup',
                    title: 'mf-swal2-title',
                    htmlContainer: 'mf-swal2-html',
                    actions: 'mf-swal2-actions',
                    confirmButton: 'btn btn-primary mf-swal2-confirm',
                    cancelButton: 'btn btn-outline-light mf-swal2-cancel',
                    icon: 'mf-swal2-icon',
                },
                showClass: {
                    popup: 'mf-swal2-animate-in',
                },
                hideClass: {
                    popup: 'mf-swal2-animate-out',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    abortForm.submit();
                }
            });
        });
    }

    render();
})();
