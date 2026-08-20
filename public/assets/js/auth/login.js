(function () {
    "use strict";

    const form = document.querySelector(".login-form");
    const password = document.querySelector("#password");
    const passwordToggle = document.querySelector("[data-password-toggle]");
    const dialogBackdrop = document.querySelector("[data-account-dialog]");
    const dialog = dialogBackdrop?.querySelector("[role='dialog']");
    const authPage = document.querySelector(".auth-page");
    const dialogTrigger = document.querySelector("[data-account-chooser-open]");
    const dialogClose = document.querySelector("[data-account-dialog-close]");
    let previousFocus = null;

    function openDialog() {
        if (!dialogBackdrop || !dialog) return;

        previousFocus = document.activeElement;
        dialogBackdrop.hidden = false;
        authPage?.setAttribute("inert", "");
        document.body.style.overflow = "hidden";
        dialogClose?.focus();
    }

    function closeDialog() {
        if (!dialogBackdrop) return;

        dialogBackdrop.hidden = true;
        authPage?.removeAttribute("inert");
        document.body.style.overflow = "";
        previousFocus?.focus();
        previousFocus = null;
    }

    passwordToggle?.addEventListener("click", () => {
        if (!password) return;

        const willShow = password.type === "password";
        password.type = willShow ? "text" : "password";
        passwordToggle.setAttribute("aria-pressed", String(willShow));
        passwordToggle.setAttribute("aria-label", willShow ? "Hide password" : "Show password");
        password.focus();
    });


    dialogTrigger?.addEventListener("click", openDialog);
    dialogClose?.addEventListener("click", closeDialog);
    dialogBackdrop?.addEventListener("click", (event) => {
        if (event.target === dialogBackdrop) closeDialog();
    });

    document.addEventListener("keydown", (event) => {
        if (!dialogBackdrop || dialogBackdrop.hidden) return;

        if (event.key === "Escape") {
            closeDialog();
            return;
        }

        if (event.key !== "Tab" || !dialog) return;

        const focusable = Array.from(dialog.querySelectorAll("button:not([disabled]), a[href]"));
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
}());
