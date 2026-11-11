(() => {
    'use strict';
    document.querySelectorAll('form[data-officer-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.officerConfirm)) event.preventDefault();
        });
    });
})();
