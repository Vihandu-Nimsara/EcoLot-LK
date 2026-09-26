(function () {
    "use strict";

    const body = document.body;
    const navToggle = document.querySelector("[data-nav-toggle]");
    const navClose = document.querySelector("[data-nav-close]");
    const dialog = document.querySelector("[data-workspace-dialog]");
    const dialogForm = dialog?.querySelector("[data-dialog-form]");
    const dialogTitle = dialog?.querySelector("[data-dialog-title]");
    const dialogEyebrow = dialog?.querySelector("[data-dialog-eyebrow]");
    const dialogDescription = dialog?.querySelector("[data-dialog-description]");
    const dialogFields = dialog?.querySelector("[data-dialog-fields]");
    const dialogConfirm = dialog?.querySelector("[data-dialog-confirm]");
    const dialogNotice = dialog?.querySelector("[data-dialog-notice]");
    let activeTrigger = null;

    function initialiseFilters() {
        document.querySelectorAll("[data-client-filter]").forEach((filterForm) => {
            const rows = Array.from(document.querySelectorAll(filterForm.dataset.rows || ""));
            const empty = document.querySelector(filterForm.dataset.empty || "");
            const result = document.querySelector(filterForm.dataset.result || "");
            const apply = () => {
                const search = String(filterForm.elements.search?.value || "").trim().toLowerCase();
                const quickFilters = Array.from(filterForm.querySelectorAll("[data-filter-name][aria-pressed='true']"));
                let visible = 0;
                rows.forEach((row) => {
                    const textMatch = !search || String(row.dataset.search || "").toLowerCase().includes(search);
                    const selectMatch = Array.from(filterForm.querySelectorAll("select[name]")).every((select) => !select.value || row.dataset[select.name] === select.value);
                    const quickMatch = quickFilters.every((button) => !button.dataset.filterValue || row.dataset[button.dataset.filterName] === button.dataset.filterValue);
                    const filtersMatch = selectMatch && quickMatch;
                    row.hidden = !(textMatch && filtersMatch);
                    if (!row.hidden) visible += 1;
                });
                if (empty) empty.hidden = visible !== 0;
                if (result) result.textContent = `${visible} ${visible === 1 ? "record" : "records"} shown`;
            };
            filterForm.addEventListener("submit", (event) => { event.preventDefault(); apply(); });
            filterForm.addEventListener("input", apply);
            filterForm.addEventListener("change", apply);
            filterForm.addEventListener("reset", () => requestAnimationFrame(apply));
            filterForm.addEventListener("click", (event) => {
                const button = event.target.closest("[data-filter-name]");
                if (!button) return;
                filterForm.querySelectorAll(`[data-filter-name="${button.dataset.filterName}"]`).forEach((item) => item.setAttribute("aria-pressed", String(item === button)));
                apply();
            });
            apply();
        });
    }

    const escapeHtml = (value) => String(value ?? "").replace(/[&<>'"]/g, (character) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;"
    }[character]));

    function setNavigation(open) {
        body.classList.toggle("nav-open", open);
        navToggle?.setAttribute("aria-expanded", String(open));
    }

    function field(label, control) {
        return `<label class="dialog-field"><span>${label}</span>${control}</label>`;
    }

    function dialogConfig(type, trigger) {
        const capabilityCategories = ["Demo Consumer Electronics", "Demo Battery and Circuit Boards", "Office E-Waste", "Medical E-Waste"];
        const capabilityControl = trigger.dataset.category
            ? `<input value="${escapeHtml(trigger.dataset.category)}" readonly>`
            : `<select required><option value="">Select a waste category</option>${capabilityCategories.map((item) => `<option>${escapeHtml(item)}</option>`).join("")}</select>`;

        const configs = {
            "logout": { eyebrow: "Session", title: "Log Out?", description: "Confirm that you want to leave the Recycler workspace.", confirm: "Log Out", fields: "<p>Logout is a frontend-only representation until authentication is connected.</p>" },
            "edit-profile": { eyebrow: "My Profile", title: "Edit Contact Details", description: "Update the basic contact information shown on your profile.", confirm: "Request Change", fields: field("Contact Person", '<input value="Anjana Silva" required>') + field("Business Email", '<input type="email" value="anjana@greencycle.lk" required>') + field("Phone", '<input value="077 234 5678" required>') + field("Business Address", '<textarea rows="3" required>45 Green Park, Colombo 05</textarea>') + field("District", '<select required><option selected>Colombo</option><option>Gampaha</option><option>Kalutara</option><option>Kandy</option><option>Galle</option></select>') },
            "licence-request": { eyebrow: "Compliance request", title: "Submit Licence Update", description: "New licence information requires Administrator verification and does not overwrite the current verified record.", confirm: "Submit Update for Review", fields: field("SWML Number", '<input value="SWML/2026/001" required>') + field("New Expiry Date", '<input type="date" value="2027-06-30" required>') + field("New Licence PDF", '<input type="file" accept="application/pdf,.pdf" required>') + '<p class="dialog-context-note">Upload the renewed CEA-issued SWML record. This frontend preview does not store the file.</p>' },
            "capability-request": { eyebrow: "Capability request", title: "Request Capability Change", description: "Capability requests require approval before affecting eligibility.", confirm: "Submit Request", fields: field("Waste Category", capabilityControl) + field("Request", '<select required><option value="">Select request</option><option>Add capability</option><option>Update capability</option><option>Deactivate capability</option></select>') + field("Reason", '<textarea rows="3" required placeholder="Explain the requested change"></textarea>') },
        };

        return configs[type] || null;
    }

    function closeDialog() {
        if (!dialog) return;
        dialog.hidden = true;
        document.querySelector(".app-layout")?.removeAttribute("inert");
        body.style.overflow = "";
        dialogForm?.reset();
        if (dialogNotice) dialogNotice.hidden = true;
        activeTrigger?.focus();
        activeTrigger = null;
    }

    function openDialog(trigger) {
        if (!dialog || !dialogFields || !dialogConfirm) return;
        const config = dialogConfig(trigger.dataset.recyclerDialog, trigger);
        if (!config) return;
        activeTrigger = trigger;
        dialogEyebrow.textContent = config.eyebrow;
        dialogTitle.textContent = config.title;
        dialogDescription.textContent = config.description;
        dialogFields.innerHTML = config.fields;
        dialogConfirm.textContent = config.confirm;
        dialogConfirm.classList.toggle("reject-dialog-btn", Boolean(config.danger));
        dialogConfirm.dataset.closeOnly = config.closeOnly ? "true" : "false";
        dialogNotice.hidden = true;
        dialog.hidden = false;
        document.querySelector(".app-layout")?.setAttribute("inert", "");
        body.style.overflow = "hidden";
        dialog.querySelector("input:not([readonly]), select, textarea, [data-dialog-close]")?.focus();
    }

    navToggle?.addEventListener("click", () => setNavigation(!body.classList.contains("nav-open")));
    navClose?.addEventListener("click", () => setNavigation(false));

    document.addEventListener("click", (event) => {
        const trigger = event.target.closest("[data-recycler-dialog]");
        if (trigger) openDialog(trigger);
        if (event.target.closest("[data-dialog-close]") || event.target === dialog) closeDialog();
        const demoControl = event.target.closest("[data-demo-message]");
        if (demoControl) {
            const pageNotice = document.querySelector("[data-page-notice]");
            if (pageNotice) {
                pageNotice.textContent = demoControl.dataset.demoMessage;
                pageNotice.hidden = false;
                pageNotice.focus();
            }
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            if (dialog && !dialog.hidden) closeDialog();
            else setNavigation(false);
            return;
        }
        if (event.key === "Tab" && dialog && !dialog.hidden) {
            const focusable = Array.from(dialog.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]'));
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });

    dialogForm?.addEventListener("submit", (event) => {
        event.preventDefault();
        if (dialogConfirm.dataset.closeOnly === "true") {
            closeDialog();
            return;
        }
        if (!dialogForm.reportValidity()) return;
        dialogNotice.textContent = "Demo only — this action will be connected during backend implementation. No data was saved.";
        dialogNotice.hidden = false;
        dialogNotice.focus();
    });

    document.querySelectorAll("[data-demo-form]").forEach((form) => form.addEventListener("submit", (event) => event.preventDefault()));
    initialiseFilters();
}());
