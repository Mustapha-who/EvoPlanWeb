function initializeEditButtons() {
    document.addEventListener('DOMContentLoaded', function () {
        console.log("DOM fully loaded. Initializing event delegation.");

        const table = document.getElementById('datatablesSimple');

        if (table) {
            console.error('Table not found.');
            return;
        }
        console.log("Table found:", table);

        // Use event delegation to handle clicks on dynamically added buttons
        table.addEventListener('click', function (event) {
            const target = event.target;
            console.log("Click event detected on:", target);

            // Check if the clicked element is an "Edit" button
            if (target && target.classList.contains('edit-btn')) {
                console.log("Edit button clicked with user ID:", target.getAttribute('data-user-id'));

                const userId = target.getAttribute('data-user-id');
                const url = '/admin/edit-user/' + userId;

                fetch(url)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('modalFormContent').innerHTML = data;

                        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                        modal.show();
                    })
                    .catch(error => {
                        console.error("Error loading form:", error);
                        alert("Error loading form");
                    });
            }
        });
    });
}

// Call the function after the DOM is fully loaded
document.addEventListener('DOMContentLoaded', initializeEditButtons);