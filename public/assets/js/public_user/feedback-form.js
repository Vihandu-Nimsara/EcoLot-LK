document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-feedback-form]');
    if (!form) {
        return;
    }

    const message = form.querySelector('#feedback-message');
    const counter = form.querySelector('[data-feedback-counter]');
    const submitButton = form.querySelector('button[type="submit"]');

    const updateCounter = () => {
        if (counter) {
            counter.textContent = `${message.value.length} / ${message.maxLength}`;
        }
    };

    message.addEventListener('input', updateCounter);
    updateCounter();

    form.addEventListener('submit', (event) => {
        if (message.value.trim().length < 10) {
            event.preventDefault();
            alert('Please write at least 10 characters in your message.');
            message.focus();
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';
    });
});