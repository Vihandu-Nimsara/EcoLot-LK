(() => {
    'use strict';

    const filterForm = document.querySelector('[data-report-filter-form]');
    const printButton = document.querySelector('[data-print-report]');
    const performanceBody = document.querySelector('[data-report-performance-body]');
    const elotsBody = document.querySelector('[data-report-elots-body]');

    if (!filterForm || !printButton || !performanceBody || !elotsBody) {
        return;
    }

    const julyPerformance = [
        { schedule: 'SCH-0004', area: 'Rajagiriya', date: '23 Jul 2026', assigned: 4, collected: 3, pending: 1, verified: 3, weight: '31.50 kg' },
        { schedule: 'SCH-0003', area: 'Kollupitiya', date: '16 Jul 2026', assigned: 2, collected: 2, pending: 0, verified: 2, weight: '18.00 kg' },
    ];

    const augustPerformance = [
        { schedule: 'SCH-0011', area: 'Moratuwa', date: '02 Aug 2026', assigned: 4, collected: 0, pending: 4, verified: 0, weight: '0.00 kg' },
    ];

    const julyElots = [
        { code: 'EL-001', category: 'Domestic E-Waste', weight: '5.50 kg', bids: 2, status: 'OPEN_FOR_BIDDING', winner: 'Not Selected' },
        { code: 'EL-002', category: 'Office E-Waste', weight: '9.00 kg', bids: 2, status: 'AWARDED', winner: 'GreenCycle Lanka (Pvt) Ltd' },
        { code: 'EL-003', category: 'Industrial E-Waste', weight: '10.00 kg', bids: 0, status: 'COMPLETED', winner: 'Ceylon Circular Metals (Pvt) Ltd' },
    ];

    const reportData = {
        '': {
            metrics: { scheduledAreas: '3', openSchedules: '1', submittedRequests: '10', verifiedCollections: '5', activeElots: '2', totalWeight: '49.50 kg' },
            requests: { submitted: 10, approved: 8, assigned: 6, collected: 5, rejected: 2 },
            performance: [...julyPerformance, ...augustPerformance],
            elots: julyElots,
            feedback: { open: 1, inReview: 1, resolved: 1, closed: 1 },
        },
        'july-2026': {
            metrics: { scheduledAreas: '2', openSchedules: '0', submittedRequests: '6', verifiedCollections: '5', activeElots: '2', totalWeight: '49.50 kg' },
            requests: { submitted: 6, approved: 6, assigned: 5, collected: 5, rejected: 0 },
            performance: julyPerformance,
            elots: julyElots,
            feedback: { open: 1, inReview: 1, resolved: 1, closed: 1 },
        },
        'august-2026': {
            metrics: { scheduledAreas: '1', openSchedules: '1', submittedRequests: '4', verifiedCollections: '0', activeElots: '0', totalWeight: '0.00 kg' },
            requests: { submitted: 4, approved: 2, assigned: 1, collected: 0, rejected: 2 },
            performance: augustPerformance,
            elots: [],
            feedback: { open: 0, inReview: 0, resolved: 0, closed: 0 },
        },
    };

    const createCell = (text) => {
        const cell = document.createElement('td');
        cell.textContent = String(text);
        return cell;
    };

    const renderPerformance = (items) => {
        performanceBody.replaceChildren();
        items.forEach((item) => {
            const row = document.createElement('tr');
            [item.schedule, item.area, item.date, item.assigned, item.collected, item.pending, item.verified, item.weight]
                .forEach((value) => row.appendChild(createCell(value)));
            performanceBody.appendChild(row);
        });
    };

    const renderElots = (items) => {
        elotsBody.replaceChildren();
        if (!items.length) {
            const row = document.createElement('tr');
            const cell = createCell('No E-Lots were created for this campaign.');
            cell.colSpan = 6;
            cell.className = 'report-table-empty';
            row.appendChild(cell);
            elotsBody.appendChild(row);
            return;
        }

        items.forEach((item) => {
            const row = document.createElement('tr');
            [item.code, item.category, item.weight, item.bids].forEach((value) => row.appendChild(createCell(value)));
            const statusCell = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = `report-status ${item.status === 'OPEN_FOR_BIDDING' ? 'open' : item.status.toLowerCase()}`;
            badge.textContent = item.status.replaceAll('_', ' ');
            statusCell.appendChild(badge);
            row.append(statusCell, createCell(item.winner));
            elotsBody.appendChild(row);
        });
    };

    const renderReport = () => {
        const data = reportData[filterForm.elements.campaign.value] || reportData[''];
        document.querySelectorAll('[data-report-metric]').forEach((element) => {
            element.textContent = data.metrics[element.dataset.reportMetric];
        });
        document.querySelectorAll('[data-request-metric]').forEach((element) => {
            element.textContent = data.requests[element.dataset.requestMetric];
        });
        document.querySelectorAll('[data-feedback-metric]').forEach((element) => {
            element.textContent = data.feedback[element.dataset.feedbackMetric];
        });
        renderPerformance(data.performance);
        renderElots(data.elots);
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        renderReport();
    });
    filterForm.addEventListener('reset', () => requestAnimationFrame(renderReport));
    printButton.addEventListener('click', () => window.print());

    renderReport();
})();
