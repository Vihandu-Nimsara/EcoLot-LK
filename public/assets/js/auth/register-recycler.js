(function () {
    "use strict";

    const form = document.querySelector(".recycler-registration-page .registration-form");

    if (!form) {
        return;
    }

    form.addEventListener("submit", (event) => {
        const expiry = form.elements.licence_expiry;
        const activities = form.querySelectorAll(
            '[name="activities[]"]:checked'
        );
        const capabilities = form.querySelectorAll(
            '[name="requested_capabilities[]"]:checked'
        );
        const firstActivity = form.querySelector('[name="activities[]"]');
        const firstCapability = form.querySelector('[name="requested_capabilities[]"]');
        const activitiesError = form.querySelector("[data-activities-error]");
        const capabilitiesError = form.querySelector("[data-capabilities-error]");
        firstActivity?.setCustomValidity(
            activities.length ? "" : "Select at least one licence activity."
        );
        firstCapability?.setCustomValidity(
            capabilities.length ? "" : "Select at least one requested capability."
        );

        if (activitiesError) activitiesError.hidden = activities.length > 0;
        if (capabilitiesError) capabilitiesError.hidden = capabilities.length > 0;

        if (expiry) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const selected = expiry.value ? new Date(`${expiry.value}T00:00:00`) : null;
            expiry.setCustomValidity(
                selected && selected <= today
                    ? "The SWML expiry date must be in the future."
                    : ""
            );
        }
        if (!form.reportValidity()) {
            event.preventDefault();
            return;
        }

        const password = form.querySelector('[name="company_password"]');
        const confirmation = form.querySelector('[name="confirm_password"]');
        if (password && confirmation && password.value !== confirmation.value) {
            event.preventDefault();
            confirmation.setCustomValidity("Passwords do not match.");
            confirmation.reportValidity();
            confirmation.addEventListener(
                "input",
                () => confirmation.setCustomValidity(""),
                { once: true }
            );
            return;
        }

        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Processing...";
        }
    });

    form.addEventListener("change", (event) => {
        if (event.target.matches(
            '[name="activities[]"], [name="requested_capabilities[]"], [name="licence_expiry"]'
        )) {
            event.target.setCustomValidity("");
            form.querySelector('[name="activities[]"]')?.setCustomValidity("");
            form.querySelector('[name="requested_capabilities[]"]')?.setCustomValidity("");
            const activitiesError = form.querySelector("[data-activities-error]");
            const capabilitiesError = form.querySelector("[data-capabilities-error]");
            if (activitiesError && event.target.matches('[name="activities[]"]')) {
                activitiesError.hidden = form.querySelectorAll(
                    '[name="activities[]"]:checked'
                ).length > 0;
            }

            if (capabilitiesError && event.target.matches('[name="requested_capabilities[]"]')) {
                capabilitiesError.hidden = form.querySelectorAll(
                    '[name="requested_capabilities[]"]:checked'
                ).length > 0;
            }
        }
    });
}());
