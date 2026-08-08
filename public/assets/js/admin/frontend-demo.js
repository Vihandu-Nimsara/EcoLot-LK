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
        const configs = {
            "logout": { eyebrow: "Session", title: "Log Out?", description: "Confirm that you want to leave the Admin workspace.", confirm: "Log Out", fields: "<p>Logout is a frontend-only representation until authentication is connected.</p>" },
            "approve-recycler": { eyebrow: "Verification decision", title: "Approve Recycler?", description: "Confirm that the company and compliance information has been reviewed.", confirm: "Approve", fields: `<p>Approve <strong>${name}</strong> as an authorized recycler? No status will be persisted in this frontend demo.</p>` },
            "reject-recycler": { eyebrow: "Verification decision", title: "Reject Recycler", description: "Provide a clear reason for the applicant.", confirm: "Reject", danger: true, fields: field("Reason", '<textarea rows="4" required placeholder="Enter the rejection reason"></textarea>') },
            "reconsider-recycler": { eyebrow: "Verification decision", title: "Reconsider Application?", description: "Return this application to the review workflow.", confirm: "Reconsider", fields: `<p>Reconsider <strong>${name}</strong>? This frontend action will not change the record.</p>` },
            "account-status": { eyebrow: "Account status", title: `${action} Account?`, description: "Confirm the selected staff account status change.", confirm: action, danger: action.toLowerCase().includes("deactivate"), fields: `<p>${action} the account for <strong>${name}</strong>? No account data will be changed.</p>` },
            "edit-staff": { eyebrow: "Staff account", title: "Edit Staff User", description: "Update the compact staff identity fields.", confirm: "Review Changes", fields: field("Full Name", `<input value="${name}" required>`) + field("Email", `<input type="email" value="${escapeHtml(trigger.dataset.email || "officer@ecolot.lk")}" required>`) + field("Role", '<select required><option>Municipal Officer</option><option>Collector</option><option>Administrator</option></select>') },
            "licence-status": action === "Deactivate"
                ? { eyebrow: "Licence compliance", title: "Deactivate Licence?", description: "Confirm the compliance status change while retaining licence history.", confirm: "Deactivate", danger: true, fields: "<p>Deactivate this recycler licence? No data will be persisted.</p>" }
                : { eyebrow: "Licence compliance", title: "Update Licence Status", description: "Review the licence state without editing the full compliance record.", confirm: "Review Update", fields: field("Licence Status", '<select required><option>Pending Review</option><option>Active</option><option>Inactive</option><option>Expired</option></select>') + field("Note (optional)", '<textarea rows="3" placeholder="Add a compliance note"></textarea>') },
            "capability-action": { eyebrow: "Capability status", title: `${action} Capability?`, description: "Confirm this capability request or status change.", confirm: action, danger: ["reject", "deactivate"].some((word) => action.toLowerCase().includes(word)), fields: `<p>${action} the <strong>${name}</strong> capability? No data will be persisted.</p>` },
            "create-rule": { eyebrow: "Risk rules", title: "Create Risk Rule", description: "Define compact collection guidance for future backend integration.", confirm: "Create Rule", fields: field("Category", '<select required><option value="">Select category</option><option>Battery and Circuit Boards</option><option>Medical E-Waste</option><option>Do Not Collect</option></select>') + field("Condition Type", '<select required><option value="">Select condition</option><option>Damaged</option><option>Leaking</option><option>Contains controlled material</option></select>') + field("Risk Level", '<select><option>Low</option><option>Medium</option><option>High</option></select>') + field("Action Note", '<textarea rows="3" required placeholder="Describe collection guidance"></textarea>') },
            "view-rule": { eyebrow: "Risk rule details", title: "View Risk Rule", description: "A compact summary of this collection rule.", confirm: "Close", closeOnly: true, fields: `<div class="dialog-summary"><div><span>Rule</span><strong>#${escapeHtml(trigger.dataset.ruleId)}</strong></div><div><span>Category</span><strong>${name}</strong></div><div><span>Risk</span><strong>${escapeHtml(trigger.dataset.risk || "High")}</strong></div><div><span>Status</span><strong>${escapeHtml(trigger.dataset.status || "Active")}</strong></div></div><p>${escapeHtml(trigger.dataset.note || "Collection guidance")}</p>` },
            "edit-rule": { eyebrow: "Risk rules", title: "Edit Risk Rule", description: "Update the compact rule fields without leaving the table.", confirm: "Review Changes", fields: field("Category", `<input value="${name}" required>`) + field("Condition Type", `<input value="${escapeHtml(trigger.dataset.condition)}" required>`) + field("Risk Level", '<select><option>High</option><option>Medium</option><option>Low</option></select>') + field("Action Note", `<textarea rows="3" required>${escapeHtml(trigger.dataset.note)}</textarea>`) },
            "rule-status": { eyebrow: "Confirmation", title: `${action} Rule?`, description: "Rules are retained for history when their status changes.", confirm: action, danger: action === "Deactivate", fields: `<p>${action} risk rule <strong>#${escapeHtml(trigger.dataset.ruleId)}</strong>? No data will be persisted.</p>` },
            "category-status": { eyebrow: "Category status", title: "Update Category Status?", description: "Confirm the selected catalogue status.", confirm: action, danger: action === "Deactivate", fields: `<p>${action} <strong>${name}</strong>? Existing catalogue history will remain represented.</p>` }
        };
        return configs[type] || null;
    }

    function closeDialog() {
        if (!dialog) return;
        dialog.hidden = true;
        body.style.overflow = "";
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
        confirmButton.textContent = config.confirm;
        confirmButton.classList.toggle("reject-btn", Boolean(config.danger));
        confirmButton.classList.toggle("approve-btn", !config.danger);
        confirmButton.dataset.closeOnly = config.closeOnly ? "true" : "false";
        notice.hidden = true;
        dialog.hidden = false;
        body.style.overflow = "hidden";
        dialog.querySelector("input, select, textarea, [data-dialog-close]")?.focus();
    }

    navToggle?.addEventListener("click", () => setNavigation(!body.classList.contains("nav-open")));
    navClose?.addEventListener("click", () => setNavigation(false));

    document.addEventListener("click", (event) => {
        const trigger = event.target.closest("[data-admin-dialog]");
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
        if (event.key !== "Escape") return;
        if (dialog && !dialog.hidden) closeDialog();
        else setNavigation(false);
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
}());
