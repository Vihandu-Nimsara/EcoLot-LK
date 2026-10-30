(() => {
    const dialog = document.querySelector('[data-campaign-dialog]');
    const form = document.querySelector('[data-campaign-form]');
    if (!dialog || !form) return;
    let trigger;
    const show = () => { dialog.hidden = false; form.elements.name.focus(); };
    const close = () => { dialog.hidden = true; trigger?.focus(); };
    document.querySelector('[data-open-campaign-dialog]').addEventListener('click', event => {
        trigger = event.currentTarget;
        form.reset();
        form.elements.campaign_id.value = '';
        form.elements.name.value = '';
        const parts = new Intl.DateTimeFormat('en', {timeZone:'Asia/Colombo',year:'numeric',month:'2-digit'}).formatToParts(new Date());
        form.elements.period.value = `${parts.find(p => p.type === 'year').value}-${parts.find(p => p.type === 'month').value}`;
        form.elements.status.value = 'OPEN';
        document.querySelector('[data-campaign-dialog-title]').textContent = 'Create Campaign';
        document.querySelector('[data-campaign-form-error]').hidden = true;
        show();
    });
    document.querySelectorAll('.edit-btn').forEach(button => button.addEventListener('click', () => {
        trigger = button;
        const data = button.closest('tr').dataset;
        form.elements.campaign_id.value = data.campaignId;
        form.elements.name.value = data.name;
        form.elements.period.value = data.period;
        form.elements.status.value = data.status;
        document.querySelector('[data-campaign-dialog-title]').textContent = 'Edit Campaign';
        document.querySelector('[data-campaign-form-error]').hidden = true;
        show();
    }));
    document.querySelectorAll('[data-close-campaign-dialog]').forEach(button => button.addEventListener('click', close));
    document.addEventListener('keydown', event => {
        if (dialog.hidden) return;
        if (event.key === 'Escape') close();
        if (event.key === 'Tab') {
            const controls = [...dialog.querySelectorAll('button,input:not([type=hidden]),select')];
            const first = controls[0], last = controls.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    if (form.dataset.errors === '1') show();
})();
