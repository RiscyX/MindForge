document.addEventListener('DOMContentLoaded', () => {
    const logoutLink = document.getElementById('mf-logout-link');
    const logoutForm = document.getElementById('mf-logout-form');

    if (!logoutLink || !logoutForm) return;

    logoutLink.addEventListener('click', (e) => {
        e.preventDefault();

        const isHu = document.documentElement.lang === 'hu';
        
        Swal.fire({
            title: isHu ? 'Kijelentkezés' : 'Logout',
            text: isHu ? 'Biztosan ki szeretne jelentkezni?' : 'Are you sure you want to log out?',
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: isHu
                ? '<i class="bi bi-box-arrow-right"></i><span>Kijelentkezés</span>'
                : '<i class="bi bi-box-arrow-right"></i><span>Log out</span>',
            cancelButtonText: isHu
                ? '<i class="bi bi-x-lg"></i><span>Mégse</span>'
                : '<i class="bi bi-x-lg"></i><span>Cancel</span>',
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
