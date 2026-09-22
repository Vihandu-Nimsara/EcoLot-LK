(() => {
    // Progressive enhancement only. PHP renders and persists all schedules.
    const filter = document.querySelector('#campaign-filter');
    filter?.addEventListener('change', () => {
        document.querySelectorAll('[data-campaign-id]').forEach((row) => {
            row.hidden = filter.value !== '' && row.dataset.campaignId !== filter.value;
        });
    });
})();
