(() => {
    const form = document.querySelector('[data-mf-register-form]');
    if (!form) {
        return;
    }

    const emailInput = form.querySelector('[data-mf-email]');
    const passwordInput = form.querySelector('[data-mf-password]');
    const confirmInput = form.querySelector('[data-mf-confirm]');

    const strengthWrap = form.querySelector('[data-mf-strength-wrap]');
    const strengthBar = form.querySelector('[data-mf-strength-bar]');

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

    const rgb = (r, g, b) => `rgb(${r}, ${g}, ${b})`;

    const lerp = (a, b, t) => a + (b - a) * t;

    const strengthScore = (password) => {
        if (!password) {
            return 0;
        }

        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSymbol = /[^A-Za-z0-9]/.test(password);
        const variety = [hasLower, hasUpper, hasNumber, hasSymbol].filter(Boolean).length;

        // Width should be able to hit 100% based on length alone,
        // otherwise users typing only letters will never see a full bar.
        const lengthScore = (clamp(password.length, 0, 16) / 16) * 100;

        // Small bonus for variety (helps it fill a bit sooner).
        const varietyBonus = clamp(variety - 1, 0, 3) * 5;

        return Math.round(clamp(lengthScore + varietyBonus, 0, 100));
    };

    const setStrength = (password) => {
        if (!strengthWrap || !strengthBar) {
            return;
        }

        if (!password) {
            strengthWrap.classList.add('d-none');
            strengthBar.style.width = '0%';
            return;
        }

        strengthWrap.classList.remove('d-none');

        const score = strengthScore(password);
        strengthBar.style.width = `${score}%`;

        const t = score / 100;
        const r = Math.round(lerp(230, 46, t));
        const g = Math.round(lerp(62, 204, t));
        const b = Math.round(lerp(62, 113, t));
        strengthBar.style.backgroundColor = rgb(r, g, b);
    };

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

    const setEmailValidity = () => {
        if (!emailInput) {
            return;
        }

        if (!emailInput.value) {
            emailInput.setCustomValidity('');
            return;
        }

        const ok = /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(emailInput.value);
        emailInput.setCustomValidity(ok ? '' : ((window.MF && window.MF.t) ? window.MF.t('invalidEmail') : 'Please enter a valid email address.'));
    };

    passwordInput?.addEventListener('input', () => {
        setPasswordValidity();
        setConfirmValidity();
        setStrength(passwordInput.value);
    });

    confirmInput?.addEventListener('input', () => {
        setConfirmValidity();
    });

    emailInput?.addEventListener('input', () => {
        setEmailValidity();
    });

    form.addEventListener('submit', (event) => {
        setEmailValidity();
        setPasswordValidity();
        setConfirmValidity();

        if (!form.checkValidity()) {
            event.preventDefault();
            if (typeof form.reportValidity === 'function') {
                form.reportValidity();
            }
        }

        form.classList.add('was-validated');
    });
})();
