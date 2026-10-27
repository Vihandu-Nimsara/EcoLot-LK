document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-notification-toggle]');
    const menu = document.querySelector('[data-notification-menu]');

    if (!toggle || !menu) {
        return;
    }

    const badge = toggle.querySelector('.noti-badge');
    const storageKey = `ecolot-seen-notifications-${menu.dataset.notificationUser || 'guest'}`;

    let currentIds = [];
    try {
        currentIds = JSON.parse(menu.dataset.notificationIds || '[]');
    } catch (error) {
        currentIds = [];
    }

    const readSeen = () => {
        try {
            const stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return Array.isArray(stored) ? stored : [];
        } catch (error) {
            return [];
        }
    };

    const markAllSeen = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(currentIds));
        } catch (error) {
            // Storage can be blocked; the dot will then return on the next page load.
        }
        if (badge) {
            badge.hidden = true;
        }
    };

    const seen = readSeen();
    if (badge) {
        badge.hidden = !currentIds.some((id) => !seen.includes(id));
    }

    const setMenuOpen = (isOpen) => {
        menu.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', String(isOpen));
        if (isOpen) {
            markAllSeen();
        }
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