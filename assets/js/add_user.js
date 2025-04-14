document.addEventListener('DOMContentLoaded', function () {
    function initializeFormHandlers() {
        const modalForms = document.querySelectorAll('.tab-pane form');

        modalForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault(); // Prevent the default form submission

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to submit the form');
                        }
                        return response.text();
                    })
                    .then(html => {
                        const modalBody = document.getElementById('addUserModalBody');
                        if (modalBody) {
                            modalBody.innerHTML = html; // Update modal content with the response
                            initializeFormHandlers(); // Reinitialize handlers for the new content
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting form:', error);
                    });
            });
        });
    }

    // Initialize handlers on page load
    initializeFormHandlers();

    // Reinitialize handlers when modal content is dynamically loaded
    document.getElementById('addUserModal').addEventListener('shown.bs.modal', function () {
        initializeFormHandlers();
    });
});