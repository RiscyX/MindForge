(() => {
    const form = document.querySelector('[data-mf-reset-form]');
    if (!form) {
        return;
    }

    const passwordInput = form.querySelector('[data-mf-password]');
    const confirmInput = form.querySelector('[data-mf-confirm]');

    const setConfirmValidity = () => {
        if (!passwordInput || !confirmInput) {
            return;
        }

        if (!confirmInput.value) {
            confirmInput.setCustomValidity('');
            return;
        }

        if (confirmInput.value !== passwordInput.value) {
            confirmInput.setCustomValidity('Passwords do not match.');
            return;
        }

        confirmInput.setCustomValidity('');
    };

    const setPasswordValidity = () => {
        if (!passwordInput) {
            return;
        }

        if (!passwordInput.value) {
            passwordInput.setCustomValidity('');
            return;
        }

        if (passwordInput.value.length < 8) {
            passwordInput.setCustomValidity('Password must be at least 8 characters.');
            return;
        }

        passwordInput.setCustomValidity('');
    };

    passwordInput?.addEventListener('input', () => {
        setPasswordValidity();
        setConfirmValidity();
    });

    confirmInput?.addEventListener('input', () => {
        setConfirmValidity();
    });

    form.addEventListener('submit', (event) => {
        setPasswordValidity();
        setConfirmValidity();

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add('was-validated');
    });
})();
