(function () {
    "use strict";

    const form = document.querySelector(".public-registration-page .registration-form");

    if (!form) {
        return;
    }

    form.addEventListener("submit", (event) => {
        event.preventDefault();
    });
}());
