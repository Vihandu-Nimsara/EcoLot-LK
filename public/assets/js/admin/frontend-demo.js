(function () {
    "use strict";

    const body = document.body;
    const navToggle = document.querySelector("[data-nav-toggle]");
    const navClose = document.querySelector("[data-nav-close]");
    const dialog = document.querySelector("[data-workspace-dialog]");
    const form = dialog?.querySelector("[data-dialog-form]");
    const title = dialog?.querySelector("[data-dialog-title]");
    const eyebrow = dialog?.querySelector("[data-dialog-eyebrow]");
    const description = dialog?.querySelector("[data-dialog-description]");
    const fields = dialog?.querySelector("[data-dialog-fields]");
    const confirmButton = dialog?.querySelector("[data-dialog-confirm]");
    const notice = dialog?.querySelector("[data-dialog-notice]");
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

    function configFor(trigger) {
        const type = trigger.dataset.adminDialog;
        const name = escapeHtml(trigger.dataset.name || "this record");
        const action = escapeHtml(trigger.dataset.action || "Update");
        const licenceStatus = trigger.dataset.status || "Pending";
        const licenceDecision = '<option value="">Select status</option><option>Pending</option><option>Valid</option><option>Expired</option><option>Revoked</option>';
        let categoryOptions = '<option value="">Select category</option>';
        try {
            categoryOptions += JSON.parse(trigger.dataset.categoryOptions || "[]").map((category) => `<option>${escapeHtml(category)}</option>`).join("");
        } catch (_) { /* Keep the empty prompt if malformed demo data is supplied. */ }
        const configs = {
            "logout": { eyebrow: "Session", title: "Log Out?", description: "Confirm that you want to leave the Admin workspace.", confirm: "Log Out", fields: "<p>Logout is a frontend-only representation until authentication is connected.</p>" },
            "approve-recycler": { eyebrow: "Final verification", title: "Verify Recycler?", description: "Confirm that all displayed verification requirements have been reviewed.", confirm: "Verify Recycler", fields: `<p>Verify <strong>${name}</strong> for EcoLot eligibility? Pending capabilities are not automatically approved, and no status will be persisted.</p>` },
            "reject-recycler": { eyebrow: "Verification decision", title: "Reject Recycler", description: "Provide a clear reason for the applicant.", confirm: "Reject", danger: true, fields: field("Reason", '<textarea rows="4" required placeholder="Enter the rejection reason"></textarea>') },
            "reconsider-recycler": { eyebrow: "Verification decision", title: "Reconsider Application?", description: "Return this application to the review workflow.", confirm: "Reconsider", fields: `<p>Reconsider <strong>${name}</strong>? This frontend action will not change the record.</p>` },
            "account-status": { eyebrow: "Account status", title: `${action} Account?`, description: "Confirm the selected staff account status change.", confirm: action, danger: action.toLowerCase().includes("deactivate"), fields: `<p>${action} the account for <strong>${name}</strong>? No account data will be changed.</p>` },
            "create-staff": { eyebrow: "Staff account", title: "Create Staff", description: "Create a Municipal Officer or Collector account preview.", confirm: "Review Staff Details", fields: field("Full Name", '<input autocomplete="name" required>') + field("Email", '<input type="email" autocomplete="email" required>') + field("Mobile Number", '<input type="tel" autocomplete="tel" required>') + field("Role", '<select required><option value="">Select role</option><option value="MUNICIPAL_OFFICER">Municipal Officer</option><option value="COLLECTOR">Collector</option></select>') + '<p class="dialog-context-note">Administrators are provisioned separately. Public Users and Authorized Recyclers use their own registration flows.</p>' },
            "view-user": { eyebrow: "System user", title: "Account Information", description: "Review this protected or self-registered account.", confirm: "Close", closeOnly: true, fields: `<div class="dialog-summary"><div><span>Name</span><strong>${name}</strong></div><div><span>Role</span><strong>${escapeHtml(trigger.dataset.role)}</strong></div><div><span>Email</span><strong>${escapeHtml(trigger.dataset.email)}</strong></div><div><span>Phone</span><strong>${escapeHtml(trigger.dataset.phone)}</strong></div><div><span>Identifier</span><strong>${escapeHtml(trigger.dataset.identifier)}</strong></div></div>` },
            "edit-staff": { eyebrow: "Staff account", title: "Edit Staff User", description: "Update the compact staff identity fields.", confirm: "Review Changes", fields: field("Full Name", `<input value="${name}" required>`) + field("Email", `<input type="email" value="${escapeHtml(trigger.dataset.email || "")}" required>`) + field("Phone", `<input type="tel" value="${escapeHtml(trigger.dataset.phone || "")}" required>`) + field("Role", `<select required><option${trigger.dataset.role === "Municipal Officer" ? " selected" : ""}>Municipal Officer</option><option${trigger.dataset.role === "Collector" ? " selected" : ""}>Collector</option></select>`) },
            "review-licence": { eyebrow: "CEA licence review", title: "Review Licence Record", description: "Review EcoLot's record of this CEA-issued Scheduled Waste Management Licence.", confirm: "Update Licence Status", fields: `<div class="dialog-summary"><div><span>Recycler</span><strong>${name}</strong></div><div><span>SWML Number</span><strong>${escapeHtml(trigger.dataset.swml)}</strong></div><div><span>Expiry Date</span><strong>${escapeHtml(trigger.dataset.expiry)}</strong></div><div><span>Current Status</span><strong>${escapeHtml(licenceStatus)}</strong></div></div>` + field("Licence Status", `<select name="licence_decision" required>${licenceDecision}</select>`) + field("Review Note", '<textarea name="licence_reason" rows="3" placeholder="Add a short review note"></textarea>') + '<p class="dialog-context-note">EcoLot records the CEA licence lifecycle; it does not issue the licence.</p>' },
            "review-capability": { eyebrow: "Capability review", title: `Review ${name}`, description: "Review this waste-handling capability independently from the final recycler decision.", confirm: "Update Capability Status", fields: `<div class="dialog-summary"><div><span>Category</span><strong>${name}</strong></div><div><span>Current Status</span><strong>${escapeHtml(trigger.dataset.status)}</strong></div></div>` + (trigger.dataset.status === "PENDING" ? field("Decision", '<select required><option value="">Select decision</option><option value="APPROVED">Approve</option></select>') : trigger.dataset.status === "APPROVED" ? field("Decision", '<select required><option value="">Select decision</option><option value="APPROVED">Keep Approved</option><option value="SUSPENDED">Suspend</option></select>') : field("Decision", '<select required><option value="">Select decision</option><option value="SUSPENDED">Keep Suspended</option><option value="APPROVED">Restore Approval</option></select>')) + field("Review Note", '<textarea rows="3" required placeholder="Add a short review note"></textarea>') },
            "create-category": { eyebrow: "Categories & items", title: "Create Category", description: "Add a compact E-Waste category definition.", confirm: "Review Category", fields: field("Category Name", '<input required placeholder="Enter category name">') + field("Description", '<textarea rows="4" required placeholder="Enter category description"></textarea>') },
            "create-item": { eyebrow: "Categories & items", title: "Create E-Waste Item", description: "Add an item and its default collection guidance.", confirm: "Review Item", fields: field("Category", `<select required>${categoryOptions}</select>`) + field("Item Name", '<input required placeholder="Enter item name">') + field("Collection Status", '<select required><option>Accepted</option><option>Review Required</option><option>Do Not Collect</option></select>') + field("Default Risk Level", '<select required><option>Low</option><option>Medium</option><option>High</option></select>') },
            "create-rule": { eyebrow: "Risk rules", title: "Create Risk Rule", description: "Choose a category helper, then persist the rule against one specific E-Waste item.", confirm: "Create Rule", fields: field("Category", '<select name="risk_category" required><option value="">Select category</option><option>Battery and Circuit Boards</option><option>Medical E-Waste</option><option>Office E-Waste</option><option>Domestic E-Waste</option></select>') + field("E-Waste Item", '<select name="waste_item_id" required><option value="">Select item</option><optgroup label="Battery and Circuit Boards"><option value="101">Lithium-ion Battery</option><option value="102">Circuit Board</option></optgroup><optgroup label="Medical E-Waste"><option value="201">X-Ray Equipment</option><option value="202">Laboratory Analyzer</option></optgroup><optgroup label="Office E-Waste"><option value="301">CRT Monitor</option></optgroup><optgroup label="Domestic E-Waste"><option value="401">Smoke Detector</option></optgroup></select>') + field("Condition Type", '<select required><option value="">Select condition</option><option>Damaged</option><option>Leaking</option><option>Swollen</option><option>Contains controlled material</option></select>') + field("Risk Level", '<select><option>Low</option><option>Medium</option><option>High</option></select>') + field("Action Note", '<textarea rows="3" required placeholder="Describe collection guidance"></textarea>') + field("Status", '<select required><option value="ACTIVE">Active</option><option value="INACTIVE">Inactive</option></select>') },
            "view-rule": { eyebrow: "Risk rule details", title: "View Risk Rule", description: "This rule references a specific E-Waste item.", confirm: "Close", closeOnly: true, fields: `<div class="dialog-summary"><div><span>Rule</span><strong>#${escapeHtml(trigger.dataset.ruleId)}</strong></div><div><span>Item</span><strong>${name}</strong></div><div><span>Category</span><strong>${escapeHtml(trigger.dataset.category)}</strong></div><div><span>Risk</span><strong>${escapeHtml(trigger.dataset.risk || "High")}</strong></div><div><span>Status</span><strong>${escapeHtml(trigger.dataset.status || "Active")}</strong></div></div><p>${escapeHtml(trigger.dataset.note || "Collection guidance")}</p>` },
            "edit-rule": { eyebrow: "Risk rules", title: "Edit Risk Rule", description: "Update the item-bound rule fields without leaving the table.", confirm: "Review Changes", fields: field("Category", `<input value="${escapeHtml(trigger.dataset.category)}" readonly>`) + field("E-Waste Item", `<input value="${name}" required>`) + field("Condition Type", `<input value="${escapeHtml(trigger.dataset.condition)}" required>`) + field("Risk Level", `<select><option${trigger.dataset.risk === "HIGH" ? " selected" : ""}>High</option><option${trigger.dataset.risk === "MEDIUM" ? " selected" : ""}>Medium</option><option${trigger.dataset.risk === "LOW" ? " selected" : ""}>Low</option></select>`) + field("Action Note", `<textarea rows="3" required>${escapeHtml(trigger.dataset.note)}</textarea>`) },
            "rule-status": { eyebrow: "Confirmation", title: `${action} Rule?`, description: "Rules are retained for history when their status changes.", confirm: action, danger: action === "Deactivate", fields: `<p>${action} risk rule <strong>#${escapeHtml(trigger.dataset.ruleId)}</strong>? No data will be persisted.</p>` },
            "category-status": { eyebrow: "Category status", title: "Update Category Status?", description: "Confirm the selected catalogue status.", confirm: action, danger: action === "Deactivate", fields: `<p>${action} <strong>${name}</strong>? Existing catalogue history will remain represented.</p>` }
        };
        return configs[type] || null;
    }

    function closeDialog() {
        if (!dialog) return;
        dialog.hidden = true;
        body.style.overflow = "";
        document.querySelector(".app-layout")?.removeAttribute("inert");
        form?.reset();
        if (notice) notice.hidden = true;
        activeTrigger?.focus();
        activeTrigger = null;
    }

    function openDialog(trigger) {
        if (!dialog || !fields || !confirmButton) return;
        const config = configFor(trigger);
        if (!config) return;
        activeTrigger = trigger;
        eyebrow.textContent = config.eyebrow;
        title.textContent = config.title;
        description.textContent = config.description;
        fields.innerHTML = config.fields;
        const riskCategory = fields.querySelector('[name="risk_category"]');
        const riskItem = fields.querySelector('[name="waste_item_id"]');
        const filterRiskItems = () => {
            if (!riskItem) return;
            Array.from(riskItem.querySelectorAll("optgroup")).forEach((group) => {
                const matches = group.label === riskCategory?.value;
                group.hidden = !matches;
                Array.from(group.querySelectorAll("option")).forEach((option) => { option.disabled = !matches; });
            });
            riskItem.value = "";
            riskItem.disabled = !riskCategory?.value;
        };
        if (riskCategory && riskItem) {
            riskCategory.addEventListener("change", filterRiskItems);
            filterRiskItems();
        }
        confirmButton.textContent = config.confirm;
        confirmButton.classList.toggle("reject-btn", Boolean(config.danger));
        confirmButton.classList.toggle("approve-btn", !config.danger);
        confirmButton.dataset.closeOnly = config.closeOnly ? "true" : "false";
        notice.hidden = true;
        dialog.hidden = false;
        body.style.overflow = "hidden";
        document.querySelector(".app-layout")?.setAttribute("inert", "");
        const licenceDecisionSelect = dialog.querySelector('[name="licence_decision"]');
        const licenceReason = dialog.querySelector('[name="licence_reason"]');
        licenceDecisionSelect?.addEventListener("change", () => {
            if (licenceReason) licenceReason.required = licenceDecisionSelect.value === "Revoked";
        });
        dialog.querySelector("input, select, textarea, [data-dialog-close]")?.focus();
    }

    navToggle?.addEventListener("click", () => setNavigation(!body.classList.contains("nav-open")));
    navClose?.addEventListener("click", () => setNavigation(false));

    document.addEventListener("click", (event) => {
        const trigger = event.target.closest("[data-admin-dialog]");
        if (trigger) {
            if (trigger.matches("[data-status-dialog]")) {
                const selected = trigger.closest(".status-form")?.querySelector("select")?.value;
                trigger.dataset.action = selected === "ACTIVE" ? "Activate" : "Deactivate";
            }
            if (trigger.dataset.adminDialog === "category-status") {
                const selected = trigger.closest("form")?.querySelector('[name="status"]')?.value;
                trigger.dataset.action = selected === "ACTIVE" ? "Activate" : "Deactivate";
            }
            openDialog(trigger);
        }
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
            if (dialog && !dialog.hidden) closeDialog(); else setNavigation(false);
            return;
        }
        if (event.key === "Tab" && dialog && !dialog.hidden) {
            const focusable = Array.from(dialog.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]'));
            if (!focusable.length) return;
            const first = focusable[0]; const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });

    form?.addEventListener("submit", (event) => {
        event.preventDefault();
        if (confirmButton.dataset.closeOnly === "true") {
            closeDialog();
            return;
        }
        if (!form.reportValidity()) return;
        notice.textContent = "Demo only — this action will be connected during backend implementation. No data was saved.";
        notice.hidden = false;
        notice.focus();
    });

    document.querySelectorAll("[data-demo-form]").forEach((demoForm) => demoForm.addEventListener("submit", (event) => {
        event.preventDefault();
        if (!demoForm.reportValidity()) return;
        const pageNotice = document.querySelector("[data-page-notice]");
        if (pageNotice) {
            pageNotice.textContent = "Demo only — this action will be connected during backend implementation. No data was saved.";
            pageNotice.hidden = false;
            pageNotice.focus();
        }
    }));
    initialiseFilters();
    document.querySelector("[data-print-report]")?.addEventListener("click", () => window.print());
}());
