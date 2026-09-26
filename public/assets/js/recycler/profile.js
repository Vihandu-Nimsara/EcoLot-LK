(() => {
    'use strict';
    const modal = document.querySelector('[data-profile-modal]');
    if (!modal) return;
    // Use the existing dialog styling outside the inert workspace.
    document.body.append(modal);
    const workspace = document.querySelector('.app-layout');
    const fields = modal.querySelector('[data-profile-fields]');
    const configs = {
        contact: ['My Profile', 'Edit Basic Information', 'Contact details can be drafted below. Registered company changes require Administrator review.', 'Saving unavailable'],
        licence: ['Compliance request', 'Submit Licence Update', 'All licence changes require Administrator verification.', 'Submission unavailable'],
        capability: ['Capability request', 'Request Capability Change', 'All capability changes require Administrator review.', 'Submission unavailable']
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
            modal.querySelector('[data-profile-unavailable]').textContent = config[3];
            fields.replaceChildren(template.content.cloneNode(true));
            const category = fields.querySelector('[data-profile-category]');
            if (category && opener.dataset.category) {
                category.value = opener.dataset.category;
                category.readOnly = true;
            }
            previousOverflow = document.body.style.overflow;
            modal.hidden = false;
            workspace?.setAttribute('inert', '');
            document.body.style.overflow = 'hidden';
            modal.querySelector('[data-profile-close]').focus();
        }
        if (event.target.closest('[data-profile-close]') || event.target === modal) close();
    });
    // Never send these visual drafts, including implicit submission using Enter.
    modal.querySelector('[data-profile-form]').addEventListener('submit', event => event.preventDefault());
    document.addEventListener('keydown', event => {
        if (modal.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (event.key !== 'Tab') return;
        const controls = [...modal.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])')];
        const first = controls[0], last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
})();
