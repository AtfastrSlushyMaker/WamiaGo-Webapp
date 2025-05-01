/**
 * Enhanced User Management JavaScript
 * Handles all user management functionality including:
 * - List/Card view toggling
 * - User data loading
 * - User creation, editing and deletion
 * - Form validation
 * - UI interactions
 */

'use strict';

// Configuration
const DEBUG = true;

// State variables
let currentPage = 1;
let currentUserData = [];
const currentFilters = {
    search: '',
    role: '',
    status: '',
    verified: '',
    itemsPerPage: 10
};

// DOM Elements
const elements = {
    searchInput: document.querySelector('#user-search'),
    clearSearchBtn: document.querySelector('#clear-search'),
    roleFilter: document.querySelector('#filter-role'),
    statusFilter: document.querySelector('#filter-status'),
    verifiedFilter: document.querySelector('#filter-verified'),
    itemsPerPage: document.querySelector('#page-size'),
    usersTableBody: document.querySelector('#users-table-body'),
    usersCards: document.querySelector('#users-cards'),
    paginationContainers: document.querySelectorAll('.pagination-container'),
    paginationNav: document.querySelector('.pagination'),
    gotoPageSelect: document.querySelector('#goto-page'),
    loadingOverlay: document.querySelector('#loading-overlay'),
    showingResults: document.querySelector('#showing-results'),
    totalResults: document.querySelector('#total-results'),
    listViewBtn: document.querySelector('#list-view-btn'),
    cardViewBtn: document.querySelector('#card-view-btn'),
    listView: document.querySelector('#list-view'),
    cardView: document.querySelector('#card-view'),
    addUserBtn: document.querySelector('#add-user-btn')
};

// Forms
const forms = {
    add: document.querySelector('#addUserForm'),
    edit: document.querySelector('#editUserForm')
};

// Modals
const modals = {
    add: document.querySelector('#addUserModal') ? new bootstrap.Modal(document.querySelector('#addUserModal')) : null,
    edit: document.querySelector('#edit-user-modal') ? new bootstrap.Modal(document.querySelector('#edit-user-modal')) : null,
    delete: document.querySelector('#delete-user-modal') ? new bootstrap.Modal(document.querySelector('#delete-user-modal')) : null
};

// API Routes
const API_ROUTES = {
    get: '/admin/users/api',
    add: '/admin/users/add',
    edit: '/admin/users/edit',
    update: '/admin/users/update',
    delete: '/admin/users/delete',
    deleteApi: '/admin/users/delete/api'
};

// Debug logging function
function debug(...args) {
    if (DEBUG) {
        console.log('[User Management]', ...args);
    }
}

// Debounce function to limit how often a function can be called
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.addEventListener('DOMContentLoaded', function() {
    // Validate required elements
    if (!elements.usersTableBody) {
        console.error('Required DOM element not found. User management functionality may be limited.');
    }

    // Initialize event listeners
    initializeEventListeners();
    
    // Restore view preference
    restoreViewPreference();
    
    // Load initial data
    loadUsers(currentPage);
});

function initializeEventListeners() {
    // Pagination
    if (elements.itemsPerPage) {
        elements.itemsPerPage.addEventListener('change', handleItemsPerPageChange);
    }
    
    if (elements.gotoPageSelect) {
        elements.gotoPageSelect.addEventListener('change', (e) => {
            const selectedPage = parseInt(e.target.value);
            if (!isNaN(selectedPage)) {
                loadUsers(selectedPage);
            }
        });
    }

    // Search
    if (elements.searchInput) {
        elements.searchInput.addEventListener('input', debounce(handleSearch, 400));
    }
    
    if (elements.clearSearchBtn) {
        elements.clearSearchBtn.addEventListener('click', () => {
            elements.searchInput.value = '';
            currentFilters.search = '';
            elements.clearSearchBtn.style.display = 'none';
            loadUsers(1);
        });
    }

    // Filters
    if (elements.roleFilter) {
        elements.roleFilter.addEventListener('change', handleFilterChange);
    }
    if (elements.statusFilter) {
        elements.statusFilter.addEventListener('change', handleFilterChange);
    }
    if (elements.verifiedFilter) {
        elements.verifiedFilter.addEventListener('change', handleFilterChange);
    }

    // Forms
    if (forms.add) {
        forms.add.addEventListener('submit', handleAddUserSubmit);
    }
    if (forms.edit) {
        forms.edit.addEventListener('submit', handleEditUserSubmit);
    }

    // Delete user confirmation
    const confirmDeleteBtn = document.querySelector('#confirm-delete-btn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', handleDeleteUser);
    }

    // View toggling
    if (elements.listViewBtn && elements.cardViewBtn) {
        elements.listViewBtn.addEventListener('click', () => toggleView('list'));
        elements.cardViewBtn.addEventListener('click', () => toggleView('card'));
    }
}

function handleSearch() {
    currentFilters.search = elements.searchInput.value;
    if (elements.clearSearchBtn) {
        elements.clearSearchBtn.style.display = elements.searchInput.value ? 'block' : 'none';
    }
    loadUsers(1);
}

function handleFilterChange() {
    if (elements.roleFilter) {
        currentFilters.role = elements.roleFilter.value;
    }
    if (elements.statusFilter) {
        currentFilters.status = elements.statusFilter.value;
    }
    if (elements.verifiedFilter) {
        currentFilters.verified = elements.verifiedFilter.value;
    }
    loadUsers(1);
}

function handleItemsPerPageChange() {
    currentFilters.itemsPerPage = parseInt(elements.itemsPerPage.value);
    loadUsers(1);
}

function toggleView(view) {
    if (!elements.listView || !elements.cardView) return;
    
    if (view === 'list') {
        elements.listView.style.display = 'block';
        elements.cardView.style.display = 'none';
        elements.listViewBtn.classList.add('active');
        elements.cardViewBtn.classList.remove('active');
        localStorage.setItem('userViewPreference', 'list');
    } else {
        elements.listView.style.display = 'none';
        elements.cardView.style.display = 'block';
        elements.listViewBtn.classList.remove('active');
        elements.cardViewBtn.classList.add('active');
        localStorage.setItem('userViewPreference', 'card');
    }
}

// Restore user's view preference
function restoreViewPreference() {
    if (!elements.listView || !elements.cardView) return;
    
    const preference = localStorage.getItem('userViewPreference');
    if (preference === 'card') {
        toggleView('card');
    } else {
        toggleView('list');
    }
}

