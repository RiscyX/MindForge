(() => {
    const form = document.querySelector('[data-mf-login-form]');
    if (!form) {
        return;
    }

    const emailInput = form.querySelector('[data-mf-email]');
    const passwordInput = form.querySelector('[data-mf-password]');

    const setEmailValidity = () => {
        if (!emailInput) {
            return;
        }

        if (!emailInput.value) {
            emailInput.setCustomValidity('');
            return;
        }

        const ok = /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(emailInput.value);
        emailInput.setCustomValidity(ok ? '' : 'Please enter a valid email address.');
    };

    const setPasswordValidity = () => {
        if (!passwordInput) {
            return;
        }

        // Keep this minimal: login should accept any non-empty password.
        if (!passwordInput.value) {
            passwordInput.setCustomValidity('');
            return;
        }

        passwordInput.setCustomValidity('');
    };

    emailInput?.addEventListener('input', () => {
        setEmailValidity();
    });

    passwordInput?.addEventListener('input', () => {
        setPasswordValidity();
    });

    form.addEventListener('submit', (event) => {
        setEmailValidity();
        setPasswordValidity();

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add('was-validated');
    });
})();
