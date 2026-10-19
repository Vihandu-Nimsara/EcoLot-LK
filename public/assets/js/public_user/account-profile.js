document.addEventListener('DOMContentLoaded', () => {
    const panels = document.querySelectorAll('[data-profile-panel]');

    const showPanel = (name) => {
        panels.forEach((panel) => {
            panel.hidden = panel.dataset.profilePanel !== name;
        });
    };

    document.querySelectorAll('[data-profile-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.querySelector(`[data-profile-panel="${button.dataset.profileToggle}"]`);
            showPanel(target && !target.hidden ? '' : button.dataset.profileToggle);
            target?.querySelector('input[type="password"]')?.focus();
        });
    });

    document.querySelectorAll('[data-profile-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            const panel = button.closest('[data-profile-panel]');
            panel?.reset();
            showPanel('');
        });
    });

    const passwordForm = document.querySelector('[data-profile-panel="password"]');
    passwordForm?.addEventListener('submit', (event) => {
        const newPassword = passwordForm.querySelector('#new-password').value;
        const confirm = passwordForm.querySelector('#confirm-password').value;
        if (newPassword !== confirm) {
            event.preventDefault();
            alert('The new password and confirmation do not match.');
        }
    });

    document.querySelector('[data-profile-panel="delete"]')?.addEventListener('submit', (event) => {
        if (!confirm('Are you sure you want to delete your account?')) {
            event.preventDefault();
        }
    });
});