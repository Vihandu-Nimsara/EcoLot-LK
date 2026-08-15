(function () {
    "use strict";

    const form = document.querySelector(".login-form");

    if (!form) {
        return;
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
    });
}());
