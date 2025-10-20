document.addEventListener('DOMContentLoaded', function() {
    // Initialize toast notifications
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        }).show();
    });
});

function initializeFormValidation(formId, modalId, modalLabelId, defaultLabel) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);

    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            form.classList.remove('was-validated');
            form.reset();
            if (modalLabelId && defaultLabel) {
                document.getElementById(modalLabelId).textContent = defaultLabel;
            }
            const editId = form.querySelector('input[name="edit_id"]');
            if (editId) editId.value = '';
        });
    }
}