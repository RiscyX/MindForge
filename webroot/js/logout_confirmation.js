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
            background: '#212529',
            color: '#f8f9fa',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: isHu ? 'Kijelentkezés' : 'Yes, log out',
            cancelButtonText: isHu ? 'Mégse' : 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                logoutForm.submit();
            }
        });
    });
});