async function loadUsers(page) {
    currentPage = page || 1;
    showLoading(true);
    
    try {
        // Build query params
        const params = new URLSearchParams({
            page: currentPage,
            items: currentFilters.itemsPerPage,
        });
        
        if (currentFilters.search) {
            params.append('search', currentFilters.search);
        }
        
        if (currentFilters.role) {
            params.append('role', currentFilters.role);
        }
        
        if (currentFilters.status) {
            params.append('status', currentFilters.status);
        }
        
        if (currentFilters.verified) {
            params.append('verified', currentFilters.verified);
        }
          // Check if we're in debug mode
        if (DEBUG) {
            console.log('Loading users with params:', Object.fromEntries(params.entries()));
        }
        
        // Use our debug URL during development if there are issues
        const apiUrl = `${API_ROUTES.get}?${params.toString()}`;
        // Uncomment the next line to use the debug API
        // const apiUrl = '/debug-user-api.php';
        
        const response = await fetch(apiUrl);
        
        if (!response.ok) {
            if (DEBUG) {
                console.error(`HTTP error! Status: ${response.status}`, response);
            }
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Debug mode: log the response data
        if (DEBUG) {
            console.log('API Response:', data);
        }
        
        // Update our current data cache
        currentUserData = data.users;
        
        // Update pagination info
        updatePaginationInfo(data);
        
        // Render users
        renderUsers(data.users);
        
        // Update showing results count
        updateResultsCount(data);
        
    } catch (error) {
        console.error('Error loading users:', error);
        showErrorMessage('Failed to load users. Please try again later.');
    } finally {
        showLoading(false);
    }
}

function renderUsers(users) {
    // Render list view
    renderUserTable(users);
    
    // Render card view
    renderUserCards(users);
}

function renderUserTable(users) {
    if (!elements.usersTableBody) return;
    
    elements.usersTableBody.innerHTML = '';
    
    if (users.length === 0) {
        const noDataRow = document.createElement('tr');
        noDataRow.innerHTML = `
            <td colspan="6" class="text-center py-4">
                <div class="py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No users found</h5>
                    <p class="text-muted">Try changing your search criteria</p>
                </div>
            </td>
        `;
        elements.usersTableBody.appendChild(noDataRow);
        return;
    }
    
    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Status badge colors
        let statusBadgeClass = 'bg-success';
        if (user.account_status === 'SUSPENDED') {
            statusBadgeClass = 'bg-warning';
        } else if (user.account_status === 'BANNED') {
            statusBadgeClass = 'bg-danger';
        }
        
        // Verification badge
        const verifiedBadge = user.is_verified ? 
            '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Verified</span>' : 
            '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i> Unverified</span>';
        
        // Default avatar if no profile picture
        const profilePic = user.profile_picture || '/images/default-avatar.png';
        
        row.innerHTML = `
            <td>${user.id_user}</td>
            <td>
                <div class="d-flex align-items-center">
                    <img src="${profilePic}" alt="${user.name}" class="user-avatar-sm me-3">
                    <div>
                        <h6 class="mb-0">${user.name}</h6>
                        <small class="text-muted">${user.email}</small>
                    </div>
                </div>
            </td>
            <td>
                <div><i class="fas fa-phone-alt text-primary me-2"></i>${user.phone_number}</div>
            </td>
            <td>
                <div class="mb-1">
                    <span class="badge ${user.role === 'ADMIN' ? 'bg-primary' : 'bg-info'}">
                        ${user.role}
                    </span>
                </div>
                <div>
                    <span class="badge ${statusBadgeClass}">
                        ${user.account_status}
                    </span>
                </div>
            </td>
            <td>
                ${verifiedBadge}
            </td>
            <td>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary view-user-btn view-user" data-id="${user.id_user}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary edit-user" data-id="${user.id_user}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-user" data-id="${user.id_user}" data-name="${user.name}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        
        elements.usersTableBody.appendChild(row);
    });
    
    // Add event listeners to buttons
    addTableActionListeners();
}

function renderUserCards(users) {
    if (!elements.usersCards) return;
    
    elements.usersCards.innerHTML = '';
    
    if (users.length === 0) {
        const noData = document.createElement('div');
        noData.className = 'col-12 text-center py-5';
        noData.innerHTML = `
            <div class="py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5>No users found</h5>
                <p class="text-muted">Try changing your search criteria</p>
            </div>
        `;
        elements.usersCards.appendChild(noData);
        return;
    }
    
    users.forEach(user => {
        const col = document.createElement('div');
        col.className = 'col-xl-3 col-lg-4 col-md-6 col-sm-12';
        
        // Status badge colors
        let statusBadgeClass = 'bg-success';
        if (user.account_status === 'SUSPENDED') {
            statusBadgeClass = 'bg-warning';
        } else if (user.account_status === 'BANNED') {
            statusBadgeClass = 'bg-danger';
        }
        
        // Default avatar if no profile picture
        const profilePic = user.profile_picture || '/images/default-avatar.png';
        
        col.innerHTML = `
            <div class="user-card">
                <div class="card-header">
                    <span class="badge ${user.role === 'ADMIN' ? 'bg-light text-primary' : 'bg-light text-info'} float-end">
                        ${user.role}
                    </span>
                    <span class="badge ${statusBadgeClass} float-start">
                        ${user.account_status}
                    </span>
                </div>
                <img src="${profilePic}" alt="${user.name}" class="user-avatar">
                <div class="card-body">
                    <h5 class="card-title">${user.name}</h5>
                    <p class="card-subtitle mb-2">
                        ${user.is_verified ? 
                            '<i class="fas fa-check-circle text-success"></i> Verified' : 
                            '<i class="fas fa-times-circle text-secondary"></i> Unverified'
                        }
                    </p>
                    
                    <ul class="user-info">
                        <li><i class="fas fa-envelope"></i> ${user.email}</li>
                        <li><i class="fas fa-phone"></i> ${user.phone_number}</li>
                        ${user.date_of_birth ? `<li><i class="fas fa-birthday-cake"></i> ${user.date_of_birth}</li>` : ''}
                    </ul>
                </div>
                <div class="card-footer">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary view-user-btn view-user" data-id="${user.id_user}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary edit-user" data-id="${user.id_user}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-user" data-id="${user.id_user}" data-name="${user.name}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        elements.usersCards.appendChild(col);
    });
    
    // Add event listeners to buttons
    addCardActionListeners();
}

function addTableActionListeners() {
    // Edit buttons
    document.querySelectorAll('#users-table-body .edit-user').forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.dataset.id;
            editUser(userId);
        });
    });
    
    // Delete buttons
    document.querySelectorAll('#users-table-body .delete-user').forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.dataset.id;
            const userName = button.dataset.name;
            confirmDeleteUser(userId, userName);
        });
    });
    
    // View buttons
    document.querySelectorAll('#users-table-body .view-user').forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.dataset.id;
            viewUserDetails(userId);
        });
    });
}

