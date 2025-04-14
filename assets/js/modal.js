function submitForm(formId) {
    const form = document.getElementById(formId);

    if (!form) {
        console.error('Form not found');
        return;
    }

    const formData = new FormData(form);

    fetch(form.action, { // Always use the same route
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
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
        });
}