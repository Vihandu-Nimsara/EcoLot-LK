(() => {
    const filter = document.querySelector('[data-review-filter]');
    if (!filter) return;
    const status = document.querySelector('#review-status');
    const search = document.querySelector('#review-search');
    const rows = [...document.querySelectorAll('[data-risk-status]')];
    const apply = () => {
        rows.forEach(row => { row.hidden = (status.value && row.dataset.riskStatus !== status.value) || !row.textContent.toLowerCase().includes(search.value.trim().toLowerCase()); });
        document.querySelector('[data-review-empty]').hidden = rows.some(row => !row.hidden);
    };
    filter.addEventListener('submit', event => event.preventDefault());
    filter.addEventListener('input', apply);
    filter.addEventListener('reset', () => setTimeout(apply, 0));
    document.querySelectorAll('form[method="post"]').forEach(form => {
        const note = form.elements.note;
        const decision = form.elements.decision;
        if (!note || !decision) return;
        decision.addEventListener('change', () => { note.required = decision.value === 'REJECTED'; });
        form.addEventListener('submit', event => {
            if (decision.value === 'REJECTED' && !note.value.trim()) { event.preventDefault(); note.focus(); }
        });
    });
})();
