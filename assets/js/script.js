document.addEventListener('DOMContentLoaded', () => {
    const fileInputs = document.querySelectorAll('[data-upload-input]');
    fileInputs.forEach((input) => {
        input.addEventListener('change', () => {
            const targetSelector = input.getAttribute('data-upload-target');
            const target = targetSelector ? document.querySelector(targetSelector) : null;
            if (!target) {
                return;
            }

            const fileName = input.files && input.files.length > 0 ? input.files[0].name : 'No file selected';
            target.textContent = fileName;
        });
    });

    const deleteTriggers = document.querySelectorAll('[data-confirm-message]');
    deleteTriggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            const message = trigger.getAttribute('data-confirm-message') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
