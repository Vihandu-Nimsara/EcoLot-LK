document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-notification-toggle]');
    const menu = document.querySelector('[data-notification-menu]');

    if (!toggle || !menu) return;

    const setMenuOpen = (isOpen) => {
        menu.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', String(isOpen));
    };

    toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        setMenuOpen(menu.hidden);
    });

    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target) && !toggle.contains(event.target)) {
            setMenuOpen(false);
        }
    });
});