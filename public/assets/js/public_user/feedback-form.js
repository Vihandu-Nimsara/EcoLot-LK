document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[data-feedback-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        alert('Prototype only — feedback not actually submitted.');
    });
});
