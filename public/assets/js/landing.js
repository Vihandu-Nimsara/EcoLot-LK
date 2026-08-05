const menuToggle = document.querySelector('.menu-toggle');
const mainNavigation = document.querySelector('.main-navigation');

if (menuToggle && mainNavigation) {
    const closeMenu = () => {
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'Open navigation menu');
        mainNavigation.classList.remove('is-open');
        document.body.classList.remove('menu-open');
    };

    menuToggle.addEventListener('click', () => {
        const willOpen = menuToggle.getAttribute('aria-expanded') !== 'true';
        menuToggle.setAttribute('aria-expanded', String(willOpen));
        menuToggle.setAttribute('aria-label', willOpen ? 'Close navigation menu' : 'Open navigation menu');
        mainNavigation.classList.toggle('is-open', willOpen);
        document.body.classList.toggle('menu-open', willOpen);
    });

    mainNavigation.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

    window.addEventListener('resize', () => {
        if (window.innerWidth > 760) closeMenu();
    });
}
