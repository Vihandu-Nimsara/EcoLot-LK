(() => {
    const storageKey = 'ecolot_officer_campaigns_v2';
    const dialog = document.querySelector('[data-campaign-dialog]');
    const openButton = document.querySelector('[data-open-campaign-dialog]');
    const closeButtons = document.querySelectorAll('[data-close-campaign-dialog]');
    const form = document.querySelector('[data-campaign-form]');
    const tableBody = document.querySelector('[data-campaign-table-body]');
    const toast = document.querySelector('[data-campaign-toast]');
    const toastMessage = document.querySelector('[data-campaign-toast-message]');
    const dialogEyebrow = document.querySelector('[data-campaign-dialog-eyebrow]');
    const dialogTitle = document.querySelector('[data-campaign-dialog-title]');
    const dialogDescription = document.querySelector('[data-campaign-dialog-description]');
    const statusMessage = document.querySelector('[data-campaign-status-message]');
    const submitButton = document.querySelector('[data-campaign-submit]');
    const formError = document.querySelector('[data-campaign-form-error]');

    if (!dialog || !openButton || !form || !tableBody || !submitButton) {
        return;
    }

    const nameInput = form.querySelector('[name="name"]');
    const periodInput = form.querySelector('[name="period"]');
    const statusInput = form.querySelector('[name="status"]');
    let editingCampaignId = null;
    let activeTrigger = openButton;
    let toastTimer;

    const readInitialCampaigns = () => Array.from(tableBody.querySelectorAll('tr')).map((row) => {
        const cells = row.cells;
        const periodMatch = cells[2].textContent.match(/(\d{1,2})\s*\/\s*(\d{4})/);

        return {
            id: Number.parseInt(cells[0].textContent.replace(/\D/g, ''), 10),
            name: cells[1].textContent.trim(),
            period: row.dataset.campaignPeriod
                || (periodMatch ? `${periodMatch[2]}-${String(periodMatch[1]).padStart(2, '0')}` : ''),
            status: cells[3].textContent.trim() === 'CLOSED' ? 'CLOSED' : 'OPEN',
            createdBy: cells[4].textContent.trim(),
            createdDate: row.dataset.createdDate || cells[5].textContent.trim(),
        };
    }).filter(isValidCampaign);

    const isValidCampaign = (campaign) => (
        Number.isInteger(campaign?.id)
        && typeof campaign?.name === 'string'
        && /^\d{4}-\d{2}$/.test(campaign?.period)
        && ['OPEN', 'CLOSED'].includes(campaign?.status)
        && typeof campaign?.createdBy === 'string'
        && /^\d{4}-\d{2}-\d{2}$/.test(campaign?.createdDate)
    );

    const initialCampaigns = readInitialCampaigns();

    const loadCampaigns = () => {
        try {
            const savedCampaigns = JSON.parse(window.localStorage.getItem(storageKey));

            if (Array.isArray(savedCampaigns) && savedCampaigns.every(isValidCampaign)) {
                return savedCampaigns;
            }
        } catch (error) {
            // The in-page workflow still works when browser storage is unavailable.
        }

        return initialCampaigns;
    };

    let campaigns = loadCampaigns();

    const saveCampaigns = () => {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(campaigns));
            return true;
        } catch (error) {
            return false;
        }
    };

    const addCell = (row, content) => {
        const cell = document.createElement('td');

        if (content instanceof Node) {
            cell.append(content);
        } else {
            cell.textContent = content;
        }

        row.append(cell);
    };

    const formatPeriod = (period) => {
        const [year, month] = period.split('-').map(Number);
        return new Intl.DateTimeFormat('en-GB', { month: 'short', year: 'numeric' })
            .format(new Date(year, month - 1, 1));
    };

    const formatDate = (date) => new Intl.DateTimeFormat('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC',
    }).format(new Date(`${date}T00:00:00Z`));

    const renderCampaigns = () => {
        tableBody.replaceChildren();

        campaigns.forEach((campaign) => {
            const row = document.createElement('tr');
            const status = document.createElement('span');
            const editButton = document.createElement('button');
            row.dataset.campaignId = String(campaign.id);
            row.dataset.campaignPeriod = campaign.period;
            row.dataset.createdDate = campaign.createdDate;
            status.className = `status ${campaign.status.toLowerCase()}`;
            status.textContent = campaign.status;
            editButton.type = 'button';
            editButton.className = 'edit-btn';
            editButton.textContent = 'Edit';

            addCell(row, `#${campaign.id}`);
            addCell(row, campaign.name);
            addCell(row, formatPeriod(campaign.period));
            addCell(row, status);
            addCell(row, campaign.createdBy);
            addCell(row, formatDate(campaign.createdDate));
            addCell(row, editButton);
            tableBody.append(row);
        });
    };

    const setDefaultPeriod = () => {
        const today = new Date();
        periodInput.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    };

    const setStatusHelp = () => {
        statusMessage.textContent = statusInput.value === 'CLOSED'
            ? 'Closed campaigns are retained for reporting but cannot accept new schedules.'
            : 'Open campaigns are available when creating area schedules.';
    };

    const clearFormError = () => {
        formError.hidden = true;
        formError.textContent = '';
    };

    const showFormError = (message) => {
        formError.textContent = message;
        formError.hidden = false;
    };

    const showDialog = () => {
        clearFormError();
        setStatusHelp();
        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        nameInput.focus();
    };

    const openCreateDialog = () => {
        editingCampaignId = null;
        activeTrigger = openButton;
        form.reset();
        setDefaultPeriod();
        statusInput.value = 'OPEN';
        dialogEyebrow.textContent = 'New monthly campaign';
        dialogTitle.textContent = 'Create Campaign';
        dialogDescription.textContent = 'Set up the campaign period and availability status.';
        submitButton.textContent = 'Create Campaign';
        showDialog();
    };

    const openEditDialog = (campaign, trigger) => {
        editingCampaignId = campaign.id;
        activeTrigger = trigger;
        nameInput.value = campaign.name;
        periodInput.value = campaign.period;
        statusInput.value = campaign.status;
        dialogEyebrow.textContent = `Campaign #${campaign.id}`;
        dialogTitle.textContent = 'Edit Campaign';
        dialogDescription.textContent = 'Update the campaign name, collection period, or status.';
        submitButton.textContent = 'Save Changes';
        showDialog();
    };

    const closeDialog = () => {
        dialog.hidden = true;
        document.body.style.overflow = '';
        form.reset();
        clearFormError();

        if (activeTrigger?.isConnected) {
            activeTrigger.focus();
        }
    };

    const getNextCampaignId = () => (
        campaigns.length ? Math.max(...campaigns.map((campaign) => campaign.id)) + 1 : 1
    );

    const getCurrentDate = () => {
        const today = new Date();

        return [today.getFullYear(), today.getMonth() + 1, today.getDate()]
            .map((part) => String(part).padStart(2, '0'))
            .join('-');
    };

    const showToast = (message) => {
        if (!toast || !toastMessage) {
            return;
        }

        window.clearTimeout(toastTimer);
        toastMessage.textContent = message;
        toast.hidden = false;
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 3200);
    };

    openButton.addEventListener('click', openCreateDialog);
    closeButtons.forEach((button) => button.addEventListener('click', closeDialog));

    tableBody.addEventListener('click', (event) => {
        const editButton = event.target.closest('.edit-btn');

        if (!editButton) {
            return;
        }

        const row = editButton.closest('tr');
        const campaignId = Number.parseInt(row.dataset.campaignId, 10);
        const campaign = campaigns.find((item) => item.id === campaignId);

        if (campaign) {
            openEditDialog(campaign, editButton);
        }
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            closeDialog();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) {
            closeDialog();
        }
    });

    [nameInput, periodInput].forEach((input) => input.addEventListener('input', clearFormError));
    statusInput.addEventListener('change', () => {
        clearFormError();
        setStatusHelp();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        const name = nameInput.value.trim();
        const period = periodInput.value;
        const status = statusInput.value === 'CLOSED' ? 'CLOSED' : 'OPEN';
        const duplicatePeriod = campaigns.some((campaign) => (
            campaign.period === period && campaign.id !== editingCampaignId
        ));

        if (duplicatePeriod) {
            showFormError('A campaign already exists for the selected month and year.');
            periodInput.focus();
            return;
        }

        if (editingCampaignId !== null) {
            campaigns = campaigns.map((campaign) => campaign.id === editingCampaignId
                ? { ...campaign, name, period, status }
                : campaign);
            saveCampaigns();
            closeDialog();
            renderCampaigns();
            showToast('Campaign changes saved successfully.');
            return;
        }

        campaigns.unshift({
            id: getNextCampaignId(),
            name,
            period,
            status,
            createdBy: 'Nadeesha Perera',
            createdDate: getCurrentDate(),
        });
        saveCampaigns();
        closeDialog();
        renderCampaigns();
        showToast('Campaign created and saved in this browser.');
    });

    renderCampaigns();
})();
