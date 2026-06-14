(function () {
    document.querySelectorAll('.password-field button').forEach((button) => {
        const input = button.closest('.password-field')?.querySelector('input');

        if (!input) {
            return;
        }

        const label = button.dataset.passwordLabel || 'password';
        button.textContent = 'Show';
        button.removeAttribute('tabindex');
        button.setAttribute('aria-label', `Show ${label}`);
        button.setAttribute('aria-pressed', 'false');

        button.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            button.textContent = showPassword ? 'Hide' : 'Show';
            button.setAttribute('aria-label', `${showPassword ? 'Hide' : 'Show'} ${label}`);
            button.setAttribute('aria-pressed', String(showPassword));
        });
    });
})();
