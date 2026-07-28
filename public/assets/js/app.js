function getFlags() {
    return JSON.parse(localStorage.getItem('ecolot_flags') || '{}');
}
function setFlag(id, value) {
    const flags = getFlags();
    flags[id] = value;
    localStorage.setItem('ecolot_flags', JSON.stringify(flags));
}

let currentDetailsId = null;

function confirmPickup(btn) {
    btn.textContent = '✔ Picked Up';
    btn.disabled = true;
}

function openDetails(id) {
    currentDetailsId = id;
    const card = document.querySelector(`.route-card[data-id="${id}"]`);
    const zoneName = card.querySelector('h3').childNodes[0].textContent.trim();
    document.getElementById('modalZoneName').textContent = zoneName;
    document.getElementById('modalWeight').textContent = card.dataset.weight;
    document.getElementById('modalTime').textContent = card.dataset.time;
    document.getElementById('detailsModal').classList.add('show');
}

function closeDetails() {
    document.getElementById('detailsModal').classList.remove('show');
}

function markHazard() {
    if (!currentDetailsId) return;
    setFlag(currentDetailsId, true);
    applyFlagIcons();
    closeDetails();
    alert('Marked as hazard. This will show as a red flag for the officer.');
}

function applyFlagIcons() {
    const flags = getFlags();
    document.querySelectorAll('.flag-icon').forEach(el => {
        const id = el.dataset.flagid;
        el.classList.toggle('active', !!flags[id]);
    });
    document.querySelectorAll('.flag-btn').forEach(el => {
        const row = el.closest('.quick-status-row');
        if (row) el.classList.toggle('active', !!flags[row.dataset.flagid]);
    });
}

function deleteFlag(id) {
    setFlag(id, false);
    applyFlagIcons();
}

function sendUpdate(id) {
    setFlag(id, false);
    applyFlagIcons();
    alert('Update sent to officer: this item is NOT a hazard.');
}

document.addEventListener('DOMContentLoaded', applyFlagIcons);
