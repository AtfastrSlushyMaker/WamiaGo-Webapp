/**
 * User Management JavaScript
 * Handles all user management functionality including:
 * - List/Card view toggling
 * - User data loading
 * - User creation, editing and deletion
 * - Form validation
 * - UI interactionsUncaught SyntaxError: Unexpected token ')'
 */

'use strict';

// Configuration
const DEBUG = true;

// State variables
let currentPage = 1;
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
    roleFilter: document.querySelector('#filter-role'),
    statusFilter: document.querySelector('#filter-status'),
    verifiedFilter: document.querySelector('#filter-verified'),
    itemsPerPage: document.querySelector('#page-size'),
    userTable: document.querySelector('#users-table-body'),
    paginationContainers: document.querySelectorAll('.pagination-container'),
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
    add: document.querySelector('#add-user-modal') ? new bootstrap.Modal(document.querySelector('#add-user-modal')) : null,
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

// Validate required elements
if (!elements.userTable) {
    console.error('Required DOM element not found. User management functionality may be limited.');
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize event listeners
    initializeEventListeners();
    
    // Load initial data
    loadUsers(currentPage);
});

function initializeEventListeners() {
    // Pagination
    if (elements.itemsPerPage) {
        elements.itemsPerPage.addEventListener('change', handleItemsPerPageChange);
    }

    // Search
    if (elements.searchInput) {
        elements.searchInput.addEventListener('input', debounce(function() {
            currentFilters.search = elements.searchInput.value;
            addClearSearchButton();
            loadUsers(1);
        }, 200));
        addClearSearchButton();
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

    // View toggling
    if (elements.listViewBtn && elements.cardViewBtn && elements.listView && elements.cardView) {
        elements.listViewBtn.addEventListener('click', () => toggleView('list'));
        elements.cardViewBtn.addEventListener('click', () => toggleView('card'));
    }
}

function handleSearch() {
    currentFilters.search = elements.searchInput.value;
    loadUsers(1);
}

function handleFilterChange() {
    currentFilters.role = elements.roleFilter.value;
    currentFilters.status = elements.statusFilter.value;
    currentFilters.verified = elements.verifiedFilter.value;
    loadUsers(1);
}

function handleItemsPerPageChange() {
    currentFilters.itemsPerPage = parseInt(elements.itemsPerPage.value);
    loadUsers(1);
}

function toggleView(view) {
    if (view === 'list') {
        elements.listView.style.display = 'block';
        elements.cardView.style.display = 'none';
        elements.listViewBtn.classList.add('active');
        elements.cardViewBtn.classList.remove('active');
    } else {
        elements.listView.style.display = 'none';
        elements.cardView.style.display = 'block';
        elements.listViewBtn.classList.remove('active');
        elements.cardViewBtn.classList.add('active');
    }
}

function loadUsers(page = 1) {
    currentPage = page;
    showLoading();

    const params = new URLSearchParams({
        page: currentPage,
        limit: currentFilters.itemsPerPage,
        search: currentFilters.search,
        role: currentFilters.role,
        status: currentFilters.status,
        verified: currentFilters.verified
    });

    fetch(`${API_ROUTES.get}?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const isSearching = currentFilters.search && currentFilters.search.trim().length > 0;
            let users = Array.isArray(data.users) ? data.users : [];
            // If searching, filter users on the frontend as a workaround
            if (isSearching) {
                const searchTerm = currentFilters.search.trim().toLowerCase();
                users = users.filter(user =>
                    (user.name && user.name.toLowerCase().includes(searchTerm)) ||
                    (user.email && user.email.toLowerCase().includes(searchTerm)) ||
                    (user.phoneNumber && user.phoneNumber.toLowerCase().includes(searchTerm))
                );
            }

            // Clear table/cards before rendering
            elements.userTable.innerHTML = '';
            if (elements.cardView) {
                const cardContainer = elements.cardView.querySelector('.row');
                if (cardContainer) cardContainer.innerHTML = '';
            }

            // Show users or "No results found"
            if (users.length === 0) {
                const msg = isSearching ? "No results found" : "No users found";
                elements.userTable.innerHTML = `<tr><td colspan="8" class="text-center">${msg}</td></tr>`;
                if (elements.cardView) {
                    const cardContainer = elements.cardView.querySelector('.row');
                    if (cardContainer) cardContainer.innerHTML = `<div class="col-12 text-center">${msg}</div>`;
                }
            } else {
                populateUserTable(users);
                populateUserCards(users);
            }

            // Update counts
            if (elements.showingResults) elements.showingResults.textContent = users.length;
            if (elements.totalResults) elements.totalResults.textContent = isSearching ? users.length : (data.total || users.length);

            // Pagination: only show when not searching
            elements.paginationContainers.forEach(container => {
                container.style.display = isSearching ? 'none' : '';
            });
            if (!isSearching && users.length > 0) {
                updatePagination(data.totalPages || data.pages || 1, currentPage);
            }

            hideLoading();
        })
        .catch(error => {
            console.error('Error loading users:', error);
            showError('Failed to load users. Please try again.');
            hideLoading();
        });
}

function showLoading() {
    if (elements.loadingOverlay) {
        elements.loadingOverlay.style.display = 'flex';
    }
}

function hideLoading() {
    if (elements.loadingOverlay) {
        elements.loadingOverlay.style.display = 'none';
    }
}

function showError(message) {
    // You can implement this based on your UI requirements
    alert(message);
}

// Debounce utility
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Highlight utility
function highlight(text, term) {
    if (!term) return text;
    const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}

function populateUserTable(users) {
    if (!elements.userTable) return;
    elements.userTable.innerHTML = '';
    if (!users || users.length === 0) {
        elements.userTable.innerHTML = '<tr><td colspan="8" class="text-center">No users found</td></tr>';
        return;
    }
    const searchTerm = currentFilters.search;
    users.forEach((user, idx) => {
        elements.userTable.innerHTML += `
            <tr>
                <td class="text-center">${idx + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="/images/${user.profilePicture || 'default-avatar.png'}" alt="Avatar" class="rounded-circle mr-2 border border-primary" width="36" height="36">
                        <div>
                            <div class="font-weight-bold">${highlight(user.name || '', searchTerm)}</div>
                            <small class="text-muted">${highlight(user.email || '', searchTerm)}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div><i class="fas fa-phone-alt mr-1 text-info"></i>${highlight(user.phoneNumber || '', searchTerm)}</div>
                    <div><i class="fas fa-venus-mars mr-1 text-secondary"></i>${user.gender || ''}</div>
                </td>
                <td><span class="badge badge-pill bg-info text-uppercase">${user.role || ''}</span></td>
                <td><span class="badge badge-pill ${user.accountStatus === 'ACTIVE' ? 'bg-success' : user.accountStatus === 'SUSPENDED' ? 'bg-warning' : user.accountStatus === 'BANNED' ? 'bg-danger' : 'bg-secondary'}">${user.accountStatus || ''}</span></td>
                <td>
                    ${user.isVerified ? '<span class="badge badge-pill bg-success"><i class="fas fa-check-circle"></i> Verified</span>' : '<span class="badge badge-pill bg-secondary"><i class="fas fa-times-circle"></i> Not Verified</span>'}
                </td>
                <td>${user.dateOfBirth ? `<i class='fas fa-birthday-cake mr-1 text-warning'></i>${user.dateOfBirth}` : ''}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary edit-user mx-1" data-user-id="${user.id}" title="Edit User">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-user mx-1" data-user-id="${user.id}" title="Delete User">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

function populateUserCards(users) {
    if (!elements.cardView) return;
    const cardContainer = elements.cardView.querySelector('.row');
    if (!cardContainer) return;
    cardContainer.innerHTML = '';
    users.forEach(user => {
        const card = document.createElement('div');
        card.className = 'col-md-4 col-lg-3 mb-4';
        card.innerHTML = `
            <div class="card h-100 shadow-sm border-primary border-2">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="/images/${user.profilePicture || 'default-avatar.png'}" alt="Avatar" class="rounded-circle mr-3 border border-primary" width="56" height="56">
                        <div>
                            <h5 class="card-title mb-0 font-weight-bold">${user.name || ''}</h5>
                            <small class="text-muted">${user.email || ''}</small>
                        </div>
                    </div>
                    <div class="mb-2"><i class="fas fa-phone-alt mr-2 text-info"></i> ${user.phoneNumber || ''}</div>
                    <div class="mb-2"><i class="fas fa-venus-mars mr-2 text-secondary"></i> ${user.gender || ''}</div>
                    <div class="mb-2"><span class="badge badge-pill bg-info text-uppercase">${user.role || ''}</span></div>
                    <div class="mb-2"><span class="badge badge-pill ${user.accountStatus === 'ACTIVE' ? 'bg-success' : user.accountStatus === 'SUSPENDED' ? 'bg-warning' : user.accountStatus === 'BANNED' ? 'bg-danger' : 'bg-secondary'}">${user.accountStatus || ''}</span></div>
                    <div class="mb-2">
                        ${user.isVerified ? '<span class="badge badge-pill bg-success"><i class="fas fa-check-circle"></i> Verified</span>' : '<span class="badge badge-pill bg-secondary"><i class="fas fa-times-circle"></i> Not Verified</span>'}
                    </div>
                    <div class="mb-2">${user.dateOfBirth ? `<i class='fas fa-birthday-cake mr-2 text-warning'></i>${user.dateOfBirth}` : ''}</div>
                </div>
                <div class="card-footer d-flex justify-content-end bg-light border-top-0">
                    <button class="btn btn-sm btn-outline-primary edit-user mx-1" data-user-id="${user.id}" title="Edit User">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-user mx-1" data-user-id="${user.id}" title="Delete User">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;
        cardContainer.appendChild(card);
    });
}

function updatePagination(totalPages, currentPage) {
    elements.paginationContainers.forEach(container => {
        container.innerHTML = '';
        if (totalPages <= 1) return;

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item${currentPage === 1 ? ' disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}">&laquo; Prev</a>`;
        container.appendChild(prevLi);

        // Page numbers with ellipsis
        let start = Math.max(1, currentPage - 2);
        let end = Math.min(totalPages, currentPage + 2);

        if (start > 1) {
            const firstLi = document.createElement('li');
            firstLi.className = 'page-item';
            firstLi.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
            container.appendChild(firstLi);
            if (start > 2) {
                const ellipsis = document.createElement('li');
                ellipsis.className = 'page-item disabled';
                ellipsis.innerHTML = `<span class="page-link">...</span>`;
                container.appendChild(ellipsis);
            }
        }

        for (let i = start; i <= end; i++) {
            const li = document.createElement('li');
            li.className = `page-item${i === currentPage ? ' active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            container.appendChild(li);
        }

        if (end < totalPages) {
            if (end < totalPages - 1) {
                const ellipsis = document.createElement('li');
                ellipsis.className = 'page-item disabled';
                ellipsis.innerHTML = `<span class="page-link">...</span>`;
                container.appendChild(ellipsis);
            }
            const lastLi = document.createElement('li');
            lastLi.className = 'page-item';
            lastLi.innerHTML = `<a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>`;
            container.appendChild(lastLi);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item${currentPage === totalPages ? ' disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}">Next &raquo;</a>`;
        container.appendChild(nextLi);

        // Add click handlers
        container.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page);
                if (!isNaN(page) && page > 0 && page <= totalPages && page !== currentPage) {
                    loadUsers(page);
                }
            });
        });
    });
}

// Add a clear search button dynamically
function addClearSearchButton() {
    let clearBtn = document.getElementById('clear-search-btn');
    if (!clearBtn && elements.searchInput) {
        clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.id = 'clear-search-btn';
        clearBtn.className = 'btn btn-outline-secondary btn-sm position-absolute';
        clearBtn.style.right = '10px';
        clearBtn.style.top = '50%';
        clearBtn.style.transform = 'translateY(-50%)';
        clearBtn.innerHTML = '<i class="fas fa-times"></i>';
        clearBtn.title = 'Clear search';
        clearBtn.onclick = function() {
            elements.searchInput.value = '';
            currentFilters.search = '';
            loadUsers(1);
            clearBtn.style.display = 'none';
        };
        elements.searchInput.parentNode.appendChild(clearBtn);
    }
    if (clearBtn) {
        clearBtn.style.display = elements.searchInput.value ? 'block' : 'none';
    }
}

