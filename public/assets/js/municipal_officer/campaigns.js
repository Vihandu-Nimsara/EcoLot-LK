(() => {
    const dialog = document.querySelector('[data-campaign-dialog]');
    const openButton = document.querySelector('[data-open-campaign-dialog]');
    const closeButtons = document.querySelectorAll('[data-close-campaign-dialog]');
    const form = document.querySelector('[data-campaign-form]');
    const tableBody = document.querySelector('[data-campaign-table-body]');
    const toast = document.querySelector('[data-campaign-toast]');

    if (!dialog || !openButton || !form || !tableBody) {
        return;
    }

    const nameInput = form.querySelector('[name="name"]');
    const periodInput = form.querySelector('[name="period"]');
    let toastTimer;

    const setDefaultPeriod = () => {
        const today = new Date();
        periodInput.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    };

    const openDialog = () => {
        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        nameInput.focus();
    };

    const closeDialog = () => {
        dialog.hidden = true;
        document.body.style.overflow = '';
        form.reset();
        setDefaultPeriod();
        openButton.focus();
    };

    const getNextCampaignId = () => {
        const ids = Array.from(tableBody.querySelectorAll('tr td:first-child'))
            .map((cell) => Number.parseInt(cell.textContent.replace(/\D/g, ''), 10))
            .filter(Number.isFinite);

        return (ids.length ? Math.max(...ids) : 0) + 1;
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

    const showToast = () => {
        if (!toast) {
            return;
        }

        window.clearTimeout(toastTimer);
        toast.hidden = false;
        toastTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 3200);
    };

    openButton.addEventListener('click', openDialog);
    closeButtons.forEach((button) => button.addEventListener('click', closeDialog));

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

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        const [year, month] = periodInput.value.split('-');
        const now = new Date();
        const date = [now.getFullYear(), now.getMonth() + 1, now.getDate()]
            .map((part) => String(part).padStart(2, '0'))
            .join('-');
        const time = [now.getHours(), now.getMinutes(), now.getSeconds()]
            .map((part) => String(part).padStart(2, '0'))
            .join(':');
        const row = document.createElement('tr');
        const status = document.createElement('span');
        const editButton = document.createElement('button');
        const createdAt = document.createElement('span');

        status.className = 'status open';
        status.textContent = 'OPEN';
        editButton.type = 'button';
        editButton.className = 'edit-btn';
        editButton.textContent = 'Edit';
        createdAt.append(document.createTextNode(date), document.createElement('br'), document.createTextNode(time));

        addCell(row, `#${getNextCampaignId()}`);
        addCell(row, nameInput.value.trim());
        addCell(row, `${Number(month)} / ${year}`);
        addCell(row, status);
        addCell(row, 'Nadeesha Perera');
        addCell(row, createdAt);
        addCell(row, editButton);

        tableBody.prepend(row);
        closeDialog();
        showToast();
    });

    setDefaultPeriod();
})();
