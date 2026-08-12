(() => {
    const storageKey = 'ecolot_officer_area_schedules_v2';
    const campaignStorageKey = 'ecolot_officer_campaigns_v2';
    const dialog = document.querySelector('[data-schedule-dialog]');
    const openButton = document.querySelector('[data-open-schedule-dialog]');
    const closeButtons = document.querySelectorAll('[data-close-schedule-dialog]');
    const form = document.querySelector('[data-schedule-form]');
    const tableBody = document.querySelector('[data-schedule-table-body]');
    const filterInput = document.querySelector('#campaign-filter');
    const formError = document.querySelector('[data-schedule-form-error]');
    const toast = document.querySelector('[data-schedule-toast]');
    const toastMessage = document.querySelector('[data-schedule-toast-message]');
    const dialogEyebrow = document.querySelector('[data-schedule-dialog-eyebrow]');
    const dialogTitle = document.querySelector('[data-schedule-dialog-title]');
    const dialogDescription = document.querySelector('[data-schedule-dialog-description]');
    const submitButton = document.querySelector('[data-schedule-submit]');

    if (!dialog || !openButton || !form || !tableBody || !submitButton) {
        return;
    }

    const campaignInput = form.querySelector('[name="campaign"]');
    const areaInput = form.querySelector('[name="area"]');
    const cutoffInput = form.querySelector('[name="cutoff"]');
    const collectionDateInput = form.querySelector('[name="collection_date"]');
    const capacityInput = form.querySelector('[name="capacity"]');
    const statusInput = form.querySelector('[name="status"]');
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let editingScheduleId = null;
    let activeTrigger = openButton;
    let toastTimer;

    const loadCampaigns = () => {
        try {
            const storedCampaigns = JSON.parse(window.localStorage.getItem(campaignStorageKey));

            if (Array.isArray(storedCampaigns) && storedCampaigns.length) {
                return storedCampaigns.map((campaign) => ({
                    key: `campaign-${campaign.id}`,
                    name: campaign.name,
                    period: campaign.period,
                    status: campaign.status,
                }));
            }
        } catch (error) {
            // Fall back to the starter campaign when browser storage is unavailable.
        }

        return [
            {
                key: 'campaign-1',
                name: 'Colombo Municipal E-Waste Campaign',
                period: '2026-08',
                status: 'OPEN',
            },
            {
                key: 'campaign-2',
                name: 'July E-Waste Collection Campaign',
                period: '2026-07',
                status: 'CLOSED',
            },
        ];
    };

    const campaigns = loadCampaigns();

    const formatCampaignPeriod = (period) => {
        const [year, month] = period.split('-');
        return `${Number(month)}/${year}`;
    };

    const parseDisplayDate = (displayDate) => {
        const [day, monthName, year] = displayDate.trim().split(/\s+/);
        const month = monthNames.indexOf(monthName) + 1;
        return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    };

    const formatDisplayDate = (isoDate) => {
        const [year, month, day] = isoDate.split('-');
        return `${Number(day)} ${monthNames[Number(month) - 1]} ${year}`;
    };

    const readInitialSchedules = () => Array.from(tableBody.querySelectorAll('tr')).map((row) => {
        const cells = row.cells;
        return {
            id: Number.parseInt(cells[0].textContent.replace(/\D/g, ''), 10),
            campaignKey: 'campaign-1',
            campaignName: cells[1].textContent.trim().split(/\n/)[0],
            campaignPeriod: '2026-08',
            area: cells[2].textContent.trim(),
            postalCode: cells[3].textContent.trim(),
            collectionDate: parseDisplayDate(cells[4].textContent),
            cutoffDate: parseDisplayDate(cells[5].textContent),
            requests: Number.parseInt(cells[6].textContent, 10),
            capacity: Number.parseInt(cells[7].textContent, 10),
            status: cells[8].textContent.trim() === 'CLOSED' ? 'CLOSED' : 'OPEN',
        };
    });

    const initialSchedules = readInitialSchedules();

    const isValidSchedule = (schedule) => (
        Number.isInteger(schedule?.id)
        && typeof schedule?.campaignKey === 'string'
        && typeof schedule?.campaignName === 'string'
        && /^\d{4}-\d{2}$/.test(schedule?.campaignPeriod)
        && typeof schedule?.area === 'string'
        && typeof schedule?.postalCode === 'string'
        && /^\d{4}-\d{2}-\d{2}$/.test(schedule?.collectionDate)
        && /^\d{4}-\d{2}-\d{2}$/.test(schedule?.cutoffDate)
        && Number.isInteger(schedule?.requests)
        && Number.isInteger(schedule?.capacity)
        && ['OPEN', 'CLOSED'].includes(schedule?.status)
    );

    const loadSchedules = () => {
        try {
            const storedSchedules = JSON.parse(window.localStorage.getItem(storageKey));

            if (Array.isArray(storedSchedules) && storedSchedules.every(isValidSchedule)) {
                return storedSchedules;
            }
        } catch (error) {
            // The in-page workflow still works when browser storage is unavailable.
        }

        return initialSchedules;
    };

    let schedules = loadSchedules();

    const saveSchedules = () => {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(schedules));
        } catch (error) {
            // Keep the current in-page session working if storage is unavailable.
        }
    };

    const syncCampaignDetails = () => {
        schedules = schedules.map((schedule) => {
            const campaign = campaigns.find((item) => item.key === schedule.campaignKey);
            return campaign
                ? { ...schedule, campaignName: campaign.name, campaignPeriod: campaign.period }
                : schedule;
        });
    };

    const buildCampaignOptions = () => {
        campaignInput.replaceChildren();
        const placeholder = new Option('Select campaign', '');
        campaignInput.append(placeholder);

        campaigns.filter((campaign) => campaign.status === 'OPEN').forEach((campaign) => {
            const option = new Option(
                `${campaign.name} — ${formatCampaignPeriod(campaign.period)}`,
                campaign.key
            );
            option.dataset.campaignName = campaign.name;
            option.dataset.campaignPeriod = campaign.period;
            campaignInput.append(option);
        });

        if (filterInput) {
            filterInput.replaceChildren(new Option('All Campaigns', 'all'));
            campaigns.forEach((campaign) => {
                filterInput.append(new Option(
                    `${campaign.name} — ${formatCampaignPeriod(campaign.period)}`,
                    campaign.key
                ));
            });
        }
    };

    const addCell = (row, content) => {
        const cell = document.createElement('td');
        if (content instanceof Node) cell.append(content);
        else cell.textContent = content;
        row.append(cell);
    };

    const renderSchedules = () => {
        tableBody.replaceChildren();

        schedules.forEach((schedule) => {
            const row = document.createElement('tr');
            const campaignCell = document.createElement('span');
            const status = document.createElement('span');
            const editButton = document.createElement('button');

            row.dataset.scheduleId = String(schedule.id);
            row.dataset.campaignKey = schedule.campaignKey;
            campaignCell.append(
                document.createTextNode(schedule.campaignName),
                document.createElement('br'),
                document.createTextNode(formatCampaignPeriod(schedule.campaignPeriod))
            );
            status.className = `status ${schedule.status.toLowerCase()}`;
            status.textContent = schedule.status;
            editButton.type = 'button';
            editButton.className = 'edit-btn';
            editButton.textContent = 'Edit';

            addCell(row, `SCH-${String(schedule.id).padStart(4, '0')}`);
            addCell(row, campaignCell);
            addCell(row, schedule.area);
            addCell(row, schedule.postalCode);
            addCell(row, formatDisplayDate(schedule.collectionDate));
            addCell(row, formatDisplayDate(schedule.cutoffDate));
            addCell(row, String(schedule.requests));
            addCell(row, String(schedule.capacity));
            addCell(row, status);
            addCell(row, editButton);
            tableBody.append(row);
        });

        applyCampaignFilter();
    };

    const applyCampaignFilter = () => {
        const selectedCampaign = filterInput?.value ?? 'all';
        tableBody.querySelectorAll('tr').forEach((row) => {
            row.hidden = selectedCampaign !== 'all' && row.dataset.campaignKey !== selectedCampaign;
        });
    };

    const clearFormError = () => {
        formError.hidden = true;
        formError.textContent = '';
    };

    const showFormError = (message, input) => {
        formError.textContent = message;
        formError.hidden = false;
        input?.focus();
    };

    const showDialog = () => {
        clearFormError();
        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        campaignInput.focus();
    };

    const openCreateDialog = () => {
        editingScheduleId = null;
        activeTrigger = openButton;
        form.reset();
        capacityInput.value = '30';
        statusInput.value = 'OPEN';
        dialogEyebrow.textContent = 'New area schedule';
        dialogTitle.textContent = 'Create Schedule';
        dialogDescription.textContent = 'Assign a collection date and request capacity to a postal-code area.';
        submitButton.textContent = 'Create Schedule';
        showDialog();
    };

    const openEditDialog = (schedule, trigger) => {
        editingScheduleId = schedule.id;
        activeTrigger = trigger;
        campaignInput.value = schedule.campaignKey;
        areaInput.value = schedule.postalCode;
        cutoffInput.value = schedule.cutoffDate;
        collectionDateInput.value = schedule.collectionDate;
        capacityInput.value = String(schedule.capacity);
        statusInput.value = schedule.status;
        dialogEyebrow.textContent = `Schedule SCH-${String(schedule.id).padStart(4, '0')}`;
        dialogTitle.textContent = 'Edit Schedule';
        dialogDescription.textContent = 'Update the area, dates, capacity, or schedule status.';
        submitButton.textContent = 'Save Changes';
        showDialog();
    };

    const closeDialog = () => {
        dialog.hidden = true;
        document.body.style.overflow = '';
        form.reset();
        clearFormError();
        if (activeTrigger?.isConnected) activeTrigger.focus();
    };

    const showToast = (message) => {
        if (!toast || !toastMessage) return;
        window.clearTimeout(toastTimer);
        toastMessage.textContent = message;
        toast.hidden = false;
        toastTimer = window.setTimeout(() => { toast.hidden = true; }, 3200);
    };

    const getNextScheduleId = () => (
        schedules.length ? Math.max(...schedules.map((schedule) => schedule.id)) + 1 : 1
    );

    openButton.addEventListener('click', openCreateDialog);
    closeButtons.forEach((button) => button.addEventListener('click', closeDialog));
    filterInput?.addEventListener('change', applyCampaignFilter);

    tableBody.addEventListener('click', (event) => {
        const editButton = event.target.closest('.edit-btn');
        if (!editButton) return;
        const scheduleId = Number.parseInt(editButton.closest('tr').dataset.scheduleId, 10);
        const schedule = schedules.find((item) => item.id === scheduleId);
        if (schedule) openEditDialog(schedule, editButton);
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) closeDialog();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !dialog.hidden) closeDialog();
    });

    form.querySelectorAll('input, select').forEach((input) => {
        input.addEventListener('input', clearFormError);
        input.addEventListener('change', clearFormError);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;

        const campaign = campaigns.find((item) => item.key === campaignInput.value);
        const selectedArea = areaInput.options[areaInput.selectedIndex];
        const area = selectedArea.dataset.areaName;
        const postalCode = areaInput.value;
        const cutoffDate = cutoffInput.value;
        const collectionDate = collectionDateInput.value;
        const capacity = Number.parseInt(capacityInput.value, 10);
        const currentSchedule = schedules.find((item) => item.id === editingScheduleId);

        if (!campaign) {
            showFormError('Select an open monthly campaign.', campaignInput);
            return;
        }

        if (!collectionDate.startsWith(campaign.period)) {
            showFormError('The collection date must be within the selected campaign month.', collectionDateInput);
            return;
        }

        if (cutoffDate >= collectionDate) {
            showFormError('The request cut-off date must be before the collection date.', cutoffInput);
            return;
        }

        if (currentSchedule && capacity < currentSchedule.requests) {
            showFormError(`Capacity cannot be lower than the current ${currentSchedule.requests} requests.`, capacityInput);
            return;
        }

        const duplicateArea = schedules.some((schedule) => (
            schedule.campaignKey === campaign.key
            && schedule.postalCode === postalCode
            && schedule.id !== editingScheduleId
        ));

        if (duplicateArea) {
            showFormError('This area already has a schedule in the selected campaign.', areaInput);
            return;
        }

        if (editingScheduleId !== null) {
            schedules = schedules.map((schedule) => schedule.id === editingScheduleId
                ? {
                    ...schedule,
                    campaignKey: campaign.key,
                    campaignName: campaign.name,
                    campaignPeriod: campaign.period,
                    area,
                    postalCode,
                    cutoffDate,
                    collectionDate,
                    capacity,
                    status: statusInput.value === 'CLOSED' ? 'CLOSED' : 'OPEN',
                }
                : schedule);
            saveSchedules();
            closeDialog();
            renderSchedules();
            showToast('Schedule changes saved successfully.');
            return;
        }

        schedules.unshift({
            id: getNextScheduleId(),
            campaignKey: campaign.key,
            campaignName: campaign.name,
            campaignPeriod: campaign.period,
            area,
            postalCode,
            cutoffDate,
            collectionDate,
            requests: 0,
            capacity,
            status: statusInput.value === 'CLOSED' ? 'CLOSED' : 'OPEN',
        });
        saveSchedules();
        closeDialog();
        renderSchedules();
        showToast('Schedule created and saved in this browser.');
    });

    syncCampaignDetails();
    buildCampaignOptions();
    renderSchedules();
})();
