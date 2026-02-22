document.addEventListener('DOMContentLoaded', () => {
    const logoutLink = document.getElementById('mf-logout-link');
    const logoutForm = document.getElementById('mf-logout-form');

    if (!logoutLink || !logoutForm) return;

    logoutLink.addEventListener('click', (e) => {
        e.preventDefault();

        const t = (key) => (window.MF && window.MF.t) ? window.MF.t(key) : key;

        Swal.fire({
            title: t('logoutTitle'),
            text: t('logoutText'),
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: `<i class="bi bi-box-arrow-right"></i><span>${t('logoutConfirm')}</span>`,
            cancelButtonText: `<i class="bi bi-x-lg"></i><span>${t('cancel')}</span>`,
            buttonsStyling: false,
            customClass: {
                container: 'mf-swal2-container',
                popup: 'mf-swal2-popup',
                title: 'mf-swal2-title',
                htmlContainer: 'mf-swal2-html',
                actions: 'mf-swal2-actions',
                confirmButton: 'btn btn-primary mf-swal2-confirm',
                cancelButton: 'btn btn-outline-light mf-swal2-cancel',
                icon: 'mf-swal2-icon'
            },
            showClass: {
                popup: 'mf-swal2-animate-in'
            },
            hideClass: {
                popup: 'mf-swal2-animate-out'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                logoutForm.submit();
            }
        });
    });
});
