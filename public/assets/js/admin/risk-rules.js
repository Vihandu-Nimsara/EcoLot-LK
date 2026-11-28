'use strict';
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-risk-page]');
    if (!root) return;
    const search = root.querySelector('[data-rr-search]');
    const category = root.querySelector('[data-rr-category]');
    const coverage = root.querySelector('[data-rr-coverage]');
    if (search && category && coverage) {
        const filter = () => {
            let count = 0;
            for (const row of root.querySelectorAll('[data-rr-item]')) {
                row.hidden = !row.dataset.search.toLowerCase().includes(search.value.trim().toLowerCase()) || (category.value !== '' && row.dataset.category !== category.value) || (coverage.value !== '' && row.dataset.coverage !== coverage.value);
                if (!row.hidden) count++;
            }
            root.querySelector('[data-rr-count]').textContent = `${count} items`;
            root.querySelector('[data-rr-empty]').hidden = count !== 0;
        };
        search.addEventListener('input', filter);
        category.addEventListener('change', filter);
        coverage.addEventListener('change', filter);
        filter();
    }
    const dialog = root.querySelector('[data-rr-dialog]');
    if (!dialog) return;
    if (typeof dialog.showModal === 'function') {
        dialog.close(); dialog.showModal();
        dialog.addEventListener('cancel', event => {
            event.preventDefault(); window.location.assign(dialog.querySelector('.rr-dialog-header a').href);
        });
    }
    const form = dialog.querySelector('[data-rr-form]');
    const levels = ['LOW','MEDIUM','HIGH'];
    const update = () => {
        const level = form.elements.risk_level.value;
        const active = form.elements.rule_status.value === 'ACTIVE';
        const effective = active && levels.indexOf(level) > levels.indexOf(form.dataset.default) ? level : form.dataset.default;
        const review = effective === 'HIGH' || form.dataset.policy === 'REVIEW_REQUIRED';
        const lower = levels.indexOf(effective) < levels.indexOf(form.dataset.before);
        form.elements.confirm_reduction.required = lower;
        form.querySelector('[data-rr-preview]').textContent = `Effective risk: ${effective}. ${review ? 'Officer review required.' : 'Normal pickup eligible, subject to other items and checks.'}${lower ? ' This reduces the current effective risk; confirmation is required.' : ''}`;
    };
    form.addEventListener('change', update); update();
});