function addCardActionListeners() {
    // Edit buttons
    document.querySelectorAll('#users-cards .edit-user').forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.dataset.id;
            editUser(userId);
        });
    });
    
    // Delete buttons
    document.querySelectorAll('#users-cards .delete-user').forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.dataset.id;
            const userName = button.dataset.name;
            confirmDeleteUser(userId, userName);
        });
    });
    
    // View buttons
    document.querySelectorAll('#users-cards .view-user').forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.dataset.id;
            viewUserDetails(userId);
        });
    });
}

function updatePaginationInfo(data) {
    const totalPages = data.pages || 1;
    const currentPage = data.page || 1;
    
    // Update the goto page dropdown
    if (elements.gotoPageSelect) {
        elements.gotoPageSelect.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `Page ${i}`;
            if (i === currentPage) {
                option.selected = true;
            }
            elements.gotoPageSelect.appendChild(option);
        }
    }
    
    // Update the pagination
    if (elements.paginationNav) {
        elements.paginationNav.innerHTML = '';
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `
            <a class="page-link" href="#" aria-label="Previous" ${currentPage !== 1 ? `onclick="loadUsers(${currentPage - 1}); return false;"` : ''}>
                <span aria-hidden="true">&laquo;</span>
            </a>
        `;
        elements.paginationNav.appendChild(prevLi);
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            // First page
            const firstPageLi = document.createElement('li');
            firstPageLi.className = 'page-item';
            firstPageLi.innerHTML = `
                <a class="page-link" href="#" onclick="loadUsers(1); return false;">1</a>
            `;
            elements.paginationNav.appendChild(firstPageLi);
            
            // Ellipsis if needed
            if (startPage > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = `
                    <a class="page-link" href="#">...</a>
                `;
                elements.paginationNav.appendChild(ellipsisLi);
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
            pageLi.innerHTML = `
                <a class="page-link" href="#" onclick="loadUsers(${i}); return false;">${i}</a>
            `;
            elements.paginationNav.appendChild(pageLi);
        }
        
        if (endPage < totalPages) {
            // Ellipsis if needed
            if (endPage < totalPages - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = `
                    <a class="page-link" href="#">...</a>
                `;
                elements.paginationNav.appendChild(ellipsisLi);
            }
            
            // Last page
            const lastPageLi = document.createElement('li');
            lastPageLi.className = 'page-item';
            lastPageLi.innerHTML = `
                <a class="page-link" href="#" onclick="loadUsers(${totalPages}); return false;">${totalPages}</a>
            `;
            elements.paginationNav.appendChild(lastPageLi);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `
            <a class="page-link" href="#" aria-label="Next" ${currentPage !== totalPages ? `onclick="loadUsers(${currentPage + 1}); return false;"` : ''}>
                <span aria-hidden="true">&raquo;</span>
            </a>
        `;
        elements.paginationNav.appendChild(nextLi);
    }
}

function updateResultsCount(data) {
    if (elements.showingResults && elements.totalResults) {
        const start = (data.page - 1) * (data.itemsPerPage || 10) + 1;
        const end = Math.min(start + data.users.length - 1, data.total);
        elements.showingResults.textContent = data.total > 0 ? `${start}-${end}` : '0';
        elements.totalResults.textContent = data.total;
    }
}

function showLoading(isLoading) {
    if (elements.loadingOverlay) {
        elements.loadingOverlay.style.display = isLoading ? 'flex' : 'none';
    }
}

function showErrorMessage(message) {
    // You could implement a toast or notification system here
    console.error(message);
    alert(message);
}

function editUser(userId) {
    // Find the user in our current data
    const user = currentUserData.find(u => u.id_user == userId);
    
    if (!user) {
        console.error('User not found:', userId);
        return;
    }
    
    // Populate the form
    document.querySelector('#edit-id').value = user.id_user;
    document.querySelector('#edit-name').value = user.name;
    document.querySelector('#edit-email').value = user.email;
    document.querySelector('#edit-phone').value = user.phone_number;
    document.querySelector('#edit-role').value = user.role;
    
    if (document.querySelector('#edit-gender')) {
        document.querySelector('#edit-gender').value = user.gender;
    }
    
    document.querySelector('#edit-status').value = user.account_status;
    
    // Date needs formatting
    if (user.date_of_birth && document.querySelector('#edit-dob')) {
        document.querySelector('#edit-dob').value = user.date_of_birth;
    } else if (document.querySelector('#edit-dob')) {
        document.querySelector('#edit-dob').value = '';
    }
    
    // Profile picture
    if (user.profile_picture && document.querySelector('#edit-profile-pic')) {
        document.querySelector('#edit-profile-pic').value = user.profile_picture;
    } else if (document.querySelector('#edit-profile-pic')) {
        document.querySelector('#edit-profile-pic').value = '';
    }
    
    // Checkbox
    if (document.querySelector('#edit-verified')) {
        document.querySelector('#edit-verified').checked = user.is_verified;
    }
    
    // Show the modal
    modals.edit.show();
}

