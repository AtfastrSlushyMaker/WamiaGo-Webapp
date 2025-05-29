/**
 * View user details
 * Drop-in replacement for the viewUser function in admin-user-management.js
 */
function viewUser(userId) {
    if (DEBUG) {
        console.log('View user:', userId);
    }

    showLoading(true);

    try {
        // Get the view modal or create it if it doesn't exist
        let modal = document.getElementById('view-user-modal');

        // If no modal exists with that ID, try to find the edit modal and clone it
        if (!modal) {
            const editModal = document.getElementById('edit-user-modal');
            if (editModal) {
                // Clone the edit modal and modify it for viewing
                modal = editModal.cloneNode(true);
                modal.id = 'view-user-modal';

                // Change title to "View User Details"
                const modalTitle = modal.querySelector('.modal-title');
                if (modalTitle) modalTitle.textContent = 'View User Details';

                // Disable all inputs in the form
                const form = modal.querySelector('form');
                if (form) {
                    form.querySelectorAll('input, select, textarea').forEach(input => {
                        input.disabled = true;
                    });

                    // Hide the save button
                    const saveButton = form.querySelector('button[type="submit"]');
                    if (saveButton) {
                        saveButton.style.display = 'none';
                    }

                    // Add a close button if none exists
                    const footerDiv = form.querySelector('.modal-footer');
                    if (footerDiv && !footerDiv.querySelector('.btn-secondary')) {
                        const closeButton = document.createElement('button');
                        closeButton.type = 'button';
                        closeButton.className = 'btn btn-secondary';
                        closeButton.setAttribute('data-bs-dismiss', 'modal');
                        closeButton.textContent = 'Close';
                        footerDiv.appendChild(closeButton);
                    }
                }

                document.body.appendChild(modal);
            } else {
                throw new Error('Neither view modal nor edit modal found');
            }
        }

        // Make sure we're using a numeric ID
        const actualUserId = parseInt(userId);
        if (isNaN(actualUserId)) {
            console.error('Invalid user ID:', userId);
            showError('Invalid user ID provided');
            showLoading(false);
            return;
        }

        // Use the same API endpoint as edit to get user data
        let userApiUrl = API_ROUTES.getUser;

        // Fix incorrect URL prefix if needed
        if (userApiUrl.startsWith('/users/')) {
            userApiUrl = '/admin' + userApiUrl;
        }

        // Use helper function to replace ID in URL
        const url = replaceIdInUrl(userApiUrl, actualUserId);

        if (DEBUG) {
            console.log('Fetching user data for viewing from URL:', url);
        }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`API request failed with status ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(apiUser => {
                // Get the user data
                const user = apiUser.data || apiUser;

                if (!user) throw new Error('User data not found in API response');

                // Populate the form fields (read-only)
                const form = modal.querySelector('form');
                if (!form) throw new Error('Form not found in view modal');

                // Populate user info
                populateField(form, 'name', user.name || user.fullName || user.full_name || '');
                populateField(form, 'email', user.email || user.mail || '');
                populateField(form, 'phone_number', user.phone_number || user.phone || user.phoneNumber || '');

                // Account settings
                populateSelectField(form, 'role', user.role || user.userRole || user.user_role || 'CLIENT');
                populateSelectField(form, 'account_status', user.account_status || user.status || user.accountStatus || 'ACTIVE');
                populateSelectField(form, 'gender', user.gender || '');

                // Handle date of birth if exists
                if (user.date_of_birth) {
                    populateField(form, 'date_of_birth', user.date_of_birth);
                }

                // Show the modal
                let bsModal = bootstrap.Modal.getInstance(modal);
                if (!bsModal) {
                    bsModal = new bootstrap.Modal(modal, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                }
                bsModal.show();

                showLoading(false);
            })
            .catch(error => {
                console.error('Error loading user data for viewing:', error);
                showError('Could not load user data: ' + error.message);
                showLoading(false);
            });
    } catch (error) {
        console.error('Error preparing view modal:', error);
        showError('Could not show user details: ' + error.message);
        showLoading(false);
    }
}
