(function () {
    "use strict";

    const form = document.querySelector(".recycler-registration-page .registration-form");

    if (!form) {
        return;
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        if (!form.reportValidity()) {
            return;
        }

        const password = form.querySelector('[name="company_password"]');
        const confirmation = form.querySelector('[name="confirm_password"]');
        const notice = document.querySelector("[data-registration-notice]");

        if (password && confirmation && password.value !== confirmation.value) {
            confirmation.setCustomValidity("Passwords do not match.");
            confirmation.reportValidity();
            confirmation.addEventListener("input", () => confirmation.setCustomValidity(""), { once: true });
            return;
        }

        if (notice) {
            notice.textContent = "Frontend preview only. Your company, licence evidence, and requested capabilities have not been submitted or saved.";
            notice.hidden = false;
            notice.focus();
        }
    });
}());