function confirmDeleteUser(userId, userName) {
    // Set the user name in the confirmation message
    document.querySelector('#delete-user-name').textContent = userName;
    
    // Store the user ID in a data attribute for the confirm button
    document.querySelector('#confirm-delete-btn').dataset.userId = userId;
    
    // Show the modal
    modals.delete.show();
}

function viewUserDetails(userId) {
    // Find the user in our current data
    const user = currentUserData.find(u => u.id_user == userId);
    
    if (!user) {
        console.error('User not found:', userId);
        return;
    }
    
    // Use the event delegation approach from view-user-modal.js
    // We don't need to do anything here as the view-user-modal.js handles this
    // via event delegation on the view-user-btn and view-user classes
}

async function handleAddUserSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    // Disable the button and show spinner
    if (submitBtn) submitBtn.disabled = true;
    if (spinner) spinner.classList.remove('d-none');
    
    try {
        const response = await fetch(API_ROUTES.add, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Success - close modal and reload users
            if (modals.add) modals.add.hide();
            form.reset();
            loadUsers(currentPage);
            showSuccessMessage('User added successfully');
        } else {
            // Error - show error message
            showFormErrors(form, result.errors || { general: result.message || 'Error adding user' });
        }
    } catch (error) {
        console.error('Error adding user:', error);
        showErrorMessage('Failed to add user. Please try again later.');
    } finally {
        // Re-enable the button and hide spinner
        if (submitBtn) submitBtn.disabled = false;
        if (spinner) spinner.classList.add('d-none');
    }
}

async function handleEditUserSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    // Disable the button and show spinner
    if (submitBtn) submitBtn.disabled = true;
    if (spinner) spinner.classList.remove('d-none');
    
    try {
        const response = await fetch(API_ROUTES.update, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Success - close modal and reload users
            if (modals.edit) modals.edit.hide();
            form.reset();
            loadUsers(currentPage);
            showSuccessMessage('User updated successfully');
        } else {
            // Error - show error message
            showFormErrors(form, result.errors || { general: result.message || 'Error updating user' });
        }
    } catch (error) {
        console.error('Error updating user:', error);
        showErrorMessage('Failed to update user. Please try again later.');
    } finally {
        // Re-enable the button and hide spinner
        if (submitBtn) submitBtn.disabled = false;
        if (spinner) spinner.classList.add('d-none');
    }
}

async function handleDeleteUser() {
    const button = document.querySelector('#confirm-delete-btn');
    const userId = button.dataset.userId;
    const spinner = button.querySelector('.spinner-border');
    
    // Disable the button and show spinner
    if (button) button.disabled = true;
    if (spinner) spinner.classList.remove('d-none');
    
    try {
        const response = await fetch(`${API_ROUTES.deleteApi}/${userId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Success - close modal and reload users
            if (modals.delete) modals.delete.hide();
            loadUsers(currentPage);
            showSuccessMessage('User deleted successfully');
        } else {
            // Error - show error message
            showErrorMessage(result.message || 'Failed to delete user');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showErrorMessage('Failed to delete user. Please try again later.');
    } finally {
        // Re-enable the button and hide spinner
        if (button) button.disabled = false;
        if (spinner) spinner.classList.add('d-none');
    }
}

function showFormErrors(form, errors) {
    // Reset previous error states
    form.querySelectorAll('.is-invalid').forEach(field => {
        field.classList.remove('is-invalid');
    });
    
    form.querySelectorAll('.invalid-feedback').forEach(feedback => {
        feedback.textContent = '';
    });
    
    // Add new error states
    for (const field in errors) {
        const input = form.querySelector(`[name="${field}"]`);
        const feedback = form.querySelector(`#${form.id.replace('Form', '')}-${field}-error`);
        
        if (input) {
            input.classList.add('is-invalid');
        }
        
        if (feedback) {
            feedback.textContent = errors[field];
        } else if (field === 'general') {
            // Show general error
            showErrorMessage(errors[field]);
        }
    }
}

function showSuccessMessage(message) {
    // You could implement a toast or notification system here
    console.log(message);
    alert(message);
}

// Expose necessary functions to global scope for inline event handlers
window.loadUsers = loadUsers;

// Handle missing or null values safely
function getPropertySafely(obj, propName, defaultValue = '') {
    if (!obj || obj[propName] === undefined || obj[propName] === null) {
        return defaultValue;
    }
    return obj[propName];
}
