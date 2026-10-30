(() => {
    const search = document.querySelector('#assignment-search');
    const rows = [...document.querySelectorAll('[data-assignment-row]')];
    search?.addEventListener('input', () => {
        rows.forEach(row => { row.hidden = !row.textContent.toLowerCase().includes(search.value.trim().toLowerCase()); });
        document.querySelector('[data-assignment-empty]').hidden = rows.some(row => !row.hidden);
    });
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    }));
})();
