(() => {
    'use strict';
    const resend = document.querySelector('.recycler-profile-page [data-phone-resend]');
    const cooldown = document.querySelector('.recycler-profile-page [data-phone-cooldown]');
    if (resend && cooldown) {
        const readyAt = Date.now() + Number(resend.dataset.cooldown || 0) * 1000;
        const updateCooldown = () => {
            const remaining = Math.max(0, Math.ceil((readyAt - Date.now()) / 1000));
            resend.disabled = remaining > 0;
            cooldown.textContent = remaining > 0 ? `Resend available in ${remaining} seconds.` : 'You can request a new code.';
            return remaining;
        };
        if (updateCooldown() > 0) {
            const timer = setInterval(() => { if (updateCooldown() === 0) clearInterval(timer); }, 1000);
        }
    }
    document.querySelector('[data-profile-feedback]')?.focus();
    const modal = document.querySelector('[data-profile-modal]');
    if (!modal) return;
    // Use the existing dialog styling outside the inert workspace.
    document.body.append(modal);
    const workspace = document.querySelector('.app-layout');
    const fields = modal.querySelector('[data-profile-fields]');
    const configs = {
        contact: ['My Profile', 'Edit Basic Information', 'Contact details can be drafted below. Registered company changes require Administrator review.', 'Saving unavailable'],
        licence: ['Compliance request', 'Submit Licence Update', 'All licence changes require Administrator verification.', 'Submission unavailable'],
        capability: ['Capability request', 'Request Capability Change', 'All capability changes require Administrator approval.', 'Submit Request']
    };
    let trigger = null;
    let previousOverflow = '';
    const close = () => {
        if (modal.hidden) return;
        modal.hidden = true;
        workspace?.removeAttribute('inert');
        document.body.style.overflow = previousOverflow;
        fields.replaceChildren();
        trigger?.focus();
        trigger = null;
    };
    document.addEventListener('click', event => {
        const opener = event.target.closest('[data-profile-open]');
        if (opener) {
            const type = opener.dataset.profileOpen;
            const config = configs[type];
            const template = document.querySelector(`[data-profile-template="${type}"]`);
            if (!config || !template) return;
            trigger = opener;
            modal.querySelector('[data-profile-eyebrow]').textContent = config[0];
            modal.querySelector('#profile-dialog-title').textContent = config[1];
            modal.querySelector('#profile-dialog-description').textContent = config[2];
            const submit = modal.querySelector('[data-profile-submit]');
            submit.textContent = config[3];
            submit.type = type === 'capability' ? 'submit' : 'button';
            submit.disabled = type !== 'capability';
            modal.querySelector('[data-profile-form]').dataset.mode = type;
            fields.replaceChildren(template.content.cloneNode(true));
            const category = fields.querySelector('[data-profile-category]');
            if (type === 'capability' && category) {
                const request = fields.querySelector('[data-profile-request]');
                const risk = fields.querySelector('[data-profile-risk]');
                const riskField = fields.querySelector('[data-profile-risk-field]');
                const updateRisk = () => {
                    const option = category.selectedOptions[0];
                    const deactivating = request.value === 'DEACTIVATE';
                    riskField.hidden = deactivating;
                    risk.disabled = deactivating;
                    risk.required = !deactivating;
                    risk.replaceChildren(new Option('Select permission', ''));
                    for (const [value, label] of [['0', 'No'], ['1', 'Yes']]) {
                        if (request.value === 'UPDATE' && option?.dataset.status === 'APPROVED' && option.dataset.risk === value) continue;
                        risk.add(new Option(label, value));
                    }
                };
                const updateCategory = () => {
                    const actions = category.selectedOptions[0]?.dataset.actions?.split(',') || [];
                    request.replaceChildren(new Option('Select request', ''));
                    const labels = {ADD: 'Add capability', UPDATE: 'Update / Reactivate capability', DEACTIVATE: 'Deactivate capability'};
                    for (const action of actions) request.add(new Option(labels[action], action));
                    if (actions.length === 1) request.value = actions[0];
                    submit.disabled = category.options.length < 2;
                    updateRisk();
                };
                category.value = opener.dataset.categoryId || category.dataset.selected || '';
                category.addEventListener('change', updateCategory);
                request.addEventListener('change', updateRisk);
                updateCategory();
                if (request.dataset.selected && [...request.options].some(option => option.value === request.dataset.selected)) {
                    request.value = request.dataset.selected;
                    updateRisk();
                }
                if ([...risk.options].some(option => option.value === risk.dataset.selected)) risk.value = risk.dataset.selected;
            }
            previousOverflow = document.body.style.overflow;
            modal.hidden = false;
            workspace?.setAttribute('inert', '');
            document.body.style.overflow = 'hidden';
            modal.querySelector('[data-profile-close]').focus();
        }
        if (event.target.closest('[data-profile-close]') || event.target === modal) close();
    });
    // Only the capability form now has a backend. Other drafts remain unavailable.
    modal.querySelector('[data-profile-form]').addEventListener('submit', event => {
        if (event.currentTarget.dataset.mode !== 'capability') event.preventDefault();
    });
    document.addEventListener('keydown', event => {
        if (modal.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (event.key !== 'Tab') return;
        const controls = [...modal.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])')].filter(control => control.getClientRects().length > 0);
        const first = controls[0], last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    document.querySelector('[data-profile-auto-open]')?.click();
})();
