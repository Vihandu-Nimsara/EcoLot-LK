// The server independently enforces the excluded category's policy.
document.querySelectorAll('[data-catalogue-item-form]').forEach((form) => {
    const category = form.querySelector('select[name="category_id"]');
    const policy = form.querySelector('select[name="collection_status"]');
    if (!category || !policy) return;
    const sync = () => {
        const excluded = category.selectedOptions[0]?.dataset.excluded === '1';
        for (const option of policy.options) {
            option.disabled = excluded && option.value !== 'DO_NOT_COLLECT';
        }
        if (excluded) policy.value = 'DO_NOT_COLLECT';
    };
    category.addEventListener('change', () => { policy.value = ''; sync(); });
    sync();
});

// Catalogue-only enhancements; normal links and the inline form remain usable without JS.
(() => {
    const root = document.querySelector('.category-items-page');
    const dialog = root?.querySelector('[data-catalogue-dialog]');
    const tabs = Array.from(root?.querySelectorAll('[data-catalogue-tab]') || []);
    if (!dialog || tabs.length !== 2) return;
    const panels = Object.fromEntries(tabs.map(tab => [tab.dataset.catalogueTab, root.querySelector(tab.hash)]));
    const tablist = root.querySelector('[data-catalogue-tabs]');
    tablist.setAttribute('role', 'tablist');
    function activate(name, focus = false) {
        tabs.forEach(tab => {
            const selected = tab.dataset.catalogueTab === name;
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-controls', tab.dataset.catalogueTab);
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
            const panel = panels[tab.dataset.catalogueTab];
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', tab.id);
            panel.hidden = !selected;
            if (selected && focus) tab.focus();
        });
    }
    function setHash(hash, replace = false) {
        if (location.hash !== hash) history[replace ? 'replaceState' : 'pushState'](null, '', hash);
    }
    const modalSupported = typeof dialog.showModal === 'function';
    let opener = null;
    if (modalSupported) {
        dialog.removeAttribute('open');
        root.classList.add('catalogue-enhanced');
    }
    function openForm(trigger) {
        if (!modalSupported) return;
        opener = trigger || root.querySelector('[data-catalogue-add]');
        activate('items');
        if (!dialog.open) dialog.showModal();
    }
    function closeForm() {
        setHash('#items', true);
        if (dialog.open) dialog.close();
        const form = dialog.querySelector('[data-catalogue-item-form]');
        if (form) {
            form.reset();
            const category = form.querySelector('select[name=category_id]');
            // Restore the policy options after discarding an unsaved create form.
            category?.dispatchEvent(new Event('change'));
        }
    }
    function syncLocation() {
        const hash = location.hash;
        if (hash === '#item-form' || (!hash && (dialog.dataset.editing === '1' || dialog.dataset.failed === '1'))) {
            activate('items');
            openForm();
        } else {
            if (modalSupported && dialog.open) dialog.close();
            activate(hash === '#categories' ? 'categories' : 'items');
        }
    }
    tabs.forEach((tab, i) => {
        const select = () => { setHash(tab.hash); activate(tab.dataset.catalogueTab, true); };
        tab.addEventListener('click', event => { event.preventDefault(); select(); });
        tab.addEventListener('keydown', event => {
            const target = {ArrowRight: (i+1)%tabs.length, ArrowLeft: (i+tabs.length-1)%tabs.length, Home: 0, End: tabs.length-1}[event.key];
            if (target === undefined) return;
            event.preventDefault(); tabs[target].click();
        });
    });
    if (modalSupported) {
        root.querySelector('[data-catalogue-add]')?.addEventListener('click', event => {
            // The edit form is server-rendered; reload to obtain a fresh create form.
            if (dialog.dataset.editing === '1') return;
            event.preventDefault(); setHash('#item-form'); openForm(event.currentTarget);
        });
        dialog.querySelectorAll('[data-catalogue-close]').forEach(link => link.addEventListener('click', event => {
            event.preventDefault(); closeForm();
        }));
        dialog.addEventListener('cancel', event => { event.preventDefault(); closeForm(); });
        dialog.addEventListener('close', () => {
            if (!panels.items.hidden && opener && opener.isConnected) opener.focus({preventScroll: true});
            opener = null;
        });
    }
    window.addEventListener('popstate', syncLocation);
    window.addEventListener('hashchange', syncLocation);
    syncLocation();

    // Filter the entire result set first, then paginate. Shared demo filters do not own these rows.
    const form = root.querySelector('[data-catalogue-item-filter]');
    const rows = Array.from(root.querySelectorAll('[data-item-row]'));
    const result = root.querySelector('[data-item-filter-result]');
    const empty = root.querySelector('[data-item-filter-empty]');
    const pagination = root.querySelector('[data-catalogue-pagination]');
    const pageSize = root.querySelector('#catalogue-page-size');
    const previous = root.querySelector('[data-page-prev]');
    const next = root.querySelector('[data-page-next]');
    const numbers = root.querySelector('[data-page-numbers]');
    if (!form || !pagination) return;
    let page = 1;
    function render(focusPage = false) {
        const query = form.elements.search.value.trim().toLowerCase();
        const selects = Array.from(form.querySelectorAll('select[name]'));
        const matches = rows.filter(row => (!query || row.dataset.search.toLowerCase().includes(query)) && selects.every(select => !select.value || row.dataset[select.name] === select.value));
        const size = Number(pageSize.value) || 10;
        const pages = Math.max(1, Math.ceil(matches.length / size));
        page = Math.min(pages, Math.max(1, page));
        const start = (page - 1) * size;
        const visible = new Set(matches.slice(start, start + size));
        rows.forEach(row => { row.hidden = !visible.has(row); });
        result.textContent = matches.length ? `Showing ${start+1}–${Math.min(start+size,matches.length)} of ${matches.length} items` : '0 items shown';
        empty.hidden = matches.length !== 0;
        pagination.hidden = matches.length === 0;
        previous.disabled = page === 1;
        next.disabled = page === pages;
        const indices = [...new Set([1, ...Array.from({length: 5}, (_, i) => page+i-2).filter(n=>n>1 && n<pages), pages])].sort((a,b)=>a-b);
        numbers.replaceChildren();
        let last = 0;
        for (const index of indices) {
            if (last && index > last+1) {
                const dots = document.createElement('span'); dots.textContent = '…'; dots.setAttribute('aria-hidden','true'); numbers.append(dots);
            }
            const button = document.createElement('button');
            button.type = 'button'; button.textContent = String(index); button.setAttribute('aria-label', `Page ${index}`);
            if (index === page) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => { page = index; render(true); });
            numbers.append(button);
            last = index;
        }
        if (focusPage) numbers.querySelector('[aria-current="page"]')?.focus({preventScroll:true});
    }
    const reset = () => { page = 1; render(); };
    form.addEventListener('submit', event => { event.preventDefault(); reset(); });
    form.addEventListener('input', reset);
    form.addEventListener('change', reset);
    pageSize.addEventListener('change', reset);
    previous.addEventListener('click', () => { page--; render(true); });
    next.addEventListener('click', () => { page++; render(true); });
    render();
})();
