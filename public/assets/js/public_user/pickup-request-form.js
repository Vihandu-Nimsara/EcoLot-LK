document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-pickup-request-form]');

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        alert('Prototype only — request not actually submitted.');
    });

    document.querySelectorAll('[data-guide-category]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const category = tab.dataset.guideCategory;

            document.querySelectorAll('[data-guide-category]').forEach((item) => {
                item.classList.toggle('active', item === tab);
            });
            document.querySelectorAll('[data-guide-list]').forEach((list) => {
                list.classList.toggle('active', list.dataset.guideList === category);
            });
        });
    });
});
