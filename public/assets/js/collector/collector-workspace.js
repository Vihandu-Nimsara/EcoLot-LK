const collectorStorageKeys = {
    hazards: 'ecolot_collector_flags',
    pickups: 'ecolot_collector_pickups',
};

let selectedRouteId = null;

function readCollectorState(key) {
    try {
        return JSON.parse(sessionStorage.getItem(key) || '{}');
    } catch {
        return {};
    }
}

function writeCollectorState(key, value) {
    sessionStorage.setItem(key, JSON.stringify(value));
}

function updateHazard(id, isHazardous) {
    const hazards = readCollectorState(collectorStorageKeys.hazards);
    hazards[id] = isHazardous;
    writeCollectorState(collectorStorageKeys.hazards, hazards);
}

function updatePickup(id, isPickedUp) {
    const pickups = readCollectorState(collectorStorageKeys.pickups);
    pickups[id] = isPickedUp;
    writeCollectorState(collectorStorageKeys.pickups, pickups);
}

function renderCollectorState() {
    const hazards = readCollectorState(collectorStorageKeys.hazards);
    const pickups = readCollectorState(collectorStorageKeys.pickups);
    const completedPickupIds = Object.keys(pickups).filter((id) => pickups[id]);

    document.querySelectorAll('[data-hazard-indicator]').forEach((indicator) => {
        indicator.classList.toggle('active', Boolean(hazards[indicator.dataset.hazardIndicator]));
    });

    document.querySelectorAll('[data-toggle-hazard]').forEach((button) => {
        button.classList.toggle('active', Boolean(hazards[button.dataset.toggleHazard]));
    });

    document.querySelectorAll('[data-confirm-pickup]').forEach((button) => {
        const isPickedUp = Boolean(pickups[button.dataset.confirmPickup]);
        button.textContent = isPickedUp ? 'Picked Up' : 'Confirm Pickup';
        button.disabled = isPickedUp;
    });

    const pickedUpCount = document.querySelector('[data-picked-up-count]');
    const pendingCount = document.querySelector('[data-pending-count]');
    const hazardCount = document.querySelector('[data-hazard-count]');
    const progress = document.querySelector('[data-pickup-progress]');
    const routeStopCount = 4;

    if (pickedUpCount) pickedUpCount.textContent = completedPickupIds.length;
    if (pendingCount) pendingCount.textContent = Math.max(routeStopCount - completedPickupIds.length, 0);
    if (hazardCount) hazardCount.textContent = Object.values(hazards).filter(Boolean).length;
    if (progress) progress.style.width = `${(completedPickupIds.length / routeStopCount) * 100}%`;
}

function setRouteModalOpen(isOpen) {
    const modal = document.querySelector('[data-route-modal]');
    if (modal) modal.hidden = !isOpen;
}

function openRouteDetails(id) {
    const routeCard = document.querySelector(`[data-route-id="${id}"]`);
    if (!routeCard) return;

    selectedRouteId = id;

    const zone = document.querySelector('[data-route-zone]');
    const weight = document.querySelector('[data-route-modal-weight]');
    const time = document.querySelector('[data-route-modal-time]');

    if (zone) zone.textContent = routeCard.querySelector('h2')?.textContent || '';
    if (weight) weight.textContent = routeCard.dataset.routeWeight;
    if (time) time.textContent = routeCard.dataset.routeTime;

    setRouteModalOpen(true);
}

function showCollectorMessage(message) {
    const output = document.querySelector('[data-record-message]');
    if (output) output.textContent = message;
    alert(message);
}

document.addEventListener('DOMContentLoaded', () => {
    renderCollectorState();

    document.querySelectorAll('[data-confirm-pickup]').forEach((button) => {
        button.addEventListener('click', () => {
            updatePickup(button.dataset.confirmPickup, true);
            renderCollectorState();
        });
    });

    document.querySelectorAll('[data-view-route]').forEach((button) => {
        button.addEventListener('click', () => openRouteDetails(button.dataset.viewRoute));
    });

    document.querySelector('[data-close-route-modal]')?.addEventListener('click', () => {
        setRouteModalOpen(false);
    });

    document.querySelector('[data-route-modal]')?.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) setRouteModalOpen(false);
    });

    document.querySelector('[data-mark-hazard]')?.addEventListener('click', () => {
        if (!selectedRouteId) return;
        updateHazard(selectedRouteId, true);
        renderCollectorState();
        setRouteModalOpen(false);
    });

    document.querySelectorAll('[data-toggle-hazard]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.toggleHazard;
            const hazards = readCollectorState(collectorStorageKeys.hazards);
            updateHazard(id, !hazards[id]);
            renderCollectorState();
        });
    });

    document.querySelectorAll('[data-clear-hazard], [data-send-update]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = button.dataset.clearHazard || button.dataset.sendUpdate;
            updateHazard(id, false);
            renderCollectorState();

            if (button.dataset.sendUpdate) {
                showCollectorMessage('Mistaken!! There is no hazard.');
            }
        });
    });

    document.querySelector('[data-toggle-filter]')?.addEventListener('click', () => {
        const panel = document.querySelector('[data-filter-panel]');
        if (panel) panel.hidden = !panel.hidden;
    });

    document.querySelector('[data-request-filter]')?.addEventListener('change', (event) => {
        const hazards = readCollectorState(collectorStorageKeys.hazards);
        const pickups = readCollectorState(collectorStorageKeys.pickups);

        document.querySelectorAll('[data-request-row]').forEach((row) => {
            const id = row.dataset.requestId;
            const shouldShow = event.target.value === 'all'
                || (event.target.value === 'flagged' && hazards[id])
                || (event.target.value === 'pending' && !pickups[id]);
            row.hidden = !shouldShow;
        });
    });

    document.querySelector('[data-daily-report]')?.addEventListener('click', () => window.print());

    document.querySelector('[data-quick-record-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        showCollectorMessage(`Record for ${formData.get('id')} saved.`);
        event.currentTarget.reset();
    });
});