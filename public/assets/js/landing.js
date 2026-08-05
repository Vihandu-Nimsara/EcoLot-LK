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

const benefitTabs = Array.from(document.querySelectorAll('[data-benefit-tab]'));
const benefitPanels = Array.from(document.querySelectorAll('[data-benefit-panel]'));

const activateBenefit = (tab) => {
    const target = tab.dataset.benefitTab;

    benefitTabs.forEach((candidate) => {
        const isActive = candidate === tab;
        candidate.classList.toggle('is-active', isActive);
        candidate.setAttribute('aria-selected', String(isActive));
        candidate.setAttribute('tabindex', isActive ? '0' : '-1');
    });

    benefitPanels.forEach((panel) => {
        panel.hidden = panel.dataset.benefitPanel !== target;
    });
};

benefitTabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateBenefit(tab));
    tab.addEventListener('keydown', (event) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;

        event.preventDefault();
        let nextIndex = index;
        if (event.key === 'ArrowRight') nextIndex = (index + 1) % benefitTabs.length;
        if (event.key === 'ArrowLeft') nextIndex = (index - 1 + benefitTabs.length) % benefitTabs.length;
        if (event.key === 'Home') nextIndex = 0;
        if (event.key === 'End') nextIndex = benefitTabs.length - 1;

        const nextTab = benefitTabs[nextIndex];
        activateBenefit(nextTab);
        nextTab.focus();
    });
});
