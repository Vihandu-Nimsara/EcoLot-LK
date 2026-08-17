(function () {
    "use strict";

    const form = document.querySelector(".recycler-registration-page .registration-form");

    if (!form) {
        return;
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        const expiry = form.elements.licence_expiry;
        const capabilities = form.querySelectorAll(
            '[name="requested_capabilities[]"]:checked'
        );
        const firstCapability = form.querySelector('[name="requested_capabilities[]"]');
        const capabilitiesError = form.querySelector("[data-capabilities-error]");
        firstCapability?.setCustomValidity(
            capabilities.length ? "" : "Select at least one requested capability."
        );

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
            return;
        }

        const password = form.querySelector('[name="company_password"]');
        const confirmation = form.querySelector('[name="confirm_password"]');
        const notice = document.querySelector("[data-registration-notice]");

        if (password && confirmation && password.value !== confirmation.value) {
            confirmation.setCustomValidity("Passwords do not match.");
            confirmation.reportValidity();
            confirmation.addEventListener(
                "input",
                () => confirmation.setCustomValidity(""),
                { once: true }
            );
            return;
        }

        const result = document.querySelector("[data-registration-result]");
        form.hidden = true;
        document.querySelector(".registration-heading")?.setAttribute("hidden", "");
        if (result) {
            result.hidden = false;
            result.focus();
        }
    });

    form.addEventListener("change", (event) => {
        if (event.target.matches(
            '[name="requested_capabilities[]"], [name="licence_expiry"]'
        )) {
            event.target.setCustomValidity("");
            form.querySelector('[name="requested_capabilities[]"]')?.setCustomValidity("");
            const capabilitiesError = form.querySelector("[data-capabilities-error]");
            if (capabilitiesError && event.target.matches('[name="requested_capabilities[]"]')) {
                capabilitiesError.hidden = form.querySelectorAll(
                    '[name="requested_capabilities[]"]:checked'
                ).length > 0;
            }
        }
    });
}());
