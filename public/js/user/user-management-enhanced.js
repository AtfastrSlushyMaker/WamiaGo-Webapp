/**
 * User Management Enhanced JavaScript
 * This script provides dynamic functionality for the user management page
 */

// Initial setup
document.addEventListener('DOMContentLoaded', function () {
    // Initialize variables
    let currentPage = 1;
    let perPage = 10;
    let filters = {
        role: document.getElementById('filter-role').value,
        status: document.getElementById('filter-status').value,
        verified: document.getElementById('filter-verified').value,
        search: document.getElementById('user-search').value
    };

    // Load users on page load
    loadUsers(currentPage, perPage, filters);

    // Set up event listeners for filters
    document.getElementById('filter-role').addEventListener('change', function () {
        filters.role = this.value;
        loadUsers(1, perPage, filters);
    });

    document.getElementById('filter-status').addEventListener('change', function () {
        filters.status = this.value;
        loadUsers(1, perPage, filters);
    });

    document.getElementById('filter-verified').addEventListener('change', function () {
        filters.verified = this.value;
        loadUsers(1, perPage, filters);
    });

    document.getElementById('page-size').addEventListener('change', function () {
        perPage = parseInt(this.value);
        loadUsers(1, perPage, filters);
    });

    // Search functionality
    let searchTimer;
    document.getElementById('user-search').addEventListener('input', function () {
        filters.search = this.value;
        document.getElementById('clear-search').style.display = this.value ? 'block' : 'none';

        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            loadUsers(1, perPage, filters);
        }, 300);
    });

    // Clear search
    document.getElementById('clear-search').addEventListener('click', function () {
        document.getElementById('user-search').value = '';
        filters.search = '';
        this.style.display = 'none';
        loadUsers(1, perPage, filters);
    });

    // View toggle
    document.getElementById('list-view-btn').addEventListener('click', function () {
        document.getElementById('list-view').style.display = 'block';
        document.getElementById('card-view').style.display = 'none';
        this.classList.add('active');
        document.getElementById('card-view-btn').classList.remove('active');
    });

    document.getElementById('card-view-btn').addEventListener('click', function () {
        document.getElementById('list-view').style.display = 'none';
        document.getElementById('card-view').style.display = 'block';
        this.classList.add('active');
        document.getElementById('list-view-btn').classList.remove('active');
    });

    // Add user form submission
    document.getElementById('addUserForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const userData = Object.fromEntries(formData.entries());

        // Show loading indicator
        const submitBtn = document.getElementById('add-user-submit');
        const spinner = submitBtn.querySelector('.spinner-border');
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');

        fetch(`${window.apiBaseUrl}/users`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(userData)
        })
            .then(response => response.json())
            .then(data => {
                // Hide loading indicator
                submitBtn.disabled = false;
                spinner.classList.add('d-none');

                if (data.status === 'success') {
                    // Close modal and refresh users
                    bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                    loadUsers(currentPage, perPage, filters);
                    // Show success message
                    showAlert('success', 'User added successfully');
                    // Reset form
                    document.getElementById('addUserForm').reset();
                } else {
                    // Show validation errors
                    showAlert('danger', data.message || 'Failed to add user');
                    if (data.errors) {
                        displayFormErrors('add', data.errors);
                    }
                }
            })
            .catch(error => {
                // Hide loading indicator
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
                showAlert('danger', 'An error occurred while adding the user');
                console.error('Add user error:', error);
            });
    });

    // Edit user form submission
    document.getElementById('editUserForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const userData = Object.fromEntries(formData.entries());
        const userId = userData.id_user;

        // Show loading indicator
        const submitBtn = document.getElementById('edit-user-submit');
        const spinner = submitBtn.querySelector('.spinner-border');
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');

        fetch(window.userUpdateUrl.replace('USER_ID', userId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(userData)
        })
            .then(response => response.json())
            .then(data => {
                // Hide loading indicator
                submitBtn.disabled = false;
                spinner.classList.add('d-none');

                if (data.status === 'success') {
                    // Close modal and refresh users
                    bootstrap.Modal.getInstance(document.getElementById('edit-user-modal')).hide();
                    loadUsers(currentPage, perPage, filters);
                    // Show success message
                    showAlert('success', 'User updated successfully');
                } else {
                    // Show validation errors
                    showAlert('danger', data.message || 'Failed to update user');
                    if (data.errors) {
                        displayFormErrors('edit', data.errors);
                    }
                }
            })
            .catch(error => {
                // Hide loading indicator
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
                showAlert('danger', 'An error occurred while updating the user');
                console.error('Edit user error:', error);
            });
    });

    // Handle delete user button
    document.body.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('delete-user-btn')) {
            const userId = e.target.dataset.userId;
            const userName = e.target.dataset.userName;

            // Set user name in modal
            document.getElementById('delete-user-name').textContent = userName;

            // Set up delete confirmation button
            document.getElementById('confirm-delete-btn').dataset.userId = userId;

            // Show modal
            let deleteModal = new bootstrap.Modal(document.getElementById('delete-user-modal'));
            deleteModal.show();
        }
    });

    // Confirm delete user
    document.getElementById('confirm-delete-btn').addEventListener('click', function () {
        const userId = this.dataset.userId;

        // Show loading indicator
        const submitBtn = this;
        const spinner = submitBtn.querySelector('.spinner-border');
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');

        fetch(window.userDeleteUrl.replace('USER_ID', userId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                // Hide loading indicator
                submitBtn.disabled = false;
                spinner.classList.add('d-none');

                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('delete-user-modal')).hide();

                if (data.status === 'success') {
                    // Refresh users
                    loadUsers(currentPage, perPage, filters);
                    // Show success message
                    showAlert('success', 'User deleted successfully');
                } else {
                    // Show error message
                    showAlert('danger', data.message || 'Failed to delete user');
                }
            })
            .catch(error => {
                // Hide loading indicator
                submitBtn.disabled = false;
                spinner.classList.add('d-none');

                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('delete-user-modal')).hide();

                showAlert('danger', 'An error occurred while deleting the user');
                console.error('Delete user error:', error);
            });
    });
});

