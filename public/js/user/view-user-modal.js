/**
 * View User Modal Handler
 * Implements functionality for viewing comprehensive user details and action buttons
 */

document.addEventListener('DOMContentLoaded', function () {
    // Constants for user statuses
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_SUSPENDED = 'SUSPENDED';
    const STATUS_BANNED = 'BANNED';

    // Set up event listeners for view user buttons
    setupViewUserButtons();

    /**
     * Set up event listeners for view user buttons
     */
    function setupViewUserButtons() {
        // Use event delegation to handle view button clicks
        document.addEventListener('click', function (e) {
            const viewButton = e.target.closest('.view-user-btn, .view-user');

            if (viewButton) {
                const userId = viewButton.dataset.id;
                if (userId) {
                    fetchAndDisplayUserDetails(userId);
                }
            }
        });
    }

    /**
     * Fetch and display user details in the modal
     */
    function fetchAndDisplayUserDetails(userId) {
        // Show loading overlay
        const viewModal = document.getElementById('view-user-modal');
        if (!viewModal) return;

        // Get modal body for manipulating content
        const modalBody = viewModal.querySelector('.modal-body');

        // Display loading spinner
        modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading user details...</span>
                </div>
                <p class="mt-2">Loading user details...</p>
            </div>
        `;

        // Show the modal while loading data
        const modal = new bootstrap.Modal(viewModal);
        modal.show();

        // Construct URL for user details
        const apiUrl = window.apiBaseUrl ?
            `${window.apiBaseUrl}/users/${userId}` :
            `/api/users/${userId}`;

        // Fetch user details
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch user details. Status: ${response.status}`);
                }
                return response.json();
            })
            .then(user => {
                if (user) {
                    populateUserDetails(user);
                    setupActionButtons(user);
                } else {
                    throw new Error('User data not found');
                }
            })
            .catch(error => {
                console.error('Error fetching user details:', error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load user details: ${error.message}
                    </div>
                `;
            });
    }

    /**
     * Populate user details in the view modal
     */
    function populateUserDetails(user) {
        // Set user avatar
        const avatarImg = document.getElementById('view-user-avatar');
        if (avatarImg) {
            avatarImg.src = user.profilePicture || '/images/default-avatar.png';
            avatarImg.alt = `${user.name}'s Avatar`;
        }

        // Set user name and email
        document.getElementById('view-user-name').textContent = user.name || 'N/A';
        document.getElementById('view-user-email').textContent = user.email || 'N/A';

        // Set user ID
        document.getElementById('view-user-id').textContent = user.id || 'N/A';

        // Set user phone
        document.getElementById('view-user-phone').textContent = user.phone_number || 'N/A';

        // Set user gender
        document.getElementById('view-user-gender').textContent = user.gender || 'N/A';

        // Set date of birth with formatting
        const dob = user.date_of_birth ?
            new Date(user.date_of_birth).toLocaleDateString() : 'N/A';
        document.getElementById('view-user-dob').textContent = dob;

        // Set account status
        const accountStatus = document.getElementById('view-user-account-status');
        accountStatus.textContent = user.account_status || 'ACTIVE';

        // Set status badge color
        const statusBadge = document.getElementById('view-user-status-badge');
        if (statusBadge) {
            if (user.account_status === STATUS_BANNED) {
                statusBadge.className = 'position-absolute bottom-0 end-0 badge rounded-pill bg-danger';
                statusBadge.innerHTML = '<i class="fas fa-ban"></i>';
            } else if (user.account_status === STATUS_SUSPENDED) {
                statusBadge.className = 'position-absolute bottom-0 end-0 badge rounded-pill bg-warning';
                statusBadge.innerHTML = '<i class="fas fa-clock"></i>';
            } else {
                statusBadge.className = 'position-absolute bottom-0 end-0 badge rounded-pill bg-success';
                statusBadge.innerHTML = '<i class="fas fa-check-circle"></i>';
            }
        }

        // Set verified status
        document.getElementById('view-user-verified').textContent =
            user.isVerified ? 'Yes' : 'No';

        // Set role
        const role = user.role || 'CLIENT';
        document.getElementById('view-user-role').textContent = role;

        // Set role badge
        const roleBadge = document.getElementById('view-user-role-badge');
        if (roleBadge) {
            roleBadge.textContent = role;
            roleBadge.className = role === 'ADMIN' ?
                'badge bg-primary me-1' : 'badge bg-info me-1';
        }

        // Set verification badge
        const verificationBadge = document.getElementById('view-user-verification-badge');
        if (verificationBadge) {
            if (user.isVerified) {
                verificationBadge.textContent = 'Verified';
                verificationBadge.className = 'badge bg-success';
            } else {
                verificationBadge.textContent = 'Not Verified';
                verificationBadge.className = 'badge bg-secondary';
            }
        }
    }

    /**
     * Set up action buttons based on user status
     */
    function setupActionButtons(user) {
        // Status action buttons
        const activateBtn = document.getElementById('view-user-activate-btn');
        const suspendBtn = document.getElementById('view-user-suspend-btn');
        const banBtn = document.getElementById('view-user-ban-btn');

        // Hide all buttons initially
        if (activateBtn) activateBtn.style.display = 'none';
        if (suspendBtn) suspendBtn.style.display = 'none';
        if (banBtn) banBtn.style.display = 'none';

        // Show relevant buttons based on current status
        switch (user.account_status) {
            case STATUS_ACTIVE:
                if (suspendBtn) suspendBtn.style.display = 'inline-block';
                if (banBtn) banBtn.style.display = 'inline-block';
                break;
            case STATUS_SUSPENDED:
                if (activateBtn) activateBtn.style.display = 'inline-block';
                if (banBtn) banBtn.style.display = 'inline-block';
                break;
            case STATUS_BANNED:
                if (activateBtn) activateBtn.style.display = 'inline-block';
                break;
            default:
                if (activateBtn) activateBtn.style.display = 'inline-block';
                break;
        }

        // Verification button
        const verifyBtn = document.getElementById('view-user-verify-btn');
        if (verifyBtn) {
            if (user.isVerified) {
                verifyBtn.innerHTML = '<i class="fas fa-user-times me-1"></i> Unverify';
                verifyBtn.classList.remove('btn-info');
                verifyBtn.classList.add('btn-secondary');
            } else {
                verifyBtn.innerHTML = '<i class="fas fa-user-check me-1"></i> Verify';
                verifyBtn.classList.remove('btn-secondary');
                verifyBtn.classList.add('btn-info');
            }
        }

        // Set up action button event listeners
        setupActionButtonEvents(user.id);
    }

    /**
     * Set up event listeners for action buttons
     */
    function setupActionButtonEvents(userId) {
        // Activate button
        const activateBtn = document.getElementById('view-user-activate-btn');
        if (activateBtn) {
            activateBtn.onclick = function () {
                updateUserStatus(userId, STATUS_ACTIVE);
            };
        }

        // Suspend button
        const suspendBtn = document.getElementById('view-user-suspend-btn');
        if (suspendBtn) {
            suspendBtn.onclick = function () {
                updateUserStatus(userId, STATUS_SUSPENDED);
            };
        }

        // Ban button
        const banBtn = document.getElementById('view-user-ban-btn');
        if (banBtn) {
            banBtn.onclick = function () {
                updateUserStatus(userId, STATUS_BANNED);
            };
        }

        // Verify button
        const verifyBtn = document.getElementById('view-user-verify-btn');
        if (verifyBtn) {
            verifyBtn.onclick = function () {
                const isCurrentlyVerified = verifyBtn.innerHTML.includes('Unverify');
                toggleUserVerification(userId, !isCurrentlyVerified);
            };
        }

        // Edit button
        const editBtn = document.getElementById('view-user-edit-btn');
        if (editBtn) {
            editBtn.onclick = function () {
                // Close current modal
                const viewModal = document.getElementById('view-user-modal');
                const bsViewModal = bootstrap.Modal.getInstance(viewModal);
                if (bsViewModal) bsViewModal.hide();

                // Trigger edit modal with a small delay
                setTimeout(() => {
                    const editBtn = document.querySelector(`.edit-user-btn[data-id="${userId}"]`);
                    if (editBtn) {
                        editBtn.click();
                    } else {
                        // If no edit button is found, try to open the edit modal directly
                        const editModal = document.getElementById('edit-user-modal');
                        if (editModal) {
                            // Set the user ID for the edit form
                            const userIdInput = editModal.querySelector('input[name="user_id"]');
                            if (userIdInput) userIdInput.value = userId;

                            // Load user data into edit form
                            loadUserIntoEditForm(userId);

                            // Show edit modal
                            const bsEditModal = new bootstrap.Modal(editModal);
                            bsEditModal.show();
                        }
                    }
                }, 300);
            };
        }

        // Delete button
        const deleteBtn = document.getElementById('view-user-delete-btn');
        if (deleteBtn) {
            deleteBtn.onclick = function () {
                // Close current modal
                const viewModal = document.getElementById('view-user-modal');
                const bsViewModal = bootstrap.Modal.getInstance(viewModal);
                if (bsViewModal) bsViewModal.hide();

                // Trigger delete confirmation with a small delay
                setTimeout(() => {
                    const deleteBtn = document.querySelector(`.delete-user-btn[data-id="${userId}"]`);
                    if (deleteBtn) {
                        deleteBtn.click();
                    } else {
                        confirmDeleteUser(userId);
                    }
                }, 300);
            };
        }
    }

    /**
     * Update user status (active, suspended, banned)
     */
    function updateUserStatus(userId, newStatus) {
        // Construct update URL
        const updateUrl = window.userUpdateUrl ?
            window.userUpdateUrl.replace('USER_ID', userId) :
            `/api/users/${userId}/update-status`;

        // Show loading spinner on buttons
        const actionButtons = document.querySelectorAll('#status-action-group button');
        actionButtons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + btn.textContent;
        });

        // Send request to update status
        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                account_status: newStatus
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to update status. Status code: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    showToast('Success', `User status updated to ${newStatus}`, 'success');

                    // Reload user details
                    fetchAndDisplayUserDetails(userId);

                    // Refresh user list if function exists
                    if (typeof reloadUsers === 'function') {
                        reloadUsers();
                    }
                } else {
                    throw new Error(data.error || 'Failed to update user status');
                }
            })
            .catch(error => {
                console.error('Error updating user status:', error);
                showToast('Error', error.message, 'error');
            })
            .finally(() => {
                // Re-enable buttons
                actionButtons.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = btn.innerHTML.replace('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ', '');
                });
            });
    }

    /**
     * Toggle user verification status
     */
    function toggleUserVerification(userId, setVerified) {
        // Construct verification URL
        const verifyUrl = window.userVerifyUrl ?
            window.userVerifyUrl.replace('USER_ID', userId) :
            `/api/users/${userId}/verify`;

        // Show loading spinner on verify button
        const verifyBtn = document.getElementById('view-user-verify-btn');
        if (verifyBtn) {
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' +
                (setVerified ? 'Verifying...' : 'Unverifying...');
        }

        // Send request to toggle verification
        fetch(verifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                isVerified: setVerified
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to update verification. Status code: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    showToast('Success', `User ${setVerified ? 'verified' : 'unverified'} successfully`, 'success');

                    // Reload user details
                    fetchAndDisplayUserDetails(userId);

                    // Refresh user list if function exists
                    if (typeof reloadUsers === 'function') {
                        reloadUsers();
                    }
                } else {
                    throw new Error(data.error || 'Failed to update verification status');
                }
            })
            .catch(error => {
                console.error('Error updating verification status:', error);
                showToast('Error', error.message, 'error');
            })
            .finally(() => {
                // Re-enable verify button
                if (verifyBtn) {
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = setVerified ?
                        '<i class="fas fa-user-check me-1"></i> Verify' :
                        '<i class="fas fa-user-times me-1"></i> Unverify';
                }
            });
    }

    /**
     * Confirm and delete user
     */
    function confirmDeleteUser(userId) {
        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="confirm-delete-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Delete User</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                                <h5>Are you sure you want to delete this user?</h5>
                                <p class="text-muted">This action cannot be undone.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                                <i class="fas fa-trash-alt me-1"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Append modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('confirm-delete-modal'));
        modal.show();

        // Handle confirm button click
        document.getElementById('confirm-delete-btn').addEventListener('click', function () {
            deleteUser(userId);
            modal.hide();

            // Remove modal from DOM after hiding
            document.getElementById('confirm-delete-modal').addEventListener('hidden.bs.modal', function () {
                this.remove();
            });
        });
    }

    /**
     * Delete user
     */
    function deleteUser(userId) {
        // Construct delete URL
        const deleteUrl = window.userDeleteUrl ?
            window.userDeleteUrl.replace('USER_ID', userId) :
            `/api/users/${userId}/delete`;

        // Show loading overlay if it exists
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }

        // Send request to delete user
        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to delete user. Status code: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    showToast('Success', 'User deleted successfully', 'success');

                    // Close the view modal if it's open
                    const viewModal = document.getElementById('view-user-modal');
                    const bsViewModal = bootstrap.Modal.getInstance(viewModal);
                    if (bsViewModal) {
                        bsViewModal.hide();
                    }

                    // Refresh user list if function exists
                    if (typeof reloadUsers === 'function') {
                        reloadUsers();
                    }
                } else {
                    throw new Error(data.error || 'Failed to delete user');
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                showToast('Error', error.message, 'error');
            })
            .finally(() => {
                // Hide loading overlay
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
            });
    }

    /**
     * Load user data into edit form
     */
    function loadUserIntoEditForm(userId) {
        // Construct URL for user details
        const apiUrl = window.apiBaseUrl ?
            `${window.apiBaseUrl}/users/${userId}` :
            `/api/users/${userId}`;

        // Fetch user details
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch user details. Status: ${response.status}`);
                }
                return response.json();
            })
            .then(user => {
                if (user) {
                    // Populate form fields
                    const editForm = document.getElementById('editUserForm');
                    if (editForm) {
                        // Set form fields
                        const fields = [
                            { id: 'edit-name', value: user.name },
                            { id: 'edit-email', value: user.email },
                            { id: 'edit-phone', value: user.phone_number },
                            { id: 'edit-role', value: user.role },
                            { id: 'edit-gender', value: user.gender },
                            { id: 'edit-status', value: user.account_status },
                            { id: 'edit-dob', value: user.date_of_birth ? user.date_of_birth.substring(0, 10) : '' },
                            { id: 'edit-profile-pic', value: user.profilePicture }
                        ];

                        fields.forEach(field => {
                            const element = document.getElementById(field.id);
                            if (element) {
                                element.value = field.value || '';
                            }
                        });

                        // Set user ID for form submission
                        const userIdField = editForm.querySelector('input[name="user_id"]');
                        if (userIdField) {
                            userIdField.value = user.id;
                        }
                    }
                } else {
                    throw new Error('User data not found');
                }
            })
            .catch(error => {
                console.error('Error loading user into edit form:', error);
                showToast('Error', error.message, 'error');
            });
    }

    /**
     * Display toast notification
     */
    function showToast(title, message, type = 'info') {
        // Check if toast container exists, create if not
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' :
            type === 'error' ? 'bg-danger' :
                type === 'warning' ? 'bg-warning' : 'bg-primary';

        const iconClass = type === 'success' ? 'fas fa-check-circle' :
            type === 'error' ? 'fas fa-exclamation-circle' :
                type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';

        const toastHtml = `
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                <div class="toast-header ${bgClass} text-white">
                    <i class="${iconClass} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        // Add toast to container
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        // Initialize and show toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });

        toast.show();

        // Remove toast from DOM after hiding
        toastElement.addEventListener('hidden.bs.toast', function () {
            this.remove();
        });
    }
}); 