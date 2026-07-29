const storageKeys = {
    flags: 'ecolot_collector_flags',
    pickups: 'ecolot_collector_pickups',
};

let currentDetailsId = null;

function readStorage(key) {
    try {
        return JSON.parse(localStorage.getItem(key) || '{}');
    } catch {
        return {};
    }
}

function writeStorage(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function setFlag(id, value) {
    const flags = readStorage(storageKeys.flags);
    flags[id] = value;
    writeStorage(storageKeys.flags, flags);
}

function setPickup(id, value) {
    const pickups = readStorage(storageKeys.pickups);
    pickups[id] = value;
    writeStorage(storageKeys.pickups, pickups);
}

function applyCollectorState() {
    const flags = readStorage(storageKeys.flags);
    const pickups = readStorage(storageKeys.pickups);
    const pickupIds = Object.keys(pickups).filter((id) => pickups[id]);

    document.querySelectorAll('.flag-icon').forEach((element) => {
        element.classList.toggle('active', Boolean(flags[element.dataset.flagid]));
    });
    document.querySelectorAll('.flag-btn').forEach((element) => {
        element.classList.toggle('active', Boolean(flags[element.dataset.toggleHazard]));
    });
    document.querySelectorAll('[data-pickup-id]').forEach((button) => {
        const isPickedUp = Boolean(pickups[button.dataset.pickupId]);
        button.textContent = isPickedUp ? '✔ Picked Up' : '✔ Confirm Pickup';
        button.disabled = isPickedUp;
    });

    const pickedUpCount = document.querySelector('[data-picked-up-count]');
    const pendingCount = document.querySelector('[data-pending-count]');
    const hazardCount = document.querySelector('[data-hazard-count]');
    const progress = document.querySelector('[data-pickup-progress]');
    const routeStops = 4;

    if (pickedUpCount) pickedUpCount.textContent = pickupIds.length;
    if (pendingCount) pendingCount.textContent = Math.max(routeStops - pickupIds.length, 0);
    if (hazardCount) hazardCount.textContent = Object.values(flags).filter(Boolean).length;
    if (progress) progress.style.width = `${(pickupIds.length / routeStops) * 100}%`;
}

function openDetails(id) {
    const card = document.querySelector(`.route-card[data-id="${id}"]`);
    const modal = document.getElementById('detailsModal');
    if (!card || !modal) return;

    currentDetailsId = id;
    document.getElementById('modalZoneName').textContent = card.querySelector('h3').childNodes[0].textContent.trim();
    document.getElementById('modalWeight').textContent = card.dataset.weight;
    document.getElementById('modalTime').textContent = card.dataset.time;
    modal.hidden = false;
    modal.classList.add('show');
}

function closeDetails() {
    const modal = document.getElementById('detailsModal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.hidden = true;
}

function showRecordMessage(message) {
    const output = document.querySelector('[data-record-message]');
    if (output) output.textContent = message;
}

document.addEventListener('DOMContentLoaded', () => {
    applyCollectorState();

    document.querySelectorAll('[data-pickup-id]').forEach((button) => {
        button.addEventListener('click', () => {
            setPickup(button.dataset.pickupId, true);
            applyCollectorState();
        });
    });
    document.querySelectorAll('[data-details-id]').forEach((button) => {
        button.addEventListener('click', () => openDetails(button.dataset.detailsId));
    });
    document.querySelector('[data-close-details]')?.addEventListener('click', closeDetails);
    document.querySelector('[data-mark-hazard]')?.addEventListener('click', () => {
        if (!currentDetailsId) return;
        setFlag(currentDetailsId, true);
        applyCollectorState();
        closeDetails();
    });
    document.querySelectorAll('[data-toggle-hazard]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.toggleHazard;
            const flags = readStorage(storageKeys.flags);
            setFlag(id, !flags[id]);
            applyCollectorState();
        });
    });
    document.querySelectorAll('[data-clear-hazard], [data-send-update]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.clearHazard || button.dataset.sendUpdate;
            setFlag(id, false);
            applyCollectorState();
            if (button.dataset.sendUpdate) showRecordMessage(`Update sent for ${id.toUpperCase()}.`);
        });
    });
    document.querySelector('[data-toggle-filter]')?.addEventListener('click', () => {
        const panel = document.querySelector('[data-filter-panel]');
        if (panel) panel.hidden = !panel.hidden;
    });
    document.querySelector('[data-request-filter]')?.addEventListener('change', (event) => {
        const flags = readStorage(storageKeys.flags);
        const pickups = readStorage(storageKeys.pickups);
        document.querySelectorAll('[data-request-row]').forEach((row) => {
            const id = row.dataset.flagid;
            const show = event.target.value === 'all'
                || (event.target.value === 'flagged' && flags[id])
                || (event.target.value === 'pending' && !pickups[id]);
            row.hidden = !show;
        });
    });
    document.querySelector('[data-daily-report]')?.addEventListener('click', () => window.print());
    document.querySelector('[data-quick-record-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        showRecordMessage(`Record for ${formData.get('id')} saved.`);
        event.currentTarget.reset();
    });
});