// Helper function to load users with filters and pagination
function loadUsers(page, limit, filters) {
    // Show loading overlay
    document.getElementById('loading-overlay').style.display = 'flex';

    // Build query string
    let queryParams = new URLSearchParams({
        page: page,
        perPage: limit
    });

    // Add filters to query
    if (filters.role) queryParams.append('role', filters.role);
    if (filters.status) queryParams.append('status', filters.status);
    if (filters.verified !== null && filters.verified !== '') queryParams.append('verified', filters.verified);
    if (filters.search) queryParams.append('search', filters.search);

    // Fetch users from API
    fetch(`${window.apiBaseUrl}/users?${queryParams.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            // Hide loading overlay
            document.getElementById('loading-overlay').style.display = 'none';

            if (data.status === 'success') {
                // Update users data
                renderUsers(data.data);

                // Update pagination if metadata is available
                if (data.meta) {
                    updatePagination(data.meta.current_page, data.meta.last_page, data.meta.total);
                } else {
                    // Fallback if meta isn't available - create simple pagination
                    const totalPages = Math.ceil(data.data.length / limit);
                    updatePagination(page, totalPages, data.data.length);
                }

                // Update showing results
                document.getElementById('showing-results').textContent = data.data.length;
                document.getElementById('total-results').textContent = data.meta ? data.meta.total : data.data.length;
            } else {
                showAlert('danger', data.message || 'Failed to load users');
            }
        })
        .catch(error => {
            // Hide loading overlay
            document.getElementById('loading-overlay').style.display = 'none';
            showAlert('danger', 'An error occurred while loading users');
            console.error('Load users error:', error);
        });
}

// Render users in both list and card views
function renderUsers(users) {
    const tableBody = document.getElementById('users-table-body');
    const cardsContainer = document.getElementById('users-cards');

    // Clear existing content
    tableBody.innerHTML = '';
    cardsContainer.innerHTML = '';

    if (users.length === 0) {
        // No users found
        tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i> No users found matching the criteria
                    </div>
                </td>
            </tr>
        `;

        cardsContainer.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No users found matching the criteria
                </div>
            </div>
        `;
        return;
    }

    // Helper function to get badge class for role
    function getRoleBadgeClass(role) {
        return role === 'ADMIN' ? 'bg-admin' : 'bg-client';
    }

    // Helper function to get badge class for status
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'ACTIVE': return 'bg-success';
            case 'SUSPENDED': return 'bg-warning';
            case 'BANNED': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }

    // Helper function to format date
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    // Render table rows and cards
    users.forEach((user, index) => {
        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${index + 1}</td>
            <td>
                <div class="d-flex align-items-center">
                    <img src="${user.profilePicture || '/images/default-avatar.png'}" alt="${user.name}" class="user-avatar-sm me-3" onerror="this.src='/images/default-avatar.png'">
                    <div>
                        <div class="fw-bold">${user.name}</div>
                        <small class="text-muted">ID: ${user.id}</small>
                    </div>
                </div>
            </td>
            <td>
                <div><i class="fas fa-envelope me-2 text-muted"></i> ${user.email}</div>
                <div><i class="fas fa-phone me-2 text-muted"></i> ${user.phone_number || 'N/A'}</div>
            </td>
            <td>${formatDate(user.dateOfBirth)}</td>
            <td>${user.gender || 'N/A'}</td>
            <td><span class="badge ${getRoleBadgeClass(user.role)}">${user.role}</span></td>
            <td><span class="badge ${getStatusBadgeClass(user.accountStatus)}">${user.accountStatus}</span></td>
            <td>
                <span class="badge ${user.isVerified ? 'bg-success' : 'bg-danger'}">
                    ${user.isVerified ? 'Verified' : 'Unverified'}
                </span>
            </td>
            <td>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary view-user-btn" data-user-id="${user.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary edit-user-btn" data-user-id="${user.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" data-user-id="${user.id}" data-user-name="${user.name}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;

        tableBody.appendChild(row);

        // Render card view
        const card = document.createElement('div');
        card.className = 'col-md-6 col-lg-4 mb-4';

        card.innerHTML = `
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="badge ${getRoleBadgeClass(user.role)}">${user.role}</span>
                    <span class="badge ${getStatusBadgeClass(user.accountStatus)}">${user.accountStatus}</span>
                </div>
                <div class="card-body text-center">
                    <img src="${user.profilePicture || '/images/default-avatar.png'}" alt="${user.name}" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;" onerror="this.src='/images/default-avatar.png'">
                    <h5 class="card-title">${user.name}</h5>
                    <p class="card-text">
                        <i class="fas fa-envelope me-2 text-muted"></i> ${user.email}<br>
                        <i class="fas fa-phone me-2 text-muted"></i> ${user.phone_number || 'N/A'}<br>
                        <i class="fas fa-birthday-cake me-2 text-muted"></i> ${formatDate(user.dateOfBirth)}<br>
                        <i class="fas fa-venus-mars me-2 text-muted"></i> ${user.gender || 'N/A'}
                    </p>
                    <div class="mb-3">
                        <span class="badge ${user.isVerified ? 'bg-success' : 'bg-danger'}">
                            ${user.isVerified ? 'Verified' : 'Unverified'}
                        </span>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary view-user-btn" data-user-id="${user.id}">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary edit-user-btn" data-user-id="${user.id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" data-user-id="${user.id}" data-user-name="${user.name}">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `;

        cardsContainer.appendChild(card);
    });
}

// Update pagination
function updatePagination(currentPage, lastPage, total) {
    const paginationEl = document.querySelector('.pagination');
    const gotoPageEl = document.getElementById('goto-page');

    // Clear existing pagination
    paginationEl.innerHTML = '';
    gotoPageEl.innerHTML = '';

    // Previous button
    const prevItem = document.createElement('li');
    prevItem.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevItem.innerHTML = `
        <button class="page-link" data-page="${currentPage - 1}" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
        </button>
    `;
    paginationEl.appendChild(prevItem);

    // Generate page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);

    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // First page
    if (startPage > 1) {
        const firstItem = document.createElement('li');
        firstItem.className = 'page-item';
        firstItem.innerHTML = `<button class="page-link" data-page="1">1</button>`;
        paginationEl.appendChild(firstItem);

        if (startPage > 2) {
            const ellipsisItem = document.createElement('li');
            ellipsisItem.className = 'page-item disabled';
            ellipsisItem.innerHTML = `<span class="page-link">...</span>`;
            paginationEl.appendChild(ellipsisItem);
        }
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement('li');
        pageItem.className = `page-item ${i === currentPage ? 'active' : ''}`;
        pageItem.innerHTML = `<button class="page-link" data-page="${i}">${i}</button>`;
        paginationEl.appendChild(pageItem);
    }

    // Last page
    if (endPage < lastPage) {
        if (endPage < lastPage - 1) {
            const ellipsisItem = document.createElement('li');
            ellipsisItem.className = 'page-item disabled';
            ellipsisItem.innerHTML = `<span class="page-link">...</span>`;
            paginationEl.appendChild(ellipsisItem);
        }

        const lastItem = document.createElement('li');
        lastItem.className = 'page-item';
        lastItem.innerHTML = `<button class="page-link" data-page="${lastPage}">${lastPage}</button>`;
        paginationEl.appendChild(lastItem);
    }

    // Next button
    const nextItem = document.createElement('li');
    nextItem.className = `page-item ${currentPage === lastPage ? 'disabled' : ''}`;
    nextItem.innerHTML = `
        <button class="page-link" data-page="${currentPage + 1}" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
        </button>
    `;
    paginationEl.appendChild(nextItem);

    // Set up page click handler
    paginationEl.querySelectorAll('.page-link[data-page]').forEach(el => {
        el.addEventListener('click', function () {
            const page = parseInt(this.dataset.page);
            loadUsers(page, 10, {});
        });
    });

    // Populate goto page dropdown
    for (let i = 1; i <= lastPage; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        option.selected = i === currentPage;
        gotoPageEl.appendChild(option);
    }

    // Set up goto page handler
    gotoPageEl.addEventListener('change', function () {
        const page = parseInt(this.value);
        loadUsers(page, 10, {});
    });
}

// Helper function to display validation errors
function displayFormErrors(prefix, errors) {
    // Reset previous errors
    document.querySelectorAll(`.invalid-feedback[id^="${prefix}-"]`).forEach(el => {
        el.textContent = '';
    });
    document.querySelectorAll(`.form-control[id^="${prefix}-"]`).forEach(el => {
        el.classList.remove('is-invalid');
    });

    // Display new errors
    for (const field in errors) {
        const inputEl = document.getElementById(`${prefix}-${field}`);
        const errorEl = document.getElementById(`${prefix}-${field}-error`);

        if (inputEl && errorEl) {
            inputEl.classList.add('is-invalid');
            errorEl.textContent = errors[field].join(' ');
        }
    }
}

// Helper function to show alerts
function showAlert(type, message) {
    // Create alerts container if it doesn't exist
    let alertsContainer = document.getElementById('alerts-container');
    if (!alertsContainer) {
        alertsContainer = document.createElement('div');
        alertsContainer.id = 'alerts-container';
        alertsContainer.className = 'position-fixed top-0 end-0 p-3';
        alertsContainer.style.zIndex = '1050';
        document.body.appendChild(alertsContainer);
    }

    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    alertsContainer.appendChild(alert);

    // Auto-dismiss alert after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }
    }, 5000);
}
