(function () {
    "use strict";

    const form = document.querySelector(
        ".public-registration-page .registration-form"
    );

    if (!form) {
        return;
    }

    form.addEventListener("submit", () => {
        const submitButton = form.querySelector(
            'button[type="submit"]'
        );

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Processing...";
        }
    });
}());