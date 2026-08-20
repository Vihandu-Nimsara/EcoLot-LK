(() => {
    'use strict';

    const SELECTED_SCHEDULE_KEY = 'ecolot_collector_selected_schedule_id';
    const COLLECTION_RECORDS_KEY = 'ecolot_collector_collection_records';
    const SCHEDULE_SUBMISSIONS_KEY = 'ecolot_collector_schedule_submissions_v1';

    const collector = {
        userId: 26,
        name: 'Collector User',
        code: 'Collector #26'
    };

    const schedules = [
        {
            id: 'SCH-03201',
            area: 'Kollupitiya',
            postalCode: '00300',
            collectionDate: '2026-08-16',
            vehicle: 'WP LM-7284',
            status: 'IN_PROGRESS',
            requestIds: ['REQ-01061', 'REQ-01064', 'REQ-01067']
        },
        {
            id: 'SCH-03204',
            area: 'Narahenpita',
            postalCode: '00500',
            collectionDate: '2026-08-18',
            vehicle: 'WP LM-7315',
            status: 'ASSIGNED',
            requestIds: ['REQ-01072', 'REQ-01075']
        },
        {
            id: 'SCH-03208',
            area: 'Rajagiriya',
            postalCode: '10100',
            collectionDate: '2026-08-25',
            vehicle: 'WP LM-7342',
            status: 'ASSIGNED',
            requestIds: ['REQ-01080', 'REQ-01081', 'REQ-01082', 'REQ-01084']
        }
    ];

    const requests = [
        {
            id: 'REQ-01061',
            scheduleId: 'SCH-03201',
            pickupAddress: 'No. 42, Galle Road, Colombo 03',
            publicUser: 'Public User 061',
            phone: '077 100 0061',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-061-1', name: 'Laptop Computer', category: 'Computers & IT Equipment', requestedQuantity: 1, estimatedWeightKg: 2.4, reportedCondition: 'USED', conditionNote: 'Battery drains quickly.' },
                { id: 'RI-061-2', name: 'Computer Mouse', category: 'Computers & IT Equipment', requestedQuantity: 2, estimatedWeightKg: 0.3, reportedCondition: 'USED', conditionNote: '' }
            ]
        },
        {
            id: 'REQ-01064',
            scheduleId: 'SCH-03201',
            pickupAddress: 'No. 17, Duplication Road, Colombo 03',
            publicUser: 'Public User 064',
            phone: '077 100 0064',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-064-1', name: 'Mobile Phone', category: 'Mobile & Communication Devices', requestedQuantity: 3, estimatedWeightKg: 0.7, reportedCondition: 'DAMAGED', conditionNote: 'Two screens are cracked.' }
            ]
        },
        {
            id: 'REQ-01067',
            scheduleId: 'SCH-03201',
            pickupAddress: 'No. 8, Sea View Avenue, Colombo 03',
            publicUser: 'Public User 067',
            phone: '077 100 0067',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-067-1', name: 'Wi-Fi Router', category: 'Networking Equipment', requestedQuantity: 2, estimatedWeightKg: 1.1, reportedCondition: 'USED', conditionNote: 'Old routers.' },
                { id: 'RI-067-2', name: 'Power Adapter', category: 'Accessories', requestedQuantity: 2, estimatedWeightKg: 0.5, reportedCondition: 'UNKNOWN', conditionNote: '' }
            ]
        },
        {
            id: 'REQ-01072',
            scheduleId: 'SCH-03204',
            pickupAddress: 'No. 18, Kirimandala Mawatha, Colombo 05',
            publicUser: 'Public User 072',
            phone: '077 100 0072',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-072-1', name: 'Laptop Computer', category: 'Computers & IT Equipment', requestedQuantity: 2, estimatedWeightKg: 5.4, reportedCondition: 'USED', conditionNote: 'Both laptops are old but complete.' },
                { id: 'RI-072-2', name: 'Mobile Phone', category: 'Mobile & Communication Devices', requestedQuantity: 3, estimatedWeightKg: 0.9, reportedCondition: 'DAMAGED', conditionNote: 'One phone has a swollen battery.' }
            ]
        },
        {
            id: 'REQ-01075',
            scheduleId: 'SCH-03204',
            pickupAddress: 'No. 67, Park Road, Colombo 05',
            publicUser: 'Public User 075',
            phone: '077 100 0075',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-075-1', name: 'Desktop Monitor', category: 'Displays & Monitors', requestedQuantity: 1, estimatedWeightKg: 4.8, reportedCondition: 'DAMAGED', conditionNote: 'Display does not power on.' }
            ]
        },
        {
            id: 'REQ-01080',
            scheduleId: 'SCH-03208',
            pickupAddress: 'No. 11, Parliament Road, Rajagiriya',
            publicUser: 'Public User 080',
            phone: '077 100 0080',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-080-1', name: 'Keyboard', category: 'Computer Accessories', requestedQuantity: 4, estimatedWeightKg: 2.4, reportedCondition: 'USED', conditionNote: '' }
            ]
        },
        {
            id: 'REQ-01081',
            scheduleId: 'SCH-03208',
            pickupAddress: 'No. 38, Cotta Road, Rajagiriya',
            publicUser: 'Public User 081',
            phone: '077 100 0081',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-081-1', name: 'Printer', category: 'Office Electronics', requestedQuantity: 1, estimatedWeightKg: 7.2, reportedCondition: 'USED', conditionNote: 'Ink removed.' }
            ]
        },
        {
            id: 'REQ-01082',
            scheduleId: 'SCH-03208',
            pickupAddress: 'No. 90, Lake Drive, Rajagiriya',
            publicUser: 'Public User 082',
            phone: '077 100 0082',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-082-1', name: 'Lithium-ion Battery Pack', category: 'Batteries', requestedQuantity: 2, estimatedWeightKg: 3.1, reportedCondition: 'DAMAGED', conditionNote: 'Outer casing scratched.' }
            ]
        },
        {
            id: 'REQ-01084',
            scheduleId: 'SCH-03208',
            pickupAddress: 'No. 6, Welikada Terrace, Rajagiriya',
            publicUser: 'Public User 084',
            phone: '077 100 0084',
            requestStatus: 'APPROVED',
            items: [
                { id: 'RI-084-1', name: 'Wi-Fi Router', category: 'Networking Equipment', requestedQuantity: 3, estimatedWeightKg: 1.8, reportedCondition: 'USED', conditionNote: '' },
                { id: 'RI-084-2', name: 'Network Switch', category: 'Networking Equipment', requestedQuantity: 1, estimatedWeightKg: 1.6, reportedCondition: 'UNKNOWN', conditionNote: 'Not tested.' }
            ]
        }
    ];

    let activeRequestId = null;
    let modalReturnFocus = null;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const getJson = (key, fallback) => {
        try {
            const parsed = JSON.parse(localStorage.getItem(key));
            return parsed && typeof parsed === 'object' ? parsed : fallback;
        } catch (error) {
            return fallback;
        }
    };

    const setJson = (key, value) => localStorage.setItem(key, JSON.stringify(value));

    const getRecords = () => {
        const raw = getJson(COLLECTION_RECORDS_KEY, {});
        const migrated = { ...raw };
        let changed = false;

        Object.keys(migrated).forEach((requestId) => {
            const record = migrated[requestId];
            if (record && record.confirmed === true && !record.state) {
                migrated[requestId] = {
                    ...record,
                    requestId,
                    state: 'SUBMITTED',
                    submittedAt: record.confirmedAt || record.updatedAt || new Date().toISOString()
                };
                changed = true;
            }
        });

        if (changed) {
            setJson(COLLECTION_RECORDS_KEY, migrated);
        }

        return migrated;
    };

    const saveRecords = (records) => setJson(COLLECTION_RECORDS_KEY, records);
    const getSubmissions = () => getJson(SCHEDULE_SUBMISSIONS_KEY, {});
    const saveSubmissions = (submissions) => setJson(SCHEDULE_SUBMISSIONS_KEY, submissions);

    const formatDate = (isoDate) => {
        const date = new Date(`${isoDate}T00:00:00`);
        return new Intl.DateTimeFormat('en-LK', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        }).format(date);
    };

    const formatDateTime = (iso) => {
        if (!iso) return '—';
        const date = new Date(iso);
        return new Intl.DateTimeFormat('en-LK', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        }).format(date);
    };

    const normalizeState = (record) => record?.state || 'PENDING_COLLECTION';

    const stateLabel = (state) => ({
        PENDING_COLLECTION: 'Pending Collection',
        DRAFT: 'Draft',
        SUBMITTED: 'Submitted',
        VERIFIED: 'Verified',
        REJECTED: 'Needs Correction'
    }[state] || state);

    const stateClass = (state) => ({
        PENDING_COLLECTION: 'pending',
        DRAFT: 'draft',
        SUBMITTED: 'submitted',
        VERIFIED: 'verified',
        REJECTED: 'rejected'
    }[state] || 'pending');

    const scheduleStatusLabel = (status) => ({
        ASSIGNED: 'Assigned',
        IN_PROGRESS: 'In Progress',
        COLLECTION_SUBMITTED: 'Pending Verification',
        COMPLETED: 'Completed'
    }[status] || status.replaceAll('_', ' '));

    const scheduleStatusClass = (status) => ({
        ASSIGNED: 'assigned',
        IN_PROGRESS: 'in-progress',
        COLLECTION_SUBMITTED: 'pending-verification',
        COMPLETED: 'completed'
    }[status] || 'assigned');

    const getRequest = (requestId) => requests.find((request) => request.id === requestId) || null;
    const getSchedule = (scheduleId) => schedules.find((schedule) => schedule.id === scheduleId) || null;

    const getScheduleRequests = (scheduleId) => requests.filter((request) => request.scheduleId === scheduleId);

    const getScheduleDisplayStatus = (scheduleId) => {
        const submission = getSubmissions()[scheduleId];
        if (submission?.status === 'VERIFIED') return 'COMPLETED';
        if (submission?.status === 'PENDING') return 'COLLECTION_SUBMITTED';
        return getSchedule(scheduleId)?.status || 'ASSIGNED';
    };

    const deriveItemResult = (actualQuantity, requestedQuantity) => {
        const actual = Number(actualQuantity);
        const requested = Number(requestedQuantity);
        if (!Number.isFinite(actual) || actual <= 0) return 'NOT_COLLECTED';
        if (actual < requested) return 'PARTIAL';
        return 'COLLECTED';
    };

    const derivePickupResult = (items) => {
        if (!items.length || items.every((item) => item.itemResult === 'NOT_COLLECTED')) return 'NOT_COLLECTED';
        if (items.every((item) => item.itemResult === 'COLLECTED')) return 'COLLECTED';
        return 'PARTIAL';
    };

    const recordTotalWeight = (record) => (record?.items || []).reduce((sum, item) => sum + (Number(item.actualWeightKg) || 0), 0);

    const showFeedback = (message) => {
        document.querySelectorAll('#collector-feedback').forEach((box) => {
            box.textContent = message;
            box.hidden = false;
            window.clearTimeout(box._hideTimer);
            box._hideTimer = window.setTimeout(() => {
                box.hidden = true;
            }, 3600);
        });
    };

    const selectedScheduleId = () => {
        const saved = localStorage.getItem(SELECTED_SCHEDULE_KEY);
        if (saved && schedules.some((schedule) => schedule.id === saved)) return saved;
        const first = schedules[0]?.id || null;
        if (first) localStorage.setItem(SELECTED_SCHEDULE_KEY, first);
        return first;
    };

    const setSelectedSchedule = (scheduleId) => {
        if (!schedules.some((schedule) => schedule.id === scheduleId)) return;
        localStorage.setItem(SELECTED_SCHEDULE_KEY, scheduleId);
        renderSchedulesPage();
    };

    const scheduleProgress = (scheduleId) => {
        const records = getRecords();
        const scheduleRequests = getScheduleRequests(scheduleId);
        const ready = scheduleRequests.filter((request) => ['SUBMITTED', 'VERIFIED'].includes(normalizeState(records[request.id]))).length;
        const drafts = scheduleRequests.filter((request) => normalizeState(records[request.id]) === 'DRAFT').length;
        return { ready, drafts, total: scheduleRequests.length };
    };

    const renderScheduleCards = () => {
        const grid = document.getElementById('collector-schedule-grid');
        if (!grid) return;

        const selected = selectedScheduleId();
        const scheduleCount = document.getElementById('collector-schedule-count');
        const nextSchedule = document.getElementById('collector-next-schedule');
        const empty = document.getElementById('collector-schedule-empty');

        if (scheduleCount) scheduleCount.textContent = `${schedules.length} schedule${schedules.length === 1 ? '' : 's'}`;

        const futureSchedules = [...schedules].sort((a, b) => a.collectionDate.localeCompare(b.collectionDate));
        if (nextSchedule) {
            nextSchedule.textContent = futureSchedules[0] ? `Next: ${formatDate(futureSchedules[0].collectionDate)}` : 'Next: —';
        }

        if (!schedules.length) {
            grid.innerHTML = '';
            if (empty) empty.hidden = false;
            return;
        }
        if (empty) empty.hidden = true;

        grid.innerHTML = schedules.map((schedule) => {
            const progress = scheduleProgress(schedule.id);
            const displayStatus = getScheduleDisplayStatus(schedule.id);
            return `
                <article class="collector-schedule-card ${schedule.id === selected ? 'is-selected' : ''}">
                    <div class="collector-schedule-card__top">
                        <span class="collector-schedule-card__date">${escapeHtml(formatDate(schedule.collectionDate))}</span>
                        <span class="collector-status collector-status--${scheduleStatusClass(displayStatus)}">${escapeHtml(scheduleStatusLabel(displayStatus))}</span>
                    </div>
                    <h3>${escapeHtml(schedule.area)}</h3>
                    <span class="collector-schedule-card__id">${escapeHtml(schedule.id)} · ${escapeHtml(schedule.postalCode)}</span>
                    <div class="collector-schedule-card__bottom">
                        <span class="collector-schedule-card__progress">${progress.ready} of ${progress.total} records submitted${progress.drafts ? ` · ${progress.drafts} draft` : ''}</span>
                        <button type="button" class="collector-link-button" data-schedule-select="${escapeHtml(schedule.id)}">
                            ${schedule.id === selected ? 'Selected →' : 'View schedule →'}
                        </button>
                    </div>
                </article>
            `;
        }).join('');
    };

    const requestActionLabel = (state) => {
        if (state === 'DRAFT' || state === 'REJECTED') return 'Continue Record';
        if (state === 'SUBMITTED' || state === 'VERIFIED') return 'View Record';
        return 'Record Collection';
    };

    const renderSelectedSchedule = () => {
        const host = document.getElementById('collector-selected-schedule');
        if (!host) return;

        const schedule = getSchedule(selectedScheduleId());
        if (!schedule) {
            host.innerHTML = '';
            return;
        }

        const scheduleRequests = getScheduleRequests(schedule.id);
        const records = getRecords();
        const progress = scheduleProgress(schedule.id);
        const submission = getSubmissions()[schedule.id];
        const displayStatus = getScheduleDisplayStatus(schedule.id);
        const allSubmitted = scheduleRequests.length > 0 && scheduleRequests.every((request) => ['SUBMITTED', 'VERIFIED'].includes(normalizeState(records[request.id])));

        const rows = scheduleRequests.map((request) => {
            const state = normalizeState(records[request.id]);
            return `
                <tr>
                    <td><span class="collector-request-id">${escapeHtml(request.id)}</span></td>
                    <td>${escapeHtml(request.pickupAddress)}</td>
                    <td>${request.items.length} item${request.items.length === 1 ? '' : 's'}</td>
                    <td><span class="collector-status collector-status--${stateClass(state)}">${escapeHtml(stateLabel(state))}</span></td>
                    <td><button type="button" class="collector-table-action" data-request-open="${escapeHtml(request.id)}">${escapeHtml(requestActionLabel(state))}</button></td>
                </tr>
            `;
        }).join('');

        let submissionCopy = `${progress.ready} of ${progress.total} collection records submitted.`;
        let submissionAction = `<button type="button" class="collector-primary-button" data-submit-schedule="${escapeHtml(schedule.id)}" ${allSubmitted ? '' : 'disabled'}>Submit Schedule for Verification</button>`;

        if (submission?.status === 'PENDING') {
            submissionCopy = `Submitted ${formatDateTime(submission.submittedAt)}. Waiting for Municipal Officer verification.`;
            submissionAction = '<span class="collector-status collector-status--pending-verification">Pending Verification</span>';
        } else if (submission?.status === 'VERIFIED') {
            submissionCopy = `Verified ${formatDateTime(submission.verifiedAt)} by the Municipal Officer.`;
            submissionAction = '<span class="collector-status collector-status--verified">Verified</span>';
        } else if (submission?.status === 'REJECTED') {
            submissionCopy = 'The Municipal Officer returned this schedule for correction. Correct the relevant records before resubmitting.';
            submissionAction = '<span class="collector-status collector-status--rejected">Needs Correction</span>';
        }

        host.innerHTML = `
            <div class="collector-selected-schedule__header">
                <div>
                    <span class="collector-eyebrow">Selected schedule</span>
                    <h2>${escapeHtml(schedule.area)} Collection</h2>
                    <p>${scheduleRequests.length} approved request${scheduleRequests.length === 1 ? '' : 's'} assigned to this collection schedule.</p>
                </div>
                <span class="collector-status collector-status--${scheduleStatusClass(displayStatus)}">${escapeHtml(scheduleStatusLabel(displayStatus))}</span>
            </div>

            <div class="collector-schedule-meta">
                <div><span>Schedule ID</span><strong>${escapeHtml(schedule.id)}</strong></div>
                <div><span>Collection Date</span><strong>${escapeHtml(formatDate(schedule.collectionDate))}</strong></div>
                <div><span>Postal Code</span><strong>${escapeHtml(schedule.postalCode)}</strong></div>
                <div><span>Vehicle</span><strong>${escapeHtml(schedule.vehicle)}</strong></div>
            </div>

            <div class="collector-section-header">
                <div>
                    <h2>Collection Requests</h2>
                    <p>Open a request to compare the original details with the actual collection and save the collection record.</p>
                </div>
                <span class="collector-count-pill">${scheduleRequests.length} request${scheduleRequests.length === 1 ? '' : 's'}</span>
            </div>

            <div class="collector-table-wrap">
                <table class="collector-table">
                    <thead>
                        <tr>
                            <th scope="col">Request ID</th>
                            <th scope="col">Pickup Address</th>
                            <th scope="col">Items</th>
                            <th scope="col">Record Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>${rows || '<tr><td colspan="5">No approved requests assigned to this schedule.</td></tr>'}</tbody>
                </table>
            </div>

            <div class="collector-schedule-submit">
                <div class="collector-schedule-submit__copy">
                    <strong>Schedule Collection Submission</strong>
                    <span>${escapeHtml(submissionCopy)}</span>
                </div>
                ${submissionAction}
            </div>
        `;
    };

    const renderSchedulesPage = () => {
        if (!document.querySelector('[data-collector-page="schedules"]')) return;
        renderScheduleCards();
        renderSelectedSchedule();
    };

    const allRequestsWithSchedule = () => requests.map((request) => ({ ...request, schedule: getSchedule(request.scheduleId) }));

    const renderRequestFilters = () => {
        const select = document.getElementById('collector-request-schedule-filter');
        if (!select || select.dataset.ready === 'true') return;
        schedules.forEach((schedule) => {
            const option = document.createElement('option');
            option.value = schedule.id;
            option.textContent = `${schedule.area} · ${formatDate(schedule.collectionDate)}`;
            select.append(option);
        });
        select.dataset.ready = 'true';
    };

    const renderRequestSummary = () => {
        const total = document.getElementById('collector-summary-total');
        if (!total) return;
        const records = getRecords();
        const recordList = Object.values(records);
        const drafts = recordList.filter((record) => normalizeState(record) === 'DRAFT').length;
        const submitted = recordList.filter((record) => ['SUBMITTED', 'VERIFIED'].includes(normalizeState(record))).length;
        const weight = recordList.reduce((sum, record) => sum + recordTotalWeight(record), 0);

        total.textContent = String(requests.length);
        document.getElementById('collector-summary-drafts').textContent = String(drafts);
        document.getElementById('collector-summary-submitted').textContent = String(submitted);
        document.getElementById('collector-summary-weight').textContent = `${weight.toFixed(2)} kg`;
    };

    const renderAllRequests = () => {
        const body = document.getElementById('collector-all-request-rows');
        if (!body) return;
        renderRequestFilters();
        renderRequestSummary();

        const scheduleFilter = document.getElementById('collector-request-schedule-filter')?.value || 'ALL';
        const statusFilter = document.getElementById('collector-request-status-filter')?.value || 'ALL';
        const search = (document.getElementById('collector-request-search')?.value || '').trim().toLowerCase();
        const records = getRecords();

        const filtered = allRequestsWithSchedule().filter((request) => {
            const state = normalizeState(records[request.id]);
            if (scheduleFilter !== 'ALL' && request.scheduleId !== scheduleFilter) return false;
            if (statusFilter !== 'ALL' && state !== statusFilter) return false;
            if (search && !`${request.id} ${request.pickupAddress} ${request.schedule?.area || ''}`.toLowerCase().includes(search)) return false;
            return true;
        });

        body.innerHTML = filtered.map((request) => {
            const state = normalizeState(records[request.id]);
            return `
                <tr>
                    <td><span class="collector-request-id">${escapeHtml(request.id)}</span></td>
                    <td>${escapeHtml(request.schedule?.area || request.scheduleId)}<br><small>${escapeHtml(formatDate(request.schedule?.collectionDate || '2026-08-01'))}</small></td>
                    <td>${escapeHtml(request.pickupAddress)}</td>
                    <td>${request.items.length} item${request.items.length === 1 ? '' : 's'}</td>
                    <td><span class="collector-status collector-status--${stateClass(state)}">${escapeHtml(stateLabel(state))}</span></td>
                    <td><button type="button" class="collector-table-action" data-request-open="${escapeHtml(request.id)}">${escapeHtml(requestActionLabel(state))}</button></td>
                </tr>
            `;
        }).join('');

        const count = document.getElementById('collector-request-result-count');
        if (count) count.textContent = `${filtered.length} request${filtered.length === 1 ? '' : 's'}`;
        const empty = document.getElementById('collector-request-filter-empty');
        if (empty) empty.hidden = filtered.length !== 0;
    };

    const renderRequestsPage = () => {
        if (!document.querySelector('[data-collector-page="requests"]')) return;
        renderAllRequests();
    };

    const buildOriginalRequestHtml = (request, schedule) => `
        <section class="collector-record-original">
            <div class="collector-record-block-heading">
                <h3>Original Request <small>(Read-only)</small></h3>
                <span class="collector-status collector-status--verified">Approved Request</span>
            </div>
            <div class="collector-original-grid">
                <div><span>Request ID</span><strong>${escapeHtml(request.id)}</strong></div>
                <div><span>Schedule</span><strong>${escapeHtml(schedule?.id || request.scheduleId)}</strong></div>
                <div><span>Collection Date</span><strong>${escapeHtml(schedule ? formatDate(schedule.collectionDate) : '—')}</strong></div>
                <div><span>Pickup Address</span><strong>${escapeHtml(request.pickupAddress)}</strong></div>
                <div><span>Public User</span><strong>${escapeHtml(request.publicUser)}</strong></div>
                <div><span>Contact</span><strong>${escapeHtml(request.phone)}</strong></div>
                <div><span>Requested Items</span><strong>${request.items.length}</strong></div>
                <div><span>Area</span><strong>${escapeHtml(schedule?.area || '—')}</strong></div>
            </div>
            <div class="collector-requested-items">
                ${request.items.map((item) => `
                    <div class="collector-requested-item">
                        <strong>${escapeHtml(item.name)}</strong>
                        <span>Qty: ${item.requestedQuantity}</span>
                        <span>Est. ${Number(item.estimatedWeightKg).toFixed(2)} kg</span>
                        <span>${escapeHtml(item.reportedCondition)}${item.conditionNote ? ` · ${escapeHtml(item.conditionNote)}` : ''}</span>
                    </div>
                `).join('')}
            </div>
        </section>
    `;

    const blankItemRecord = (item) => ({
        itemId: item.id,
        actualQuantity: '',
        actualWeightKg: '',
        actualCondition: '',
        itemNote: '',
        itemResult: ''
    });

    const recordForRequest = (request) => {
        const existing = getRecords()[request.id];
        if (!existing) {
            return {
                requestId: request.id,
                scheduleId: request.scheduleId,
                state: 'PENDING_COLLECTION',
                collectorNote: '',
                items: request.items.map(blankItemRecord)
            };
        }
        const byId = new Map((existing.items || []).map((item) => [item.itemId, item]));
        return {
            ...existing,
            items: request.items.map((item) => ({ ...blankItemRecord(item), ...(byId.get(item.id) || {}) }))
        };
    };

    const renderRecordEditorHtml = (request, record) => {
        const state = normalizeState(record);
        const readonly = ['SUBMITTED', 'VERIFIED'].includes(state);
        const editable = !readonly;
        const totalWeight = recordTotalWeight(record);
        const pickupResult = record.pickupResult || '—';

        const itemHtml = request.items.map((item) => {
            const current = record.items.find((entry) => entry.itemId === item.id) || blankItemRecord(item);
            return `
                <article class="collector-record-item" data-record-item="${escapeHtml(item.id)}">
                    <div class="collector-record-item__header">
                        <div>
                            <strong>${escapeHtml(item.name)}</strong>
                            <span>${escapeHtml(item.category)}</span>
                        </div>
                        <span>Requested: ${item.requestedQuantity} · Est. ${Number(item.estimatedWeightKg).toFixed(2)} kg</span>
                    </div>
                    <div class="collector-record-item__grid">
                        <label class="collector-record-field">
                            <span>Actual Quantity *</span>
                            <input type="number" min="0" step="1" inputmode="numeric" data-record-field="actualQuantity" value="${escapeHtml(current.actualQuantity)}" ${readonly ? 'readonly' : ''}>
                        </label>
                        <label class="collector-record-field">
                            <span>Actual Weight (kg) *</span>
                            <input type="number" min="0" step="0.001" inputmode="decimal" data-record-field="actualWeightKg" value="${escapeHtml(current.actualWeightKg)}" ${readonly ? 'readonly' : ''}>
                        </label>
                        <label class="collector-record-field">
                            <span>Actual Condition *</span>
                            <select data-record-field="actualCondition" ${readonly ? 'disabled' : ''}>
                                <option value="">Select condition</option>
                                <option value="WORKING" ${current.actualCondition === 'WORKING' ? 'selected' : ''}>Working</option>
                                <option value="DAMAGED" ${current.actualCondition === 'DAMAGED' ? 'selected' : ''}>Damaged</option>
                                <option value="UNKNOWN" ${current.actualCondition === 'UNKNOWN' ? 'selected' : ''}>Unknown</option>
                            </select>
                        </label>
                        <label class="collector-record-field">
                            <span>Item Note</span>
                            <input type="text" maxlength="180" data-record-field="itemNote" value="${escapeHtml(current.itemNote)}" placeholder="Optional observation" ${readonly ? 'readonly' : ''}>
                        </label>
                    </div>
                </article>
            `;
        }).join('');

        let actions = '';
        if (editable) {
            actions = `
                <button type="button" class="collector-modal-button collector-modal-button--secondary" data-save-draft>Save Draft</button>
                ${state === 'DRAFT' || state === 'REJECTED' ? '<button type="button" class="collector-modal-button collector-modal-button--danger" data-delete-draft>Delete Draft</button>' : ''}
                <button type="button" class="collector-modal-button collector-modal-button--primary" data-submit-record>Submit Record</button>
            `;
        } else {
            actions = '<button type="button" class="collector-modal-button collector-modal-button--secondary" data-collector-modal-close>Close</button>';
        }

        return `
            <section class="collector-record-editor">
                <div class="collector-record-block-heading">
                    <h3>Collection Record</h3>
                    <span class="collector-status collector-status--${stateClass(state)}">${escapeHtml(stateLabel(state))}</span>
                </div>

                ${readonly ? `<p class="collector-record-readonly-note">This record has already been submitted and is read-only while it waits for Municipal Officer verification.</p>` : ''}

                <div class="collector-record-items">${itemHtml}</div>

                <div class="collector-record-note-grid">
                    <label class="collector-record-field">
                        <span>Collection Note</span>
                        <textarea maxlength="300" data-record-note placeholder="Optional overall collection note" ${readonly ? 'readonly' : ''}>${escapeHtml(record.collectorNote || '')}</textarea>
                    </label>
                    <div class="collector-record-total">
                        <span>Total Weight (Auto)</span>
                        <strong data-record-total-weight>${totalWeight.toFixed(2)} kg</strong>
                        <small>Pickup result: <b data-record-pickup-result>${escapeHtml(pickupResult)}</b></small>
                    </div>
                </div>

                ${record.updatedAt ? `<p class="collector-record-readonly-note">Last updated: ${escapeHtml(formatDateTime(record.updatedAt))}${record.submittedAt ? ` · Submitted: ${escapeHtml(formatDateTime(record.submittedAt))}` : ''}</p>` : ''}

                <div class="collector-modal-actions">${actions}</div>
            </section>
        `;
    };

    const openRequestModal = (requestId, trigger = null) => {
        const modal = document.getElementById('collector-record-modal');
        const body = document.getElementById('collector-record-modal-body');
        const title = document.getElementById('collector-record-modal-title');
        if (!modal || !body) return;

        const request = getRequest(requestId);
        if (!request) return;
        const schedule = getSchedule(request.scheduleId);
        const record = recordForRequest(request);
        activeRequestId = requestId;
        modalReturnFocus = trigger || document.activeElement;

        if (title) title.textContent = `Collection Record — ${request.id}`;
        body.innerHTML = `${buildOriginalRequestHtml(request, schedule)}${renderRecordEditorHtml(request, record)}`;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => modal.querySelector('.collector-modal__close')?.focus(), 0);
        refreshRecordDerivedPreview();
    };

    const closeRequestModal = () => {
        const modal = document.getElementById('collector-record-modal');
        if (!modal || modal.hidden) return;
        modal.hidden = true;
        document.body.style.overflow = '';
        activeRequestId = null;
        if (modalReturnFocus instanceof HTMLElement) modalReturnFocus.focus();
        modalReturnFocus = null;
    };

    const collectModalDraft = ({ requireComplete = false } = {}) => {
        const request = getRequest(activeRequestId);
        const modal = document.getElementById('collector-record-modal');
        if (!request || !modal) return { ok: false, error: 'Request is unavailable.' };

        const items = [];
        for (const requestItem of request.items) {
            const block = modal.querySelector(`[data-record-item="${CSS.escape(requestItem.id)}"]`);
            if (!block) continue;
            const quantityRaw = block.querySelector('[data-record-field="actualQuantity"]')?.value ?? '';
            const weightRaw = block.querySelector('[data-record-field="actualWeightKg"]')?.value ?? '';
            const condition = block.querySelector('[data-record-field="actualCondition"]')?.value ?? '';
            const itemNote = block.querySelector('[data-record-field="itemNote"]')?.value?.trim() ?? '';

            if (requireComplete) {
                if (quantityRaw === '' || weightRaw === '' || !condition) {
                    return { ok: false, error: `Complete Actual Quantity, Actual Weight and Actual Condition for ${requestItem.name}.` };
                }
            }

            const quantity = quantityRaw === '' ? '' : Number(quantityRaw);
            const weight = weightRaw === '' ? '' : Number(weightRaw);

            if (quantity !== '' && (!Number.isInteger(quantity) || quantity < 0)) {
                return { ok: false, error: `Actual Quantity for ${requestItem.name} must be a whole number of 0 or more.` };
            }
            if (weight !== '' && (!Number.isFinite(weight) || weight < 0)) {
                return { ok: false, error: `Actual Weight for ${requestItem.name} must be 0 or more.` };
            }

            items.push({
                itemId: requestItem.id,
                actualQuantity: quantity,
                actualWeightKg: weight,
                actualCondition: condition,
                itemNote,
                itemResult: quantity === '' ? '' : deriveItemResult(quantity, requestItem.requestedQuantity)
            });
        }

        const collectorNote = modal.querySelector('[data-record-note]')?.value?.trim() ?? '';
        const pickupResult = items.every((item) => item.itemResult) ? derivePickupResult(items) : '';

        return {
            ok: true,
            record: {
                requestId: request.id,
                scheduleId: request.scheduleId,
                collectorUserId: collector.userId,
                collectorNote,
                items,
                pickupResult
            }
        };
    };

    const refreshRecordDerivedPreview = () => {
        const draft = collectModalDraft({ requireComplete: false });
        if (!draft.ok) return;
        const weight = recordTotalWeight(draft.record);
        const totalEl = document.querySelector('#collector-record-modal [data-record-total-weight]');
        const resultEl = document.querySelector('#collector-record-modal [data-record-pickup-result]');
        if (totalEl) totalEl.textContent = `${weight.toFixed(2)} kg`;
        if (resultEl) resultEl.textContent = draft.record.pickupResult || '—';
    };

    const saveDraft = () => {
        const draft = collectModalDraft({ requireComplete: false });
        if (!draft.ok) {
            window.alert(draft.error);
            return;
        }
        const records = getRecords();
        const previous = records[activeRequestId] || {};
        records[activeRequestId] = {
            ...previous,
            ...draft.record,
            state: 'DRAFT',
            updatedAt: new Date().toISOString(),
            submittedAt: null
        };
        saveRecords(records);
        showFeedback(`${activeRequestId} draft saved.`);
        const currentId = activeRequestId;
        renderAll();
        openRequestModal(currentId);
    };

    const submitRecord = () => {
        const draft = collectModalDraft({ requireComplete: true });
        if (!draft.ok) {
            window.alert(draft.error);
            return;
        }
        if (!window.confirm('Submit this collection record? After submission it becomes read-only while awaiting Municipal Officer verification.')) return;

        const records = getRecords();
        const previous = records[activeRequestId] || {};
        records[activeRequestId] = {
            ...previous,
            ...draft.record,
            state: 'SUBMITTED',
            updatedAt: new Date().toISOString(),
            submittedAt: new Date().toISOString()
        };
        saveRecords(records);
        showFeedback(`${activeRequestId} submitted successfully.`);
        const currentId = activeRequestId;
        renderAll();
        openRequestModal(currentId);
    };

    const deleteDraft = () => {
        if (!activeRequestId) return;
        const records = getRecords();
        const current = records[activeRequestId];
        if (!current || !['DRAFT', 'REJECTED'].includes(normalizeState(current))) return;
        if (!window.confirm(`Delete the draft collection record for ${activeRequestId}?`)) return;
        delete records[activeRequestId];
        saveRecords(records);
        showFeedback(`${activeRequestId} draft deleted.`);
        closeRequestModal();
        renderAll();
    };

    const submitSchedule = (scheduleId) => {
        const schedule = getSchedule(scheduleId);
        if (!schedule) return;
        const records = getRecords();
        const scheduleRequests = getScheduleRequests(scheduleId);
        const allSubmitted = scheduleRequests.length > 0 && scheduleRequests.every((request) => ['SUBMITTED', 'VERIFIED'].includes(normalizeState(records[request.id])));
        if (!allSubmitted) {
            window.alert('Submit every request collection record before submitting the whole schedule for verification.');
            return;
        }
        if (!window.confirm(`Submit ${schedule.area} schedule collection for Municipal Officer verification?`)) return;

        const submissions = getSubmissions();
        submissions[scheduleId] = {
            scheduleId,
            collectorUserId: collector.userId,
            status: 'PENDING',
            submittedAt: new Date().toISOString()
        };
        saveSubmissions(submissions);
        showFeedback(`${schedule.id} submitted for Municipal Officer verification.`);
        renderAll();
    };

    const renderAll = () => {
        renderSchedulesPage();
        renderRequestsPage();
    };

    const trapModalFocus = (event) => {
        const modal = document.getElementById('collector-record-modal');
        if (!modal || modal.hidden) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeRequestModal();
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]')]
            .filter((el) => !el.closest('[hidden]'));
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    document.addEventListener('click', (event) => {
        const scheduleButton = event.target.closest('[data-schedule-select]');
        if (scheduleButton) {
            setSelectedSchedule(scheduleButton.dataset.scheduleSelect);
            document.getElementById('collector-selected-schedule')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        const requestButton = event.target.closest('[data-request-open]');
        if (requestButton) {
            openRequestModal(requestButton.dataset.requestOpen, requestButton);
            return;
        }

        if (event.target.closest('[data-collector-modal-close]')) {
            closeRequestModal();
            return;
        }

        if (event.target.closest('[data-save-draft]')) {
            saveDraft();
            return;
        }

        if (event.target.closest('[data-submit-record]')) {
            submitRecord();
            return;
        }

        if (event.target.closest('[data-delete-draft]')) {
            deleteDraft();
            return;
        }

        const submitScheduleButton = event.target.closest('[data-submit-schedule]');
        if (submitScheduleButton) {
            submitSchedule(submitScheduleButton.dataset.submitSchedule);
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.closest('#collector-record-modal') && (event.target.matches('[data-record-field]') || event.target.matches('[data-record-note]'))) {
            refreshRecordDerivedPreview();
        }
        if (event.target.matches('#collector-request-search')) renderAllRequests();
    });

    document.addEventListener('change', (event) => {
        if (event.target.closest('#collector-record-modal') && event.target.matches('[data-record-field]')) {
            refreshRecordDerivedPreview();
        }
        if (event.target.matches('#collector-request-schedule-filter, #collector-request-status-filter')) renderAllRequests();
    });

    document.addEventListener('keydown', trapModalFocus);

    document.addEventListener('click', (event) => {
        if (event.target.matches('#collector-print-summary')) {
            window.print();
        }
    });

    renderAll();
})();