/**
 * User Management JavaScript
 * Handles all user management functionality including:
 * - List/Card view toggling
 * - User data loading
 * - User creation, editing and deletion
 * - Form validation
 * - UI interactions
 */

// These variables will be populated by the template
let userGetUrl = window.userGetUrl;
let userEditUrl = window.userEditUrl;
let userUpdateUrl = window.userUpdateUrl;
let userDeleteUrl = window.userDeleteUrl;
let userDeleteApiUrl = window.userDeleteApiUrl;
let userCreateUrl = window.userCreateUrl;
let addUserRouteUrl = window.addUserRouteUrl;
let apiRouteUrl = window.apiRouteUrl;

document.addEventListener('DOMContentLoaded', function () {
    // Debug output to verify route variables
    console.log('Route variables:', {
        userGetUrl,
        userEditUrl,
        userUpdateUrl,
        userDeleteUrl,
        userDeleteApiUrl,
        userCreateUrl,
        addUserRouteUrl,
        apiRouteUrl
    });

    // View toggle functionality
    const listViewBtn = document.getElementById('list-view-btn');
    const cardViewBtn = document.getElementById('card-view-btn');
    const listView = document.getElementById('list-view');
    const cardView = document.getElementById('card-view');

    // Initialize all modals
    let editUserModal, addUserModal, deleteUserModal;

    if (document.getElementById('edit-user-modal')) {
        editUserModal = new bootstrap.Modal(document.getElementById('edit-user-modal'));
    }
    if (document.getElementById('add-user-modal')) {
        addUserModal = new bootstrap.Modal(document.getElementById('add-user-modal'));
    }
    if (document.getElementById('delete-user-modal')) {
        deleteUserModal = new bootstrap.Modal(document.getElementById('delete-user-modal'));
    }

    // Add cancel button handlers
    document.getElementById('edit-cancel-btn')?.addEventListener('click', function () {
        editUserModal.hide();
    });

    document.getElementById('add-cancel-btn')?.addEventListener('click', function () {
        addUserModal.hide();
    });

    document.getElementById('delete-cancel-btn')?.addEventListener('click', function () {
        deleteUserModal.hide();
    });

    listViewBtn.addEventListener('click', function () {
        listViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
        listView.style.display = 'block';
        cardView.style.display = 'none';
        localStorage.setItem('userViewPreference', 'list');
    });

    cardViewBtn.addEventListener('click', function () {
        cardViewBtn.classList.add('active');
        listViewBtn.classList.remove('active');
        cardView.style.display = 'block';
        listView.style.display = 'none';
        localStorage.setItem('userViewPreference', 'card');
    });

    // Restore user preference if available
    const userViewPreference = localStorage.getItem('userViewPreference');
    if (userViewPreference === 'card') {
        cardViewBtn.click();
    } else {
        listViewBtn.click();
    }



    if (document.getElementById('edit-user-modal')) {
        editUserModal = new bootstrap.Modal(document.getElementById('edit-user-modal'));
    }
    if (document.getElementById('add-user-modal')) {
        addUserModal = new bootstrap.Modal(document.getElementById('add-user-modal'));
    }
    if (document.getElementById('delete-user-modal')) {
        deleteUserModal = new bootstrap.Modal(document.getElementById('delete-user-modal'));
    }

    // Add cancel button handlers
    document.getElementById('edit-cancel-btn')?.addEventListener('click', function () {
        editUserModal.hide();
    });

    document.getElementById('add-cancel-btn')?.addEventListener('click', function () {
        addUserModal.hide();
    });

    document.getElementById('delete-cancel-btn')?.addEventListener('click', function () {
        deleteUserModal.hide();
    });

    // Add User Modal
    const addUserBtn = document.getElementById('add-user-btn');
    const createUserBtn = document.getElementById('create-user-btn');
    const addUserForm = document.getElementById('add-user-form');

    if (addUserBtn) {
        addUserBtn.addEventListener('click', function () {
            clearFormErrors(addUserForm);
            addUserForm.reset();

            // Fetch the new user form with CSRF token
            fetch(addUserRouteUrl, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
                .then(response => response.text())
                .then(html => {
                    // Replace modal content with the form
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const newForm = tempDiv.querySelector('form');
                    if (newForm) {
                        addUserForm.innerHTML = newForm.innerHTML;

                        // Re-initialize password strength meter
                        initPasswordStrengthMeter();
                    }

                    // Show the modal
                    addUserModal.show();
                })
                .catch(error => {
                    console.error('Error fetching user form:', error);
                    showToast('error', 'Error loading form: ' + error.message);
                });
        });
    }

    if (createUserBtn) {
        createUserBtn.addEventListener('click', function () {
            createUser();
        });
    }

    // Password visibility toggle
    function initPasswordToggle() {
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function () {
                const passwordInput = this.closest('.input-group').querySelector('input');
                const icon = this.querySelector('i');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }

    // Initialize password visibility toggles
    initPasswordToggle();

    // Initialize password strength meter
    function initPasswordStrengthMeter() {
        const addPasswordInput = document.getElementById('add-password');
        if (addPasswordInput) {
            const strengthMeter = document.querySelector('.password-strength-meter');
            const strengthText = document.querySelector('.password-strength-text');

            addPasswordInput.addEventListener('input', function () {
                const password = this.value;

                if (password) {
                    strengthMeter.style.display = 'block';
                    const strength = checkPasswordStrength(password);
                    updatePasswordStrengthUI(strength, strengthMeter, strengthText);
                } else {
                    strengthMeter.style.display = 'none';
                    strengthText.textContent = '';
                }
            });
        }
    }

    // Initialize password strength meter on page load
    initPasswordStrengthMeter();

    // Search functionality
    const searchInput = document.getElementById('user-search');
    const filterStatus = document.getElementById('filter-status');
    const filterRole = document.getElementById('filter-role');
    const filterVerified = document.getElementById('filter-verified');

    // Handle filter button clicks
    document.getElementById('apply-filters-btn')?.addEventListener('click', function () {
        applyFilters();
    });

    document.getElementById('reset-filters-btn')?.addEventListener('click', function () {
        // Reset all filter inputs
        if (searchInput) searchInput.value = '';
        if (filterStatus) filterStatus.value = '';
        if (filterRole) filterRole.value = '';
        if (filterVerified) filterVerified.value = '';

        // Apply the reset filters
        applyFilters();
    });

    document.getElementById('search-btn')?.addEventListener('click', function () {
        applyFilters();
    });

    // Add real-time filtering on dropdown changes
    filterStatus?.addEventListener('change', function () {
        debounce(applyFilters, 300)();
    });

    filterRole?.addEventListener('change', function () {
        debounce(applyFilters, 300)();
    });

    filterVerified?.addEventListener('change', function () {
        debounce(applyFilters, 300)();
    });

    // Handle Enter key in search field and implement real-time search
    searchInput?.addEventListener('keyup', function (event) {
        if (event.key === 'Enter') {
            applyFilters();
        } else {
            // Apply real-time search after user stops typing for 500ms
            debounce(applyFilters, 500)();
        }
    });

    // Handle page size change
    document.getElementById('page-size')?.addEventListener('change', function () {
        currentLimit = parseInt(this.value);
        currentPage = 1; // Reset to first page when changing limit
        applyFilters();
    });

    // More comprehensive filter application function
    function applyFilters() {
        const searchTerm = searchInput?.value.trim() || '';
        const roleFilter = filterRole?.value || '';
        const statusFilter = filterStatus?.value || '';
        const verifiedFilter = filterVerified?.value || '';

        // Reset to first page when applying new filters
        currentPage = 1;

        // If search term is very short and no other filters, we might want to delay search
        if (searchTerm.length === 1 && !roleFilter && !statusFilter && !verifiedFilter) {
            console.log('Search term too short, waiting for more input');
            return; // Wait for more characters before triggering search
        }

        loadUsers(searchTerm, roleFilter, statusFilter, verifiedFilter);
    }

    // Helper function for debounce
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Check password strength
    function checkPasswordStrength(password) {
        let strength = 0;

        if (password.length >= 8) strength += 1;
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[a-z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;

        return strength;
    }

    // Update password strength UI
    function updatePasswordStrengthUI(strength, meter, text) {
        const progressBar = meter.querySelector('.progress-bar');

        switch (strength) {
            case 0:
            case 1:
                progressBar.style.width = '20%';
                progressBar.className = 'progress-bar bg-danger';
                text.textContent = 'Very Weak Password';
                break;
            case 2:
                progressBar.style.width = '40%';
                progressBar.className = 'progress-bar bg-warning';
                text.textContent = 'Weak Password';
                break;
            case 3:
                progressBar.style.width = '60%';
                progressBar.className = 'progress-bar bg-info';
                text.textContent = 'Moderate Password';
                break;
            case 4:
                progressBar.style.width = '80%';
                progressBar.className = 'progress-bar bg-primary';
                text.textContent = 'Strong Password';
                break;
            case 5:
                progressBar.style.width = '100%';
                progressBar.className = 'progress-bar bg-success';
                text.textContent = 'Very Strong Password';
                break;
        }
    }

    // Tracking variables for pagination and sorting
    let currentPage = 1;
    let currentLimit = 10;
    let currentSortBy = 'name';
    let currentSortDir = 'asc';
    let totalPages = 1;

    // Track the ongoing request to prevent overlapping requests
    let currentRequest = null;

    // Load users from API with improved pagination handling
    function loadUsers(search = '', role = '', status = '', verified = '') {
        showLoading();

        // Build the query string
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (role) params.append('role', role);
        if (status) params.append('status', status);
        if (verified) params.append('verified', verified);
        params.append('sortBy', currentSortBy);
        params.append('sortDir', currentSortDir);
        params.append('page', currentPage);
        params.append('limit', currentLimit);

        // Debug information to help troubleshooting
        console.log('Pagination parameters:', {
            page: currentPage,
            limit: currentLimit,
            sortBy: currentSortBy,
            sortDir: currentSortDir
        });

        // Enhanced debug information
        console.log('Route variables available:', {
            userGetUrl,
            userEditUrl,
            userUpdateUrl,
            userDeleteUrl,
            userDeleteApiUrl,
            userCreateUrl,
            addUserRouteUrl,
            apiRouteUrl
        });

        // Debug: Log the API URL with all parameters
        const apiUrl = apiRouteUrl + (params.toString() ? '?' + params.toString() : '');
        console.log('Loading users from API URL:', apiUrl);

        // Cancel any existing fetch
        if (currentRequest) {
            currentRequest.abort();
        }

        // Use AbortController to be able to cancel requests
        const controller = new AbortController();
        currentRequest = controller;

        // Use the API URL from the global variable set in the template
        fetch(apiUrl, {
            credentials: 'same-origin',
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                console.log('Response received:', response);
                if (!response.ok) {
                    console.error('API Response Status:', response.status, response.statusText);
                    return response.text().then(text => {
                        console.error('API Response Body:', text);
                        throw new Error('Error fetching users: ' + response.status + ' ' + response.statusText);
                    });
                }
                return response.json();
            })
            .then(function (data) {
                console.log('Users loaded successfully:', data);

                // More robust data structure handling
                const users = data.rows || data.users || data;
                const total = data.total || users.length;

                // Debug pagination values
                console.log('Pagination data:', {
                    total: total,
                    currentPage: currentPage,
                    totalPages: Math.ceil(total / currentLimit),
                    itemsPerPage: currentLimit
                });

                populateUserTable(users);
                populateUserCards(users);
                updateUserCounts(users);
                updatePagination(total);
                hideLoading();
            })
            .catch(function (error) {
                console.error('Error details:', error);
                hideLoading();
                showToast('error', 'Error loading users: ' + error.message);

                // Display a helpful message in the table body
                const tableBody = document.getElementById('users-table-body');
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i> 
                                    Error loading users. Please check the console for details or try refreshing the page.
                                </div>
                            </td>
                        </tr>
                    `;
                }
            });
    }

    // Update pagination controls
    function updatePagination(totalItems) {
        totalPages = Math.ceil(totalItems / currentLimit);

        // Update counts
        document.getElementById('showing-results').textContent = Math.min(currentLimit, totalItems);
        document.getElementById('total-results').textContent = totalItems;
        document.getElementById('showing-results-cards').textContent = Math.min(currentLimit, totalItems);
        document.getElementById('total-results-cards').textContent = totalItems;

        // Generate pagination for list view
        const paginationContainer = document.querySelector('.pagination-container');
        if (paginationContainer) {
            let paginationHtml = `<ul class="pagination pagination-sm justify-content-center">`;

            // Previous button
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;

            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, startPage + 4);

            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            // Next button
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;

            paginationHtml += `</ul>`;
            paginationContainer.innerHTML = paginationHtml;

            // Add event listeners to pagination links
            paginationContainer.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const page = parseInt(this.getAttribute('data-page'));
                    if (page && page !== currentPage && page > 0 && page <= totalPages) {
                        currentPage = page;
                        applyFilters();
                    }
                });
            });
        }

        // Generate pagination for card view (similar structure)
        const cardPaginationContainer = document.querySelector('#card-view .pagination');
        if (cardPaginationContainer) {
            let paginationHtml = '';

            // Previous button
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;

            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, startPage + 4);

            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            // Next button
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;

            cardPaginationContainer.innerHTML = paginationHtml;

            // Add event listeners to pagination links
            cardPaginationContainer.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const page = parseInt(this.getAttribute('data-page'));
                    if (page && page !== currentPage && page > 0 && page <= totalPages) {
                        currentPage = page;
                        applyFilters();
                    }
                });
            });
        }
    }

    // Update user counts in dashboard cards
    function updateUserCounts(users) {
        // Count total users
        document.getElementById('total-users-count').textContent = users.length;

        // Count active, suspended and banned users
        let activeCount = 0;
        let suspendedCount = 0;
        let bannedCount = 0;

        users.forEach(user => {
            switch (user.accountStatus) {
                case 'ACTIVE':
                    activeCount++;
                    break;
                case 'SUSPENDED':
                    suspendedCount++;
                    break;
                case 'BANNED':
                    bannedCount++;
                    break;
            }
        });

        document.getElementById('active-users-count').textContent = activeCount;
        document.getElementById('suspended-users-count').textContent = suspendedCount;
        document.getElementById('banned-users-count').textContent = bannedCount;
    }

    // Populate table with users
    function populateUserTable(users) {
        const tableBody = document.getElementById('users-table-body');
        if (!tableBody) {
            console.error('Table body element not found');
            return;
        }

        tableBody.innerHTML = '';

        if (users.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-info-circle mr-2"></i> No users found matching your criteria
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        users.forEach((user, index) => {
            const statusClass = getStatusClass(user.accountStatus);
            const row = document.createElement('tr');
            row.setAttribute('data-user-id', user.id);

            // Generate user initials from name
            const nameParts = user.name?.trim().split(' ') || ['U', 'ser'];
            const initials = nameParts.length > 1
                ? (nameParts[0][0] + nameParts[1][0]).toUpperCase()
                : (nameParts[0][0] + (nameParts[0][1] || '')).toUpperCase();

            // Get proper gender icon
            const genderIcon = getGenderIcon(user.gender);

            // Get status icon and class
            const statusIconClass = getStatusIconClass(user.accountStatus);

            row.innerHTML = `
                <td class="text-center">${user.id}</td>
                <td>
                    <div class="d-flex align-items-center">
                        ${user.profilePicture
                    ? `<img src="/images/${user.profilePicture}" alt="Avatar" class="user-avatar" onerror="this.src='/images/default-avatar.png'">`
                    : `<div class="avatar-initials role-${user.role}">${initials}</div>`
                }
                        <div class="user-info-container">
                            <span class="user-name">${user.name || 'Unknown'}</span>
                            <span class="gender-indicator">
                                <i class="${genderIcon.icon}" style="margin-right: 4px;"></i> ${user.gender || 'Not specified'}
                            </span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="contact-info">
                        <div class="contact-info-item">
                            <span class="user-detail-icon"><i class="fas fa-envelope"></i></span>
                            <span class="user-detail-value">${user.email}</span>
                        </div>
                        <div class="contact-info-item">
                            <span class="user-detail-icon"><i class="fas fa-phone-alt"></i></span>
                            <span class="user-detail-value">${user.phoneNumber || 'Not provided'}</span>
                        </div>
                        ${user.dateOfBirth ?
                    `<div class="contact-info-item">
                            <span class="user-detail-icon"><i class="fas fa-birthday-cake"></i></span>
                            <span class="user-detail-value">${user.dateOfBirth}</span>
                        </div>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge badge-role">${user.role || 'USER'}</span>
                </td>
                <td>
                    <span class="user-status-pill status-${(user.accountStatus || 'unknown').toLowerCase()}">
                        <i class="${statusIconClass.icon}"></i> ${user.accountStatus || 'UNKNOWN'}
                    </span>
                    ${user.isVerified !== undefined ?
                    (user.isVerified
                        ? '<div class="mt-1"><i class="fas fa-check-circle text-success"></i> <small>Verified</small></div>'
                        : '<div class="mt-1"><i class="fas fa-times-circle text-danger"></i> <small>Not Verified</small></div>')
                    : ''}
                </td>
                <td>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-icon btn-edit edit-user-btn" data-user-id="${user.id}" title="Edit User">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-delete delete-user-btn" data-user-id="${user.id}" data-user-name="${user.name || 'User'}" title="Delete User">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            `;

            tableBody.appendChild(row);
        });

        // Attach event listeners to buttons
        attachTableButtonListeners();
    }

    // Populate card view with users
    function populateUserCards(users) {
        const cardsContainer = document.getElementById('users-card-container');
        if (!cardsContainer) {
            console.error('Card container not found');
            return;
        }

        cardsContainer.innerHTML = '';

        if (users.length === 0) {
            cardsContainer.innerHTML = `
                <div class="col-12 py-5 text-center">
                    <div class="text-muted">
                        <i class="fas fa-info-circle mr-2"></i> No users found matching your criteria
                    </div>
                </div>
            `;
            return;
        }

        users.forEach(user => {
            const statusClass = getStatusClass(user.accountStatus);
            const cardCol = document.createElement('div');
            cardCol.className = 'col-xl-3 col-lg-4 col-md-6 mb-4';

            // Generate a gradient background based on user role
            const gradientClass = getGradientByRole(user.role);

            // Generate user initials from name
            const nameParts = user.name?.trim().split(' ') || ['U', 'ser'];
            const initials = nameParts.length > 1
                ? (nameParts[0][0] + nameParts[1][0]).toUpperCase()
                : (nameParts[0][0] + (nameParts[0][1] || '')).toUpperCase();

            // Get status icon and class
            const statusIconClass = getStatusIconClass(user.accountStatus);

            cardCol.innerHTML = `
                <div class="card user-card shadow-sm" data-user-id="${user.id}">
                    <div class="card-header ${gradientClass}">
                        <span class="badge ${statusClass} status-badge">
                            <i class="${statusIconClass.icon} mr-1"></i> ${user.accountStatus || 'UNKNOWN'}
                        </span>
                    </div>
                    
                    <div class="avatar-container">
                        ${user.profilePicture
                    ? `<img src="/images/${user.profilePicture}" alt="Avatar" class="avatar" onerror="this.src='/images/default-avatar.png'">`
                    : `<div class="avatar-initials role-${user.role}">${initials}</div>`
                }
                        ${user.isVerified ? '<span class="verified-badge" title="Verified Account"><i class="fas fa-check-circle"></i></span>' : ''}
                    </div>
                    
                    <div class="card-body">
                        <h5 class="user-name">${user.name || 'Unknown'}</h5>
                        
                        <div class="d-flex justify-content-center mb-3">
                            <span class="user-role badge badge-role">${user.role || 'USER'}</span>
                            ${user.isVerified !== undefined ?
                    (user.isVerified
                        ? '<span class="badge badge-success ml-2"><i class="fas fa-check-circle mr-1"></i>Verified</span>'
                        : '<span class="badge badge-secondary ml-2"><i class="fas fa-times-circle mr-1"></i>Not Verified</span>')
                    : ''
                }
                        </div>
                        
                        <div class="user-card-details">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="detail-content">
                                    <span class="detail-label">Email</span>
                                    <span class="detail-value text-truncate">${user.email}</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="detail-content">
                                    <span class="detail-label">Phone</span>
                                    <span class="detail-value">${user.phoneNumber || 'Not provided'}</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="${getGenderIcon(user.gender).icon}" style="color: ${getGenderIcon(user.gender).color}"></i>
                                </div>
                                <div class="detail-content">
                                    <span class="detail-label">Gender</span>
                                    <span class="detail-value">${user.gender || 'Not specified'}</span>
                                </div>
                            </div>
                            
                            ${user.dateOfBirth ? `
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div class="detail-content">
                                    <span class="detail-label">Date of Birth</span>
                                    <span class="detail-value">${user.dateOfBirth}</span>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="card-actions">
                        <button type="button" class="btn btn-rounded btn-sm btn-info edit-user-btn" data-user-id="${user.id}">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                        <button type="button" class="btn btn-rounded btn-sm btn-danger delete-user-btn" data-user-id="${user.id}" data-user-name="${user.name || 'User'}">
                            <i class="fas fa-trash-alt mr-1"></i> Delete
                        </button>
                    </div>
                </div>
            `;

            cardsContainer.appendChild(cardCol);
        });

        // Attach event listeners to buttons
        attachCardButtonListeners();
    }

    // Get card gradient by user role
    function getGradientByRole(role) {
        switch (role) {
            case 'ADMIN':
                return 'bg-gradient-primary';
            case 'USER':
                return 'bg-gradient-info';
            case 'DRIVER':
                return 'bg-gradient-success';
            default:
                return 'bg-gradient-secondary';
        }
    }

    // Helper functions for status classes
    function getStatusClass(status) {
        switch (status) {
            case 'ACTIVE':
                return 'badge-success';
            case 'SUSPENDED':
                return 'badge-warning';
            case 'BANNED':
                return 'badge-danger';
            default:
                return 'badge-secondary';
        }
    }

    // Helper function for status icons and classes
    function getStatusIconClass(status) {
        switch (status) {
            case 'ACTIVE':
                return {
                    icon: 'fas fa-check-circle',
                    class: 'status-active'
                };
            case 'SUSPENDED':
                return {
                    icon: 'fas fa-pause-circle',
                    class: 'status-suspended'
                };
            case 'BANNED':
                return {
                    icon: 'fas fa-ban',
                    class: 'status-banned'
                };
            default:
                return {
                    icon: 'fas fa-question-circle',
                    class: 'status-unknown'
                };
        }
    }

    // Helper function for gender icons
    function getGenderIcon(gender) {
        switch (gender) {
            case 'MALE':
                return {
                    icon: 'fas fa-mars',
                    color: '#007bff'
                };
            case 'FEMALE':
                return {
                    icon: 'fas fa-venus',
                    color: '#e83e8c'
                };
            case 'OTHER':
                return {
                    icon: 'fas fa-transgender',
                    color: '#6f42c1'
                };
            default:
                return {
                    icon: 'fas fa-user',
                    color: '#6c757d'
                };
        }
    }

    // Attach event listeners to table buttons
    function attachTableButtonListeners() {
        document.querySelectorAll('.users-table .edit-user-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const userId = this.getAttribute('data-user-id');
                openEditUserModal(userId);
            });
        });

        document.querySelectorAll('.users-table .delete-user-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                openDeleteConfirmationModal(userId, userName);
            });
        });
    }

    // Attach event listeners to card buttons
    function attachCardButtonListeners() {
        document.querySelectorAll('.user-card .edit-user-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const userId = this.getAttribute('data-user-id');
                openEditUserModal(userId);
            });
        });

        document.querySelectorAll('.user-card .delete-user-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                openDeleteConfirmationModal(userId, userName);
            });
        });
    }

    // Open edit user modal with user data
    function openEditUserModal(userId) {
        showLoading();

        // First get the user data
        fetch(userGetUrl.replace('USER_ID', userId))
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Error fetching user details');
                }
                return response.json();
            })
            .then(function (user) {
                // Then get the form with CSRF token
                return fetch(userEditUrl.replace('USER_ID', userId), {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                }).then(response => {
                    return Promise.all([user, response.text()]);
                });
            })
            .then(function ([user, html]) {
                // Update header info
                document.getElementById('edit-user-header-name').textContent = user.name || 'Unknown';
                document.getElementById('edit-user-header-email').textContent = user.email || 'No email';

                // Update avatar if available
                const avatarContainer = document.getElementById('edit-user-avatar-container');
                if (avatarContainer) {
                    const avatarImg = avatarContainer.querySelector('img');
                    if (avatarImg) {
                        avatarImg.src = user.profilePicture ? `/images/${user.profilePicture}` : '/images/default-avatar.png';
                    }
                }

                // Update user ID
                document.getElementById('edit-user-id').value = user.id;

                // Replace form content with the fetched form
                const editForm = document.getElementById('edit-user-form');
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const newForm = tempDiv.querySelector('form');

                if (newForm) {
                    editForm.innerHTML = newForm.innerHTML;

                    // Populate form fields from the user data
                    if (document.getElementById('edit-name')) document.getElementById('edit-name').value = user.name || '';
                    if (document.getElementById('edit-email')) document.getElementById('edit-email').value = user.email || '';
                    if (document.getElementById('edit-phone')) document.getElementById('edit-phone').value = user.phoneNumber || '';
                    if (document.getElementById('edit-role')) document.getElementById('edit-role').value = user.role || '';
                    if (document.getElementById('edit-account-status')) document.getElementById('edit-account-status').value = user.accountStatus || '';
                    if (document.getElementById('edit-gender')) document.getElementById('edit-gender').value = user.gender || '';
                    if (document.getElementById('edit-is-verified')) document.getElementById('edit-is-verified').checked = user.isVerified || false;
                    if (document.getElementById('edit-password')) document.getElementById('edit-password').value = ''; // Clear password field

                    // Stylize the account status select based on status
                    const statusSelect = document.getElementById('edit-account-status');
                    if (statusSelect) updateStatusSelectStyles(statusSelect);

                    // Re-initialize password visibility toggles
                    initPasswordToggle();
                }

                // Show modal
                editUserModal.show();
                hideLoading();
            })
            .catch(function (error) {
                console.error('Error:', error);
                hideLoading();
                showToast('error', 'Error loading user details: ' + error.message);
            });

        // Add event listener for save button
        document.getElementById('save-user-btn').onclick = function () {
            updateUser();
        };
    }

    // Update status select styles based on selected status
    function updateStatusSelectStyles(select) {
        const selectedOption = select.options[select.selectedIndex];
        const statusValue = selectedOption.value || '';

        // Remove all previous color classes
        select.classList.remove('border-success', 'border-warning', 'border-danger', 'text-success', 'text-warning', 'text-danger');

        // Add appropriate color classes based on selected status
        if (statusValue === 'ACTIVE') {
            select.classList.add('border-success', 'text-success');
        } else if (statusValue === 'SUSPENDED') {
            select.classList.add('border-warning', 'text-warning');
        } else if (statusValue === 'BANNED') {
            select.classList.add('border-danger', 'text-danger');
        }
    }

    // Add event listeners for status select dropdowns
    const editStatusSelect = document.getElementById('edit-account-status');
    const addStatusSelect = document.getElementById('add-account-status');

    if (editStatusSelect) {
        editStatusSelect.addEventListener('change', function () {
            updateStatusSelectStyles(this);
        });
    }

    if (addStatusSelect) {
        addStatusSelect.addEventListener('change', function () {
            updateStatusSelectStyles(this);
        });
    }

    // Update user data
    function updateUser() {
        const userId = document.getElementById('edit-user-id').value;
        const form = document.getElementById('edit-user-form');
        const formData = new FormData(form);

        // Convert FormData to JSON
        const jsonData = {};
        formData.forEach((value, key) => {
            jsonData[key] = value;
        });

        showLoading();

        fetch(userUpdateUrl.replace('USER_ID', userId), {
            method: 'POST',
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": formData.get('_token')
            },
            body: JSON.stringify(jsonData)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                hideLoading(); if (data.success) {
                    // Close modal
                    editUserModal.hide();

                    // Refresh user list
                    loadUsers();
                    showToast('success', data.message || 'User updated successfully');
                    showFloatingSuccess('User updated successfully');
                } else {
                    // Show validation errors
                    if (data.errors) {
                        displayFormErrors(form, data.errors);
                    } else {
                        showToast('error', data.message || 'An error occurred while updating the user');
                    }
                }
            })
            .catch(function (error) {
                hideLoading();
                console.error('Error:', error);
                showToast('error', 'Error updating user: ' + error.message);
            });
    }

    // Create new user
    function createUser() {
        const form = document.getElementById('add-user-form');
        const formData = new FormData(form);

        // Convert FormData to JSON
        const jsonData = {};
        formData.forEach((value, key) => {
            jsonData[key] = value;
        });

        showLoading();

        fetch(userCreateUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": formData.get('_token')
            },
            body: JSON.stringify(jsonData)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                hideLoading(); if (data.success) {
                    // Close modal
                    addUserModal.hide();

                    // Refresh user list
                    loadUsers();
                    showToast('success', data.message || 'User created successfully');
                    showFloatingSuccess('New user created successfully');
                } else {
                    // Show validation errors
                    if (data.errors) {
                        displayFormErrors(form, data.errors);
                    } else {
                        showToast('error', data.message || 'An error occurred while creating the user');
                    }
                }
            })
            .catch(function (error) {
                hideLoading();
                console.error('Error:', error);
                showToast('error', 'Error creating user: ' + error.message);
            });
    }

    // Open delete confirmation modal
    function openDeleteConfirmationModal(userId, userName) {
        // Fetch the delete form with CSRF token
        fetch(userDeleteUrl.replace('USER_ID', userId), {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
            .then(response => response.text())
            .then(html => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;

                // Update user info
                document.getElementById('delete-user-id').value = userId;
                document.getElementById('delete-user-name').textContent = userName;

                // Replace form content with the fetched form
                const deleteForm = document.getElementById('delete-user-form');
                const newForm = tempDiv.querySelector('form');
                if (newForm) {
                    deleteForm.innerHTML = newForm.innerHTML;
                }

                // Show modal
                deleteUserModal.show();
            })
            .catch(error => {
                console.error('Error loading delete form:', error);
                showToast('error', 'Error loading delete form: ' + error.message);
            });

        // Add event listener for confirm delete button
        document.getElementById('confirm-delete-btn').onclick = function () {
            deleteUser(userId);
        };
    }

    // Delete user
    function deleteUser(userId) {
        showLoading();

        const form = document.getElementById('delete-user-form');
        const formData = new FormData(form);

        fetch(userDeleteApiUrl.replace('USER_ID', userId), {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": formData.get('_token')
            }
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                hideLoading();

                if (data.success) {
                    // Close modal
                    deleteUserModal.hide();

                    // Refresh user list
                    loadUsers();
                    showToast('success', data.message || 'User deleted successfully');
                } else {
                    showToast('error', data.message || 'An error occurred while deleting the user');
                }
            })
            .catch(function (error) {
                hideLoading();
                console.error('Error:', error);
                showToast('error', 'Error deleting user: ' + error.message);
            });
    }

    // Helper functions for form validation
    function clearFormErrors(form) {
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(function (field) {
            field.classList.remove('is-invalid');
        });

        form.querySelectorAll('.invalid-feedback').forEach(function (feedback) {
            feedback.textContent = '';
        });
    }

    function displayFormErrors(form, errors) {
        clearFormErrors(form);

        if (typeof errors === 'string') {
            showToast('error', errors);
            return;
        }

        if (Array.isArray(errors)) {
            errors.forEach(function (error) {
                // Try to find the field by error path
                const parts = error.split(':');
                if (parts.length >= 2) {
                    const fieldName = parts[0]; // Assumes format "field: message"
                    const message = parts.slice(1).join(':').trim();

                    const field = form.querySelector(`[name="${fieldName}"]`);
                    if (field) {
                        field.classList.add('is-invalid');

                        // Add error message
                        let feedbackEl = field.nextElementSibling;
                        if (!feedbackEl || !feedbackEl.classList.contains('invalid-feedback')) {
                            feedbackEl = document.createElement('div');
                            feedbackEl.className = 'invalid-feedback';
                            field.parentNode.appendChild(feedbackEl);
                        }
                        feedbackEl.textContent = message;
                    }
                } else {
                    showToast('error', error);
                }
            });
        } else if (typeof errors === 'object') {
            // Handle object format errors
            for (const field in errors) {
                const message = errors[field];
                const fieldEl = form.querySelector(`[name="${field}"]`);
                if (fieldEl) {
                    fieldEl.classList.add('is-invalid');

                    let feedbackEl = fieldEl.nextElementSibling;
                    if (!feedbackEl || !feedbackEl.classList.contains('invalid-feedback')) {
                        feedbackEl = document.createElement('div');
                        feedbackEl.className = 'invalid-feedback';
                        fieldEl.parentNode.appendChild(feedbackEl);
                    }
                    feedbackEl.textContent = Array.isArray(message) ? message[0] : message;
                }
            }
        }
    }

    // Loading indicator functions
    function showLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('active');
        }
    }

    function hideLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    }

    // Show floating success animation
    function showFloatingSuccess(message) {
        // Create a floating success element
        const successEl = document.createElement('div');
        successEl.className = 'floating-success';
        successEl.innerHTML = `
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <div class="success-message">${message}</div>
        `;

        document.body.appendChild(successEl);

        // Remove after animation completes
        setTimeout(function () {
            if (document.body.contains(successEl)) {
                document.body.removeChild(successEl);
            }
        }, 2500);
    }

    // Toast notification function
    function showToast(type, message) {
        // Check if Toast notification library is available
        if (typeof toastr !== 'undefined') {
            // Configure toastr options
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right',
                timeOut: 3000,
                showMethod: 'fadeIn',
                hideMethod: 'fadeOut'
            };

            toastr[type](message);
        } else {
            // Create custom toast if toastr is not available
            const toast = document.createElement('div');
            toast.className = `custom-toast toast-${type}`;

            let iconClass = 'info-circle';
            if (type === 'success') iconClass = 'check-circle';
            if (type === 'error') iconClass = 'exclamation-circle';
            if (type === 'warning') iconClass = 'exclamation-triangle';

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${iconClass}"></i>
                </div>
                <div class="toast-message">${message}</div>
                <button class="toast-close">×</button>
            `;

            // Add toast container if not exists
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container';
                document.body.appendChild(toastContainer);

                // Add toast styles
                const style = document.createElement('style');
                style.innerHTML = `
                    .toast-container {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 9999;
                    }
                    
                    .custom-toast {
                        display: flex;
                        align-items: center;
                        background-color: white;
                        border-radius: 8px;
                        box-shadow: 0 3px 12px rgba(0,0,0,0.15);
                        margin-bottom: 10px;
                        max-width: 350px;
                        padding: 12px 15px;
                        position: relative;
                        border-left: 4px solid #ccc;
                        animation: toast-in 0.3s ease-out;
                    }
                    
                    .toast-success { border-left-color: #2E7D32; }
                    .toast-error { border-left-color: #d32f2f; }
                    .toast-warning { border-left-color: #f57f17; }
                    .toast-info { border-left-color: #1976d2; }
                    
                    .toast-icon {
                        margin-right: 12px;
                        font-size: 1.2rem;
                    }
                    
                    .toast-success .toast-icon { color: #2E7D32; }
                    .toast-error .toast-icon { color: #d32f2f; }
                    .toast-warning .toast-icon { color: #f57f17; }
                    .toast-info .toast-icon { color: #1976d2; }
                    
                    .toast-message {
                        flex: 1;
                        font-size: 14px;
                        color: #333;
                    }
                    
                    .toast-close {
                        background: none;
                        border: none;
                        color: #999;
                        cursor: pointer;
                        font-size: 18px;
                        line-height: 1;
                        padding: 0 5px;
                    }
                    
                    @keyframes toast-in {
                        0% { transform: translateX(100%); opacity: 0; }
                        100% { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }

            toastContainer.appendChild(toast);

            // Auto remove after 3 seconds
            setTimeout(function () {
                if (toastContainer.contains(toast)) {
                    toast.style.opacity = 0;
                    toast.style.transform = 'translateX(100%)';
                    toast.style.transition = 'opacity 0.3s, transform 0.3s';
                    setTimeout(function () {
                        if (toastContainer.contains(toast)) {
                            toastContainer.removeChild(toast);
                        }
                    }, 300);
                }
            }, 3000);

            // Close button functionality
            toast.querySelector('.toast-close').addEventListener('click', function () {
                if (toastContainer.contains(toast)) {
                    toastContainer.removeChild(toast);
                }
            });
        }
    }

    // Load users when page is loaded
    loadUsers();
});
