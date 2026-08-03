(() => {
    const storageKey = 'ecolot_officer_flagged_reviews_v2';
    const filterForm = document.querySelector('[data-flagged-filter-form]');
    const tableBody = document.querySelector('[data-flagged-table-body]');
    const tableWrapper = document.querySelector('.flagged-table-wrapper');
    const resultCount = document.querySelector('[data-filter-result-count]');
    const emptyState = document.querySelector('[data-flagged-empty-state]');
    const dialog = document.querySelector('[data-review-dialog]');
    const closeButtons = document.querySelectorAll('[data-close-review-dialog]');
    const reviewForm = document.querySelector('[data-review-form]');
    const decisionInput = reviewForm?.querySelector('[name="decision"]');
    const noteInput = reviewForm?.querySelector('[name="note"]');
    const formError = document.querySelector('[data-review-form-error]');
    const toast = document.querySelector('[data-review-toast]');

    if (!filterForm || !tableBody || !dialog || !reviewForm || !decisionInput || !noteInput) {
        return;
    }

    const campaignFilter = filterForm.querySelector('[name="campaign"]');
    const scheduleFilter = filterForm.querySelector('[name="schedule"]');
    const statusFilter = filterForm.querySelector('[name="review_status"]');
    const requestIdOutput = document.querySelector('[data-review-request-id]');
    const userOutput = document.querySelector('[data-review-user]');
    const scheduleOutput = document.querySelector('[data-review-schedule]');
    const categoryOutput = document.querySelector('[data-review-category]');
    const reasonOutput = document.querySelector('[data-review-reason]');
    const statusLabels = {
        PENDING_REVIEW: 'PENDING REVIEW',
        APPROVED: 'APPROVED',
        REJECTED: 'REJECTED',
    };
    let activeRow = null;
    let activeTrigger = null;
    let toastTimer;

    const loadReviews = () => {
        try {
            const reviews = JSON.parse(window.localStorage.getItem(storageKey));
            return reviews && typeof reviews === 'object' && !Array.isArray(reviews) ? reviews : {};
        } catch (error) {
            return {};
        }
    };

    let reviews = loadReviews();

    const saveReviews = () => {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(reviews));
        } catch (error) {
            // Keep the current page workflow available when browser storage is disabled.
        }
    };

    const setRowStatus = (row, status) => {
        const normalizedStatus = statusLabels[status] ? status : 'PENDING_REVIEW';
        const badge = row.querySelector('.review-status');
        const reviewButton = row.querySelector('.review-btn');

        row.dataset.reviewStatus = normalizedStatus;
        badge.textContent = statusLabels[normalizedStatus];
        badge.className = `review-status ${normalizedStatus === 'PENDING_REVIEW'
            ? 'pending'
            : normalizedStatus.toLowerCase()}`;
        reviewButton.textContent = normalizedStatus === 'PENDING_REVIEW' ? 'Review' : 'Edit Review';
    };

    const restoreReviews = () => {
        tableBody.querySelectorAll('tr').forEach((row) => {
            const savedReview = reviews[row.dataset.requestId];
            if (savedReview) setRowStatus(row, savedReview.status);
        });
    };

    const applyFilters = () => {
        let visibleRows = 0;

        tableBody.querySelectorAll('tr').forEach((row) => {
            const matchesCampaign = !campaignFilter.value
                || row.dataset.campaign === campaignFilter.value;
            const matchesSchedule = !scheduleFilter.value
                || row.dataset.schedule === scheduleFilter.value;
            const matchesStatus = !statusFilter.value
                || row.dataset.reviewStatus === statusFilter.value;
            const isVisible = matchesCampaign && matchesSchedule && matchesStatus;

            row.hidden = !isVisible;
            if (isVisible) visibleRows += 1;
        });

        resultCount.textContent = `${visibleRows} request${visibleRows === 1 ? '' : 's'} shown`;
        emptyState.hidden = visibleRows !== 0;
        tableWrapper.hidden = visibleRows === 0;
    };

    const clearFormError = () => {
        formError.hidden = true;
        formError.textContent = '';
    };

    const openReviewDialog = (row, trigger) => {
        const cells = row.cells;
        const requestId = row.dataset.requestId;
        const savedReview = reviews[requestId];

        activeRow = row;
        activeTrigger = trigger;
        requestIdOutput.textContent = requestId;
        userOutput.textContent = cells[1].textContent.trim();
        scheduleOutput.textContent = cells[2].textContent.trim().replace(/\s+/g, ' ');
        categoryOutput.textContent = cells[3].textContent.trim();
        reasonOutput.textContent = cells[4].textContent.trim();
        decisionInput.value = savedReview?.status ?? row.dataset.reviewStatus;
        noteInput.value = savedReview?.note ?? '';
        clearFormError();
        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        decisionInput.focus();
    };

    const closeReviewDialog = () => {
        dialog.hidden = true;
        document.body.style.overflow = '';
        reviewForm.reset();
        clearFormError();
        if (activeTrigger?.isConnected) activeTrigger.focus();
    };

    const showToast = () => {
        if (!toast) return;
        window.clearTimeout(toastTimer);
        toast.hidden = false;
        toastTimer = window.setTimeout(() => { toast.hidden = true; }, 3200);
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    filterForm.addEventListener('reset', () => {
        window.requestAnimationFrame(applyFilters);
    });

    tableBody.addEventListener('click', (event) => {
        const reviewButton = event.target.closest('.review-btn');
        if (!reviewButton) return;
        openReviewDialog(reviewButton.closest('tr'), reviewButton);
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeReviewDialog));
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) closeReviewDialog();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) closeReviewDialog();
    });

    [decisionInput, noteInput].forEach((input) => {
        input.addEventListener('input', clearFormError);
        input.addEventListener('change', clearFormError);
    });

    reviewForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const status = statusLabels[decisionInput.value]
            ? decisionInput.value
            : 'PENDING_REVIEW';
        const note = noteInput.value.trim();

        if (status === 'REJECTED' && note === '') {
            formError.textContent = 'Add an officer note explaining why this request is rejected.';
            formError.hidden = false;
            noteInput.focus();
            return;
        }

        reviews[activeRow.dataset.requestId] = { status, note };
        saveReviews();
        setRowStatus(activeRow, status);
        closeReviewDialog();
        applyFilters();
        showToast();
    });

    restoreReviews();
    applyFilters();
})();
