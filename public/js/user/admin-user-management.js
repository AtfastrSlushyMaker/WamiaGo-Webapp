/**
* Modern User Management JS
* Advanced user management with dynamic filtering, pagination and sorting
*/

// Debug mode for development
const DEBUG = true;

// Define API_ROUTES globally but we'll populate it in the init function
let API_ROUTES = {};

// Store DOM elements
let elements = {};

// Current state
let currentPage = 1;
let users = [];
let userStats = {
    total: 0,
    active: 0,
    suspended: 0,
    banned: 0
};

// Current filters
let currentFilters = {
    search: '',
    role: '',
    status: '',
    verified: '',
    itemsPerPage: 10,
};

/**
 * Initialize the application
 */
function init() {
    if (DEBUG) console.log('Initializing user management...');

    // Initialize elements object first
    initializeElements();

    // Hide loading overlay immediately
    showLoading(false);

    // Initialize API_ROUTES with the values from window variables
    API_ROUTES = {
        get: window.apiRouteUrl || '/admin/users/api',
        getUser: window.userGetUrl || '/admin/users/api/{id}',
        updateUser: window.userUpdateUrl || '/admin/users/{id}/edit',
        deleteUser: window.userDeleteApiUrl || '/admin/users/delete/api/{id}',
        createUser: window.userCreateUrl || '/admin/users/add',
    };

    // Fix userGetUrl if it doesn't have the /admin prefix
    if (API_ROUTES.getUser && API_ROUTES.getUser.startsWith('/users/')) {
        API_ROUTES.getUser = '/admin' + API_ROUTES.getUser;
        console.log('Fixed API_ROUTES.getUser to include /admin prefix:', API_ROUTES.getUser);
    }

    // Log routes for debugging *after* they are defined
    if (DEBUG) {
        console.log('API routes initialized inside init:', API_ROUTES);
        // Also log the raw window variables for comparison
        console.log('Raw window.userGetUrl:', window.userGetUrl);
    }

    // Check if essential routes are defined
    if (!API_ROUTES.get || !API_ROUTES.getUser) {
        console.error("Essential API routes (get, getUser) are not defined. Check the template script block.");
        showError("Configuration error: API routes missing. Cannot load user data.");
        return; // Stop initialization if routes are missing
    }

    // Set up event listeners
    setupEventListeners();

    // Check if we have preloaded users
    if (window.preloadedUsers && window.preloadedUsers.length > 0) {
        // Use preloaded users for initial render
        users = window.preloadedUsers;

        // Update user stats from preloaded data
        if (window.preloadedStats) {
            userStats = window.preloadedStats;
        } else {
            calculateUserStats(users);
        }

        // Render the initial data
        renderUsers(users);
        renderUserStats();

        // Also render pagination
        const totalItems = users.length;
        const totalPages = Math.ceil(totalItems / currentFilters.itemsPerPage);
        const itemsOnPage = Math.min(users.length, currentFilters.itemsPerPage);
        renderPagination(1, totalPages, totalItems, itemsOnPage);

        // Then load the first page of data from the API (in background)
        setTimeout(() => loadUsers(1), 500);
    } else {
        // No preloaded data, load from API
        loadUsers();
    }
}

/**
 * Set up all event listeners
 */
function setupEventListeners() {
    // View toggling
    if (elements.listViewBtn) {
        elements.listViewBtn.addEventListener('click', () => {
            toggleView('list');
        });
    }

    if (elements.cardViewBtn) {
        elements.cardViewBtn.addEventListener('click', () => {
            toggleView('card');
        });
    }

    // Search input with debounce
    if (elements.searchInput) {
        elements.searchInput.addEventListener('input', debounce(() => {
            currentFilters.search = elements.searchInput.value;
            loadUsers(1);
        }, 500));
    }

    // Filter changes
    if (elements.applyFiltersBtn) {
        elements.applyFiltersBtn.addEventListener('click', () => {
            if (elements.filterRole) {
                currentFilters.role = elements.filterRole.value;
            }

            if (elements.filterStatus) {
                currentFilters.status = elements.filterStatus.value;
            }

            if (elements.filterVerified) {
                currentFilters.verified = elements.filterVerified.value;
            }

            loadUsers(1);
        });
    }

    // Reset filters
    if (elements.resetFiltersBtn) {
        elements.resetFiltersBtn.addEventListener('click', () => {
            resetFilters();
            loadUsers(1);
        });
    }

    // Items per page
    if (elements.pageSize) {
        elements.pageSize.addEventListener('change', () => {
            currentFilters.itemsPerPage = parseInt(elements.pageSize.value);
            loadUsers(1);
        });
    }

    if (elements.pageSizeCards) {
        elements.pageSizeCards.addEventListener('change', () => {
            currentFilters.itemsPerPage = parseInt(elements.pageSizeCards.value);
            loadUsers(1);
        });
    }

    // Add user button
    if (elements.addUserBtn) {
        elements.addUserBtn.addEventListener('click', () => {
            const modalElement = document.getElementById('new-user-modal');
            if (!modalElement) {
                console.error('New user modal not found');
                return;
            }

            // Check if there's an existing modal instance
            let modal = bootstrap.Modal.getInstance(modalElement);

            if (!modal) {
                // Create new modal with proper configuration 
                modal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
            }

            // Setup the form submission handler for the new user form
            const form = modalElement.querySelector('form');
            if (form) {
                console.log('Setting up form handler for new user creation');

                // Make sure the form action is set correctly
                if (!form.action || form.action === window.location.href || form.action.endsWith('#')) {
                    form.action = API_ROUTES.createUser;
                    console.log('Set form action for new user:', form.action);
                }

                // Set up the form submission handler (without a user ID since this is a new user)
                setupFormSubmitHandler(form, null);
            } else {
                console.error('Form not found in new user modal');
            }

            modal.show();
        });
    }

    // Document event listeners for dynamic elements
    document.addEventListener('click', (e) => {
        // View user details
        if (e.target.closest('.view-user')) {
            const userElement = e.target.closest('.view-user');
            const userId = userElement.dataset.id;

            // Ensure we have a valid ID
            if (userId && !isNaN(parseInt(userId))) {
                viewUser(userId);
            } else {
                console.error('Invalid user ID:', userId);
                showError('Invalid user ID');
            }
        }

        // Edit user
        if (e.target.closest('.edit-user')) {
            const userElement = e.target.closest('.edit-user');
            const userId = userElement.dataset.id;

            // Ensure we have a valid ID
            if (userId && !isNaN(parseInt(userId))) {
                editUser(userId);
            } else {
                console.error('Invalid user ID for edit:', userId);
                showError('Invalid user ID for editing');
            }
        }

        // Delete user
        if (e.target.closest('.delete-user')) {
            const userElement = e.target.closest('.delete-user');
            const userId = userElement.dataset.id;
            const userName = userElement.dataset.name;

            // Ensure we have a valid ID
            if (userId && !isNaN(parseInt(userId))) {
                confirmDeleteUser(userId, userName);
            } else {
                console.error('Invalid user ID for delete:', userId);
                showError('Invalid user ID for deletion');
            }
        }

        // Pagination clicks
        if (e.target.closest('.page-link') && !e.target.closest('.page-link').parentElement.classList.contains('disabled')) {
            e.preventDefault();
            const page = e.target.closest('.page-link').dataset.page;
            if (page) {
                loadUsers(parseInt(page));
            }
        }
    });
}

/**
 * Toggle between list and card view
 */
function toggleView(view) {
    if (view === 'list') {
        if (elements.listView) elements.listView.style.display = 'block';
        if (elements.cardView) elements.cardView.style.display = 'none';
        if (elements.listViewBtn) elements.listViewBtn.classList.add('active');
        if (elements.cardViewBtn) elements.cardViewBtn.classList.remove('active');
    } else {
        if (elements.listView) elements.listView.style.display = 'none';
        if (elements.cardView) elements.cardView.style.display = 'block';
        if (elements.listViewBtn) elements.listViewBtn.classList.remove('active');
        if (elements.cardViewBtn) elements.cardViewBtn.classList.add('active');
    }
}

/**
 * Reset all filters to default values
 */
function resetFilters() {
    currentFilters.search = '';
    currentFilters.role = '';
    currentFilters.status = '';
    currentFilters.verified = '';

    if (elements.searchInput) elements.searchInput.value = '';
    if (elements.filterRole) elements.filterRole.value = '';
    if (elements.filterStatus) elements.filterStatus.value = '';
    if (elements.filterVerified) elements.filterVerified.value = '';
}

/**
 * Show/hide the loading overlay
 */
function showLoading(show) {
    if (elements.loadingOverlay) {
        elements.loadingOverlay.style.display = show ? 'flex' : 'none';
    }
}

/**
 * Load users from the API
 */
async function loadUsers(page = 1) {
    currentPage = page;

    // Only show loading for API requests when we don't have preloaded data
    // or when the page being requested is not page 1
    const shouldShowLoading = !(window.preloadedUsers && window.preloadedUsers.length > 0 && page === 1);
    if (shouldShowLoading) {
        showLoading(true);
    }

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

        if (currentFilters.verified !== '') {
            params.append('verified', currentFilters.verified);
        }

        // Debug log
        if (DEBUG) {
            console.log('Loading users with params:', Object.fromEntries(params.entries()));
        }

        // Check if API_ROUTES.get is defined
        if (!API_ROUTES.get) {
            console.error('API route for get is not defined. Check initialization of API_ROUTES.');
            throw new Error('API configuration error: Missing base URL for user list');
        }

        // Create a local copy of the URL
        let listApiUrl = API_ROUTES.get;

        // Fix incorrect URL prefix if needed - ensure it starts with /admin
        if (listApiUrl.startsWith('/users/')) {
            console.warn('Fixing incorrect API URL prefix (missing /admin)');
            listApiUrl = '/admin' + listApiUrl;
        } else if (!listApiUrl.includes('/admin/')) {
            // If URL doesn't have admin prefix at all, add it
            console.warn('Adding missing /admin prefix to API URL');
            listApiUrl = '/admin/users/api';
        }

        const apiUrl = `${listApiUrl}?${params.toString()}`;
        console.log('Attempting to fetch users from URL:', apiUrl);

        let response;

        try {
            response = await fetch(apiUrl);

            if (!response.ok) {
                const responseText = await response.text();
                console.error('API error response:', responseText);
                throw new Error(`API request failed with status ${response.status}: ${responseText.substring(0, 200)}...`);
            }
        } catch (error) {
            console.warn('First API attempt failed:', error.message);

            // Try using the debug API file as fallback
            console.log('Attempting to use debug-user-api.php fallback...');
            const fallbackUrl = '/debug-user-api.php';
            const fallbackApiUrl = `${fallbackUrl}?${params.toString()}`;

            try {
                console.log('Fetching from fallback URL:', fallbackApiUrl);
                response = await fetch(fallbackApiUrl);
                if (!response.ok) {
                    const responseText = await response.text();
                    throw new Error(`Fallback API request failed with status ${response.status}: ${responseText.substring(0, 200)}...`);
                }
                console.log('Fallback API request succeeded');
            } catch (fallbackError) {
                console.error('Both API attempts failed:', fallbackError.message);

                // If fallback also fails, try a final attempt with the alternative endpoint
                console.log('Attempting final fallback to /admin/api/users...');
                const lastFallbackUrl = '/admin/api/users';
                const lastFallbackApiUrl = `${lastFallbackUrl}?${params.toString()}`;

                try {
                    response = await fetch(lastFallbackApiUrl);
                    if (!response.ok) {
                        const responseText = await response.text();
                        throw new Error(`Final fallback API request failed with status ${response.status}: ${responseText.substring(0, 200)}...`);
                    }
                } catch (lastFallbackError) {
                    console.error('All API attempts failed:', lastFallbackError.message);
                    throw new Error(`Failed to load users: ${error.message}`);
                }
            }
        }

        // Now we have a valid response, process it
        try {
            const responseText = await response.text();
            console.log('Raw API response:', responseText.substring(0, 500));

            // Clean up response text - find where JSON actually starts (look for first {)
            let jsonText = responseText;
            const jsonStart = responseText.indexOf('{');
            if (jsonStart > 0) {
                jsonText = responseText.substring(jsonStart);
                console.log('Cleaned response starts with:', jsonText.substring(0, 50));
            }

            const data = JSON.parse(jsonText);

            // Update users array - handle both formats (rows or users)
            users = data.rows || data.users || [];

            // Debug: Check if we got valid user data
            if (DEBUG) {
                console.log('API returned data:', data);
                console.log(`Extracted ${users.length} users from API response`);
                if (users.length > 0) {
                    console.log('First user object:', users[0]);
                } else {
                    console.warn('No users found in API response');
                }
            }

            // Update pagination data
            const totalItems = data.total || users.length || 0;
            const totalPages = Math.ceil(totalItems / currentFilters.itemsPerPage) || 1;
            const itemsOnPage = users.length;

            // Update user stats if available
            if (data.stats) {
                updateUserStats(data.stats);
            } else {
                // Calculate stats from users array
                calculateUserStats(users);
            }

            // Render the data
            renderUsers(users);
            renderPagination(currentPage, totalPages, totalItems, itemsOnPage);

            // Debug log
            if (DEBUG) {
                console.log('Loaded users:', users);
            }
        } catch (error) {
            console.error('Error parsing API response:', error);
            showError('Failed to parse API response. Please try again.');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showError('Failed to load users. Please try again.');

        // Always hide loading on error
        showLoading(false);

        // If we have preloaded data, render that instead on error
        if (window.preloadedUsers && window.preloadedUsers.length > 0 && !users.length) {
            users = window.preloadedUsers;
        }
        // Don't fallback to demo users anymore
        else if (!users.length) {
            console.warn('No users data available. Showing empty state.');
            users = []; // Empty array will trigger the no users found message
        }

        // Render whatever users we have
        renderUsers(users);

        // Calculate pagination based on users data
        const totalItems = users.length;
        const totalPages = Math.ceil(totalItems / currentFilters.itemsPerPage);
        const itemsOnPage = Math.min(users.length, currentFilters.itemsPerPage);
        renderPagination(1, totalPages, totalItems, itemsOnPage);

        // Update user stats from users data
        calculateUserStats(users);
    } finally {
        // Always ensure loading is hidden
        showLoading(false);
    }
}

/**
 * Update user statistics from API data
 */
function updateUserStats(stats) {
    userStats = {
        total: stats.total || 0,
        active: stats.active || 0,
        suspended: stats.suspended || 0,
        banned: stats.banned || 0,
    };

    renderUserStats();
}

/**
 * Calculate user statistics from the users array
 */
function calculateUserStats(users) {
    let total = users.length;
    let active = 0;
    let suspended = 0;
    let banned = 0;

    users.forEach(user => {
        if (user.account_status === 'ACTIVE') active++;
        else if (user.account_status === 'SUSPENDED') suspended++;
        else if (user.account_status === 'BANNED') banned++;
    });

    userStats = { total, active, suspended, banned };
    renderUserStats();
}

/**
 * Render user statistics in the UI
 */
function renderUserStats() {
    if (elements.totalUsersCount) {
        elements.totalUsersCount.textContent = userStats.total || 0;
    }

    if (elements.activeUsersCount) {
        elements.activeUsersCount.textContent = userStats.active || 0;
    }

    if (elements.suspendedUsersCount) {
        elements.suspendedUsersCount.textContent = userStats.suspended || 0;
    }

    if (elements.bannedUsersCount) {
        elements.bannedUsersCount.textContent = userStats.banned || 0;
    }
}

/**
 * Render users in both table and card formats
 */
/**
 * Render user data in both table and card views
 */
function renderUsers(usersToRender) {
    // Safety checks to prevent errors
    if (!usersToRender) {
        console.error('No users data provided to renderUsers function');
        usersToRender = [];
    }

    if (!Array.isArray(usersToRender)) {
        console.error('Invalid users data format:', usersToRender);
        usersToRender = [];
    }

    if (usersToRender.length > 0 && DEBUG) {
        console.log(`Rendering ${usersToRender.length} users:`, usersToRender.slice(0, 2));
    } else {
        console.log('Rendering empty users array');
    }

    console.log('DEBUG: About to render user table. usersTableBody exists:', !!elements.usersTableBody);
    renderUserTable(usersToRender);

    console.log('DEBUG: About to render user cards. usersCardContainer exists:', !!elements.usersCardContainer);
    renderUserCards(usersToRender);

    console.log('DEBUG: Finished rendering users in both views');
}

/**
 * Render user data in table format
 */
function renderUserTable(users) {
    console.log('DEBUG: renderUserTable called with', users.length, 'users');

    if (!elements.usersTableBody) {
        console.error('ERROR: Cannot render table - usersTableBody element not found!');
        return;
    }

    console.log('DEBUG: Clearing usersTableBody before adding new rows');
    elements.usersTableBody.innerHTML = '';

    if (users.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="9" class="text-center py-5">
                <div class="py-5">
                    <div class="mb-4">
                        <div class="empty-state-icon">
                            <i class="fas fa-users-slash fa-4x text-muted"></i>
                        </div>
                    </div>
                    <h4 class="mb-3">No Users Found</h4>
                    <p class="text-muted mb-4 mx-auto" style="max-width: 500px;">
                        We couldn't find any users matching your current criteria. Try adjusting your filters or adding new users.
                    </p>
                    <div class="mt-4">
                        <button class="btn btn-outline-secondary btn-lg px-4 py-2 me-3" id="reset-search-btn">
                            <i class="fas fa-sync-alt me-2"></i> Reset Filters
                        </button>
                        <button class="btn btn-primary btn-lg px-4 py-2" id="empty-add-user-btn">
                            <i class="fas fa-user-plus me-2"></i> Add User
                    </button>
                    </div>
                </div>
            </td>
        `;
        elements.usersTableBody.appendChild(emptyRow);

        // Add event listener to the reset button we just created
        const resetButton = emptyRow.querySelector('#reset-search-btn');
        if (resetButton) {
            resetButton.addEventListener('click', () => {
                resetFilters();
                loadUsers(1);
            });
        }

        // Add event listener to the add user button
        const addUserButton = emptyRow.querySelector('#empty-add-user-btn');
        if (addUserButton && elements.addUserBtn) {
            addUserButton.addEventListener('click', () => {
                elements.addUserBtn.click(); // Trigger the main add user button
            });
        }

        return;
    }
    users.forEach((user, index) => {
        const row = document.createElement('tr');
        // Map API fields to variables (handle both naming conventions)
        // Make sure the user ID is a proper number - this is crucial for the API endpoints
        const userId = parseInt(user.id_user || user.id) || index + 1;

        // Better name extraction that checks multiple possible field names
        let name = '';
        if (user.name) name = user.name;
        else if (user.username) name = user.username;
        else if (user.fullName) name = user.fullName;
        else if (user.full_name) name = user.full_name;
        else if (user.firstName && user.lastName) name = `${user.firstName} ${user.lastName}`;
        else if (user.first_name && user.last_name) name = `${user.first_name} ${user.last_name}`;
        else name = `User ${userId}`; // Fallback to user ID if no name found

        const email = user.email || '';
        const phoneNumber = user.phone_number || user.phoneNumber || '';
        const dateOfBirth = user.date_of_birth || user.dateOfBirth || '-';
        const gender = user.gender || '-';
        const role = user.role || 'CLIENT';
        const accountStatus = user.account_status || user.accountStatus || 'ACTIVE';
        const isVerified = user.isVerified !== undefined ? user.isVerified : (user.isVerified !== undefined ? user.isVerified : false);
        const profilePicture = user.profile_picture || user.profilePicture || '/images/default-avatar.png';

        // Make profile picture path absolute if it's not already
        const profilePicSrc = profilePicture.startsWith('http') ? profilePicture :
            (profilePicture.startsWith('/') ? profilePicture : `/images/${profilePicture}`);
        // Format gender with icon and appropriate class
        let genderHTML = '';
        if (gender && gender.toUpperCase() === 'MALE') {
            genderHTML = `<span class="gender-badge male" data-tooltip="Male User"><i class="fas fa-mars"></i> Male</span>`;
        } else if (gender && gender.toUpperCase() === 'FEMALE') {
            genderHTML = `<span class="gender-badge female" data-tooltip="Female User"><i class="fas fa-venus"></i> Female</span>`;
        } else {
            genderHTML = `<span class="gender-badge other" data-tooltip="Other"><i class="fas fa-genderless"></i> ${gender || 'Not specified'}</span>`;
        }

        // Format date of birth with age calculation and styling
        let dobHTML = '';
        if (dateOfBirth && dateOfBirth !== '-') {
            // Calculate age
            const dob = new Date(dateOfBirth);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }

            // Determine date class based on age
            let dateClass = '';
            if (age < 25) {
                dateClass = 'recent';
            } else if (age < 50) {
                dateClass = '';
            } else if (age < 70) {
                dateClass = 'old';
            } else {
                dateClass = 'very-old';
            }

            dobHTML = `<span class="date-badge ${dateClass}"><i class="fas fa-birthday-cake"></i> ${dateOfBirth} (${age}y)</span>`;
        } else {
            dobHTML = `<span class="date-badge"><i class="fas fa-question-circle"></i> Not specified</span>`;
        }

        row.innerHTML = `
            <td class="text-center">${userId}</td>
            <td>
                <div class="d-flex align-items-center">
                    <img src="${profilePicSrc}" alt="Avatar" class="avatar me-3">
                    <div>
                        <div class="fw-bold">${name}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="mb-2"><i class="fas fa-envelope text-primary me-2"></i>${email}</div>
                ${phoneNumber ? `<div><i class="fas fa-phone-alt text-success me-2"></i>${phoneNumber}</div>` : ''}
            </td>
            <td>${dobHTML}</td>
            <td>${genderHTML}</td>
            <td>
                <span class="badge-role badge-${role.toLowerCase()}">${role}</span>
            </td>
            <td>
                <span class="badge-status badge-${accountStatus.toLowerCase()}">${accountStatus}</span>
            </td>
            <td>
                <span class="badge-status ${isVerified ? 'badge-verified' : 'badge-not-verified'}">
                    ${isVerified ? '<i class="fas fa-check-circle me-1"></i> Verified' : '<i class="fas fa-times-circle me-1"></i> Not Verified'}
                </span>
            </td>
            <td class="text-center">
                <button type="button" class="btn-action btn-view view-user" data-id="${userId}" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn-action btn-edit edit-user" data-id="${userId}" title="Edit User">
                    <i class="fas fa-pen"></i>
                </button>
                <button type="button" class="btn-action btn-delete delete-user" data-id="${userId}" data-name="${name}" title="Delete User">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        elements.usersTableBody.appendChild(row);
    });
}

/**
 * Render user data in card format
 */
function renderUserCards(users) {
    console.log('DEBUG: renderUserCards called with', users.length, 'users');

    if (!elements.usersCardContainer) {
        console.error('ERROR: usersCardContainer (#users-card-container) not found');

        // Try to find the container by using other selector methods as fallback
        const cardViewContainer = document.getElementById('card-view');
        if (cardViewContainer) {
            console.log('DEBUG: Found card-view container, creating users-card-container as fallback');

            // Create the container
            const newContainer = document.createElement('div');
            newContainer.id = 'users-card-container';
            newContainer.className = 'user-cards-grid';
            cardViewContainer.prepend(newContainer);

            // Update the elements reference
            elements.usersCardContainer = newContainer;
        } else {
            console.error('ERROR: Cannot create users-card-container, card-view container not found');
            return;
        }
    }

    console.log('DEBUG: Clearing usersCardContainer before adding new cards');
    elements.usersCardContainer.innerHTML = '';

    if (users.length === 0) {
        const emptyCard = document.createElement('div');
        emptyCard.className = 'grid-span-full text-center py-5';
        emptyCard.innerHTML = `
            <div class="py-5">
                <div class="mb-4">
                    <i class="fas fa-search fa-3x text-muted"></i>
                </div>
                <h4 class="mb-3">No Users Found</h4>
                <p class="text-muted mb-4 mx-auto" style="max-width: 500px;">
                    We couldn't find any users matching your current criteria. Try adjusting your filters or adding new users.
                </p>
                <div class="mt-4">
                    <button class="btn btn-outline-secondary btn-lg px-4 py-2 me-3" id="reset-search-cards-btn">
                        <i class="fas fa-sync-alt me-2"></i> Reset Filters
                    </button>
                    <button class="btn btn-primary btn-lg px-4 py-2" id="empty-add-user-cards-btn">
                        <i class="fas fa-user-plus me-2"></i> Add User
                    </button>
                </div>
            </div>
        `;
        elements.usersCardContainer.appendChild(emptyCard);

        // Add event listener to the reset button we just created
        const resetButton = emptyCard.querySelector('#reset-search-cards-btn');
        if (resetButton) {
            resetButton.addEventListener('click', () => {
                resetFilters();
                loadUsers(1);
            });
        }

        // Add event listener to the add user button
        const addUserButton = emptyCard.querySelector('#empty-add-user-cards-btn');
        if (addUserButton && elements.addUserBtn) {
            addUserButton.addEventListener('click', () => {
                elements.addUserBtn.click(); // Trigger the main add user button
            });
        }

        return;
    }

    console.log('DEBUG: Adding', users.length, 'user cards to container');

    users.forEach((user, index) => {
        console.log(`DEBUG: Creating card for user ${index + 1}/${users.length}: ${user.name || `User ${user.id_user}`}`);

        const col = document.createElement('div');
        col.className = 'user-card-container';

        // Get account status for styling
        const accountStatus = user.account_status?.toLowerCase() || 'active';

        // Get verification badge
        const verificationBadge = user.isVerified
            ? '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i> Verified</span>'
            : '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending</span>';

        // Format date of birth with age if available
        let ageDisplay = '';
        if (user.date_of_birth) {
            const birthDate = new Date(user.date_of_birth);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            ageDisplay = `<small class="text-muted">(${age} years)</small>`;
        }

        // Make sure we have a valid user ID
        const userId = parseInt(user.id_user || user.id);

        // Better name extraction that checks multiple possible field names
        let name = '';
        if (user.name) name = user.name;
        else if (user.username) name = user.username;
        else if (user.fullName) name = user.fullName;
        else if (user.full_name) name = user.full_name;
        else if (user.firstName && user.lastName) name = `${user.firstName} ${user.lastName}`;
        else if (user.first_name && user.last_name) name = `${user.first_name} ${user.last_name}`;
        else name = `User ${userId}`; // Fallback to user ID if no name found

        col.innerHTML = `
            <div class="card user-card-modern h-100 shadow-sm">
                <!-- Quick action buttons that appear on hover -->
                <div class="quick-actions">
                    <button type="button" class="quick-action-btn edit-btn edit-user" data-id="${userId}" title="Edit User">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="quick-action-btn delete-btn delete-user" data-id="${userId}" data-name="${name}" title="Delete User">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="user-card-header d-flex justify-content-between p-3 border-bottom">
                    <span class="badge status-badge status-${accountStatus}">${user.account_status || 'ACTIVE'}</span>
                    <span class="badge role-badge role-${user.role?.toLowerCase() || 'client'}">${user.role || 'CLIENT'}</span>
                </div>
                <div class="user-card-body p-4 text-center position-relative">
                    <div class="avatar-wrapper mb-3">
                        <img src="${user.profile_picture || '/images/default-avatar.png'}" alt="${name}" class="rounded-circle user-avatar shadow-sm border border-light">
                        <span class="verification-indicator ${user.isVerified ? 'verified' : 'not-verified'}">
                            <i class="fas fa-${user.isVerified ? 'check' : 'clock'}"></i>
                        </span>
                    </div>
                    <h5 class="card-title mb-1">${name}</h5>
                    <p class="mb-2">${verificationBadge}</p>
                    
                    <hr class="my-3">
                    
                    <div class="user-details text-start">
                        <div class="user-detail mb-2">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            <span class="text-truncate d-inline-block" style="max-width: 180px;">${user.email || '-'}</span>
                        </div>
                        <div class="user-detail mb-2">
                            <i class="fas fa-phone text-success me-2"></i>
                            <span>${user.phone_number || '-'}</span>
                        </div>
                        <div class="user-detail mb-2">
                            <i class="fas fa-${user.gender?.toLowerCase() === 'male' ? 'mars text-info' :
                user.gender?.toLowerCase() === 'female' ? 'venus text-danger' :
                    'genderless text-muted'} me-2"></i>
                            <span>${user.gender || 'Not specified'}</span>
                        </div>
                        <div class="user-detail">
                            <i class="fas fa-birthday-cake text-warning me-2"></i>
                            <span>${user.date_of_birth || 'Not specified'} ${ageDisplay}</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light p-3 d-flex justify-content-around">
                    <button type="button" class="btn btn-sm btn-outline-primary action-btn view-user" data-id="${userId}" title="View Details">
                        <i class="fas fa-eye me-1"></i> View
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success action-btn edit-user" data-id="${userId}" title="Edit User">
                        <i class="fas fa-edit me-1"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger action-btn delete-user" data-id="${userId}" data-name="${name}" title="Delete User">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        `;
        elements.usersCardContainer.appendChild(col);
    });
}

/**
 * Render pagination controls
 */
function renderPagination(currentPage, totalPages, totalItems, itemsOnPage) {
    // Update the showing results text
    if (elements.showingResults) {
        elements.showingResults.textContent = itemsOnPage;
    }

    if (elements.totalResults) {
        elements.totalResults.textContent = totalItems;
    }

    if (elements.showingResultsCards) {
        elements.showingResultsCards.textContent = itemsOnPage;
    }

    if (elements.totalResultsCards) {
        elements.totalResultsCards.textContent = totalItems;
    }

    // Generate pagination links
    const paginationContainers = document.querySelectorAll('.pagination-container');

    paginationContainers.forEach(container => {
        container.innerHTML = '';

        // Skip if very few pages
        if (totalPages <= 1) return;

        const pagination = document.createElement('nav');
        pagination.setAttribute('aria-label', 'User pagination');

        const ul = document.createElement('ul');
        ul.className = 'pagination';

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item${currentPage === 1 ? ' disabled' : ''}`;

        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.setAttribute('aria-label', 'Previous');
        prevLink.dataset.page = currentPage - 1;
        prevLink.innerHTML = '<i class="fas fa-chevron-left"></i>';

        prevLi.appendChild(prevLink);
        ul.appendChild(prevLi);

        // Page numbers
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        // Adjust if we're near the end
        if (endPage - startPage + 1 < maxPagesToShow && startPage > 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        // First page link if not already included
        if (startPage > 1) {
            const firstLi = document.createElement('li');
            firstLi.className = 'page-item';

            const firstLink = document.createElement('a');
            firstLink.className = 'page-link';
            firstLink.href = '#';
            firstLink.dataset.page = 1;
            firstLink.textContent = '1';

            firstLi.appendChild(firstLink);
            ul.appendChild(firstLi);

            // Ellipsis if needed
            if (startPage > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';

                const ellipsisSpan = document.createElement('span');
                ellipsisSpan.className = 'page-link';
                ellipsisSpan.innerHTML = '&hellip;';

                ellipsisLi.appendChild(ellipsisSpan);
                ul.appendChild(ellipsisLi);
            }
        }

        // Page links
        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item${i === currentPage ? ' active' : ''}`;

            const pageLink = document.createElement('a');
            pageLink.className = 'page-link';
            pageLink.href = '#';
            pageLink.dataset.page = i;
            pageLink.textContent = i;

            pageLi.appendChild(pageLink);
            ul.appendChild(pageLi);
        }

        // Last page link if not already included
        if (endPage < totalPages) {
            // Ellipsis if needed
            if (endPage < totalPages - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';

                const ellipsisSpan = document.createElement('span');
                ellipsisSpan.className = 'page-link';
                ellipsisSpan.innerHTML = '&hellip;';

                ellipsisLi.appendChild(ellipsisSpan);
                ul.appendChild(ellipsisLi);
            }

            const lastLi = document.createElement('li');
            lastLi.className = 'page-item';

            const lastLink = document.createElement('a');
            lastLink.className = 'page-link';
            lastLink.href = '#';
            lastLink.dataset.page = totalPages;
            lastLink.textContent = totalPages;

            lastLi.appendChild(lastLink);
            ul.appendChild(lastLi);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item${currentPage === totalPages ? ' disabled' : ''}`;

        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.setAttribute('aria-label', 'Next');
        nextLink.dataset.page = currentPage + 1;
        nextLink.innerHTML = '<i class="fas fa-chevron-right"></i>';

        nextLi.appendChild(nextLink);
        ul.appendChild(nextLi);

        pagination.appendChild(ul);
        container.appendChild(pagination);
    });
}

/**
 * View user details
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
                    const saveButton = modal.querySelector('#save-user-edit');
                    if (saveButton) saveButton.style.display = 'none';
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

                // Hide location fields
                const locationFields = ['address', 'latitude', 'longitude', 'city', 'country', 'postal_code'];
                locationFields.forEach(fieldName => {
                    const field = form.querySelector(`input[name="${fieldName}"]`);
                    if (field) {
                        const fieldGroup = field.closest('.form-group') || field.closest('.mb-3');
                        if (fieldGroup) fieldGroup.style.display = 'none';
                    }
                });

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

/**
 * Edit user
 */
function editUser(userId) {
    if (DEBUG) {
        console.log('Edit user:', userId);
    }

    showLoading(true);

    try {
        // Get the edit modal - try multiple possible IDs since the template might use different naming
        let modal = document.getElementById('edit-user-modal');
        if (!modal) {
            modal = document.querySelector('.modal[id*="edit"]'); // Fallback to any modal with "edit" in the ID
        }
        if (!modal) throw new Error('Edit modal not found. Check modal ID in the template.');

        // First fetch the latest user data from the API to ensure we have current information
        // Make sure we're using a numeric ID, not the literal string "USER_ID"
        const actualUserId = parseInt(userId);

        if (isNaN(actualUserId)) {
            console.error('Invalid user ID:', userId);
            showError('Invalid user ID provided');
            showLoading(false);
            return;
        }

        // Check if API_ROUTES.getUser is defined
        if (!API_ROUTES.getUser) {
            throw new Error('API route for getUser is not defined. Check initialization of API_ROUTES.');
        }

        // Log the base URL before replacement
        console.log('>>> BASE URL FROM API_ROUTES:', API_ROUTES.getUser);

        // Create a local copy of the URL to avoid modifying the global API_ROUTES object
        let userApiUrl = API_ROUTES.getUser;

        // Fix incorrect URL prefix if needed - ensure it starts with /admin
        if (userApiUrl.startsWith('/users/')) {
            console.warn('Fixing incorrect API URL prefix (missing /admin)');
            userApiUrl = '/admin' + userApiUrl;
        }

        // Use the helper function to safely replace ID in URL
        const url = replaceIdInUrl(userApiUrl, actualUserId);

        // Explicitly log the final URL before fetching
        console.log('>>> FINAL FETCH URL:', url);

        if (DEBUG) {
            console.log('Fetching user data from URL:', url);
        }

        fetch(url)
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (response.ok && contentType && contentType.includes("application/json")) {
                    // If response is OK and content type is JSON, parse it
                    return response.json();
                } else {
                    // If not OK or not JSON, read as text and throw an error
                    return response.text().then(text => {
                        let errorMessage = `API request failed or returned non-JSON response. Status: ${response.status}`;
                        if (text) {
                            // Include a snippet of the response text for debugging
                            errorMessage += `. Response: ${text.substring(0, 200)}${text.length > 200 ? '...' : ''}`;
                        }
                        // Log the full text for more detailed debugging if needed
                        if (DEBUG) {
                            console.error("Full non-JSON response:", text);
                        }
                        throw new Error(errorMessage);
                    });
                }
            })
            .then(apiUser => {
                // Get the user data from API response
                const user = apiUser.data || apiUser;

                if (!user) throw new Error('User data not found in API response');                // Debug log to see the exact data structure
                console.log('DEBUG - User data received from API:', user);

                // Check for nested structure - the key insight
                console.log('DEBUG - Checking for address in nested properties:');
                console.log('- Keys in user object:', Object.keys(user));

                // Check for data/user nesting pattern
                if (user.data && typeof user.data === 'object') {
                    console.log('- Found nested data object, keys:', Object.keys(user.data));
                }

                // Check for user property nesting
                if (user.user && typeof user.user === 'object') {
                    console.log('- Found nested user object, keys:', Object.keys(user.user));
                }

                // Check for addressDetails or userDetails pattern
                if (user.addressDetails && typeof user.addressDetails === 'object') {
                    console.log('- Found addressDetails object, keys:', Object.keys(user.addressDetails));
                }

                if (user.userDetails && typeof user.userDetails === 'object') {
                    console.log('- Found userDetails object, keys:', Object.keys(user.userDetails));
                }

                // Check for profile info
                if (user.profile && typeof user.profile === 'object') {
                    console.log('- Found profile object, keys:', Object.keys(user.profile));
                }

                // Check for address object directly
                if (user.address && typeof user.address === 'object') {
                    console.log('- Found address object, keys:', Object.keys(user.address));
                }

                // Check for location object
                if (user.location && typeof user.location === 'object') {
                    console.log('- Found location object, keys:', Object.keys(user.location));
                }

                // Log specifically the fields we're having trouble with
                console.log('DEBUG - Critical fields check:', {
                    'date_of_birth fields': {
                        date_of_birth: user.date_of_birth,
                        dateOfBirth: user.dateOfBirth,
                        birth_date: user.birth_date,
                        birthDate: user.birthDate,
                        dob: user.dob
                    },
                    'address fields': {
                        address: user.address,
                        street_address: user.street_address,
                        streetAddress: user.streetAddress,
                        'address_info.address': user.address_info?.address,
                        'addressInfo.address': user.addressInfo?.address,
                        'location.address': user.location?.address
                    },
                    'city/country/postal': {
                        city: user.city,
                        country: user.country,
                        postal_code: user.postal_code,
                        postalCode: user.postalCode,
                        zip: user.zip,
                        zipCode: user.zipCode
                    }
                });

                // Populate the form fields
                const form = modal.querySelector('form');
                if (!form) throw new Error('Form not found');

                // Set the user ID in a hidden field
                const idField = form.querySelector('input[name="id"]');
                if (idField) idField.value = userId;

                // Update form action using helper function
                form.action = replaceIdInUrl(API_ROUTES.updateUser, userId);

                // Populate all available fields                // Basic information
                populateField(form, 'name', user.name || user.fullName || user.full_name || '');
                populateField(form, 'email', user.email || user.mail || '');
                populateField(form, 'phone_number', user.phone_number || user.phone || user.phoneNumber || '');

                // Location is handled separately on the backend, don't populate location fields
                // We'll completely remove location-related fields from the form

                // Find and hide any location-related fields that might exist in the form
                const locationFields = ['address', 'latitude', 'longitude'];
                locationFields.forEach(fieldName => {
                    const field = form.querySelector(`input[name="${fieldName}"]`);
                    if (field) {
                        // Find the parent container and hide it
                        const fieldGroup = field.closest('.form-group') || field.closest('.mb-3');
                        if (fieldGroup) {
                            fieldGroup.style.display = 'none';
                        }
                    }
                });

                // Check if the form has a valid submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (!submitBtn) {
                    console.error('CRITICAL ERROR: Form is missing a submit button!');
                    const formButtons = form.querySelectorAll('button');
                    console.log(`Found ${formButtons.length} other buttons in the form:`,
                        Array.from(formButtons).map(b => `${b.textContent.trim()} (type: ${b.type})`));

                    // Try to find a button that looks like a submit button and fix it
                    const possibleSubmitBtn = Array.from(formButtons).find(b =>
                        b.textContent.toLowerCase().includes('save') ||
                        b.textContent.toLowerCase().includes('update') ||
                        b.classList.contains('btn-primary') ||
                        b.classList.contains('btn-success'));

                    if (possibleSubmitBtn) {
                        console.log('Found a likely submit button:', possibleSubmitBtn.textContent);
                        possibleSubmitBtn.type = 'submit';
                        console.log('Fixed button type to "submit"');
                    } else {
                        // If no suitable button found, check for buttons outside the form that might be related
                        const modalFooterButtons = modal.querySelectorAll('.modal-footer button');
                        console.log(`Found ${modalFooterButtons.length} buttons in modal footer:`,
                            Array.from(modalFooterButtons).map(b => `${b.textContent.trim()} (type: ${b.type})`));

                        // If found in footer, add them to the form
                        if (modalFooterButtons.length > 0) {
                            const saveBtn = Array.from(modalFooterButtons).find(b =>
                                b.textContent.toLowerCase().includes('save') ||
                                b.textContent.toLowerCase().includes('update') ||
                                b.classList.contains('btn-primary') ||
                                b.classList.contains('btn-success'));

                            if (saveBtn) {
                                console.log('Found save button in modal footer:', saveBtn.textContent);
                                saveBtn.type = 'submit';
                                // Move it to the form or connect it to form submission
                                saveBtn.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    form.dispatchEvent(new Event('submit'));
                                });
                                console.log('Connected footer save button to form submission');
                            }
                        }
                    }
                } else {
                    console.log('Form has a valid submit button:', submitBtn.textContent.trim());
                }

                // Account settings
                populateSelectField(form, 'role', user.role || user.userRole || user.user_role || 'CLIENT');
                populateSelectField(form, 'account_status', user.account_status || user.status || user.accountStatus || 'ACTIVE');
                populateSelectField(form, 'gender', user.gender || '');

                // Enhanced function to find properties in deeply nested objects
                // This solution handles deeply nested properties by recursively searching the user object
                function findPropertyInObject(obj, propertyNames, maxDepth = 3) {
                    if (!obj || typeof obj !== 'object' || maxDepth <= 0) return null;

                    // Direct property check
                    for (const name of propertyNames) {
                        if (obj[name] !== undefined) {
                            console.log(`Found property "${name}" at top level with value:`, obj[name]);
                            return obj[name];
                        }
                    }

                    // Handle special case where the property might be an object itself
                    // Example: user.address could be an object containing street, city, etc.
                    const objectProperties = ['address', 'location', 'addressInfo', 'address_info', 'addressDetails'];
                    for (const objProp of objectProperties) {
                        if (obj[objProp] && typeof obj[objProp] === 'object') {
                            console.log(`Checking nested object "${objProp}":`, obj[objProp]);
                            for (const name of propertyNames) {
                                if (obj[objProp][name] !== undefined) {
                                    console.log(`Found property "${name}" in ${objProp} object with value:`, obj[objProp][name]);
                                    return obj[objProp][name];
                                }
                            }
                        }
                    }

                    // Search recursively in nested objects
                    for (const key in obj) {
                        if (typeof obj[key] === 'object' && obj[key] !== null) {
                            const result = findPropertyInObject(obj[key], propertyNames, maxDepth - 1);
                            if (result !== null) {
                                console.log(`Found property in nested object "${key}" with value:`, result);
                                return result;
                            }
                        }
                    }

                    return null;
                }

                // Try to find date of birth in any property
                const dobValue = findPropertyInObject(user, [
                    'date_of_birth', 'dateOfBirth', 'birth_date', 'birthDate',
                    'dob', 'birthdate', 'birth_day', 'date'
                ]) || '';

                // Check if the user has a location object with address
                let fullAddress = '';

                // Try to find address from the location object first (this is the proper way in your data model)
                if (user.location && user.location.address) {
                    console.log('Found address in user.location:', user.location.address);
                    fullAddress = user.location.address;

                    // Handle coordinates if they exist in the form
                    populateField(form, 'latitude', user.location.latitude || '');
                    populateField(form, 'longitude', user.location.longitude || '');
                } else {
                    // Fallback to directly looking for address properties anywhere in the user object
                    fullAddress = findPropertyInObject(user, ['address', 'street_address', 'streetAddress']) || '';
                    populateField(form, 'latitude', user.latitude || '');
                    populateField(form, 'longitude', user.longitude || '');
                }

                // Simply populate the address field, normalizing N/A to empty string
                populateField(form, 'address', fullAddress === 'N/A' ? '' : fullAddress);

                if (dobValue) {
                    const dobField = form.querySelector('input[name="date_of_birth"]');
                    if (dobField) {
                        try {
                            // Check if date is in ISO format or needs conversion
                            if (typeof dobValue === 'string') {
                                if (dobValue.includes('T')) {
                                    // Already in ISO format, just take the date part
                                    dobField.value = dobValue.split('T')[0];
                                } else {
                                    // Try to convert from various formats
                                    const parts = dobValue.split(/[-\/\.]/);
                                    if (parts.length === 3) {
                                        // Assuming yyyy-mm-dd or similar
                                        let year, month, day;

                                        // Handle both yyyy-mm-dd and dd-mm-yyyy formats
                                        if (parts[0].length === 4) {
                                            // yyyy-mm-dd format
                                            year = parts[0];
                                            month = parts[1].padStart(2, '0');
                                            day = parts[2].padStart(2, '0');
                                        } else {
                                            // dd-mm-yyyy format
                                            day = parts[0].padStart(2, '0');
                                            month = parts[1].padStart(2, '0');
                                            year = parts[2];
                                        }

                                        dobField.value = `${year}-${month}-${day}`;
                                    } else {
                                        // Try parsing as date
                                        const dateObj = new Date(dobValue);
                                        if (!isNaN(dateObj.getTime())) {
                                            // Valid date, format as yyyy-mm-dd
                                            const year = dateObj.getFullYear();
                                            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                                            const day = String(dateObj.getDate()).padStart(2, '0');
                                            dobField.value = `${year}-${month}-${day}`;
                                        } else {
                                            dobField.value = dobValue;
                                        }
                                    }
                                }
                            } else if (typeof dobValue === 'object' && dobValue instanceof Date) {
                                // If it's already a Date object
                                const year = dobValue.getFullYear();
                                const month = String(dobValue.getMonth() + 1).padStart(2, '0');
                                const day = String(dobValue.getDate()).padStart(2, '0');
                                dobField.value = `${year}-${month}-${day}`;
                            }
                        } catch (error) {
                            console.error('Error formatting date of birth:', error);
                            // As a fallback, try to set the original value
                            dobField.value = dobValue;
                        }
                    }
                }

                // Checkboxes require special handling
                const verifiedField = form.querySelector('input[name="isVerified"]');
                if (verifiedField) {
                    verifiedField.checked = user.isVerified === true || user.isVerified === 1 || user.isVerified === '1';
                }

                // Setup form submission handler with AJAX
                // setupFormSubmitHandler(form, userId);

                // Show the modal with our new direct approach
                showAndSetupEditModal(form, userId, modal);

                // Debug the form fields after population
                // debugFormFields(form);

                showLoading(false);
            })
            .catch(error => {
                console.error('Error loading user data:', error);

                let detailedErrorMessage = error.message;

                // Check for JSON parsing errors specifically
                if (error instanceof SyntaxError && error.message.includes('JSON')) {
                    detailedErrorMessage = 'Server returned invalid data (not JSON). This might indicate a server configuration or routing issue. Please check the server logs and configuration.';
                } else if (error.message.includes('500')) {
                    detailedErrorMessage = 'Server error (500) occurred. Check the server logs for details.';
                } else if (error.message.includes('failed or returned non-JSON')) {
                    // Keep the detailed message from the fetch block
                    detailedErrorMessage = error.message;
                }

                showError('Could not load user data: ' + detailedErrorMessage);
                showLoading(false);
            });
    } catch (error) {
        console.error('Error preparing edit modal:', error);
        showError('Could not load user data for editing: ' + error.message);
        showLoading(false);
    }
}

/**
 * Helper function to populate a text field
 */
function populateField(form, fieldName, value) {
    const field = form.querySelector(`input[name="${fieldName}"]`);
    if (field) field.value = value || '';
}

/**
 * Helper function to populate a select field
 */
function populateSelectField(form, fieldName, value) {
    const field = form.querySelector(`select[name="${fieldName}"]`);
    if (field) {
        // Check if the option exists
        const option = Array.from(field.options).find(opt =>
            opt.value.toLowerCase() === (value || '').toLowerCase()
        );

        if (option) {
            field.value = option.value;
        } else if (value) {
            // Option doesn't exist, but we have a value to set
            // Create a new option
            const newOption = document.createElement('option');
            newOption.value = value;
            newOption.textContent = value;
            field.appendChild(newOption);
            field.value = value;
        }
    }
}

/**
 * Setup form submission handler for AJAX
 */
function setupFormSubmitHandler(form, userId) {
    // First check if the form already has a submit handler to avoid duplicates
    if (form._hasSubmitHandler) {
        console.log('Form already has a submit handler, removing old one first');
        form.onsubmit = null;
    }

    // Add direct event listener to submit buttons as a backup
    const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"], button.submit-btn, .btn-primary');
    submitButtons.forEach(button => {
        // Remove existing click listeners by cloning
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);

        newButton.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('Submit button clicked directly:', this.textContent || this.value);

            if (form.checkValidity && !form.checkValidity()) {
                console.log('Form validation failed');
                // Force the browser to show validation messages
                form.reportValidity();
                return;
            }

            // Call our submit handler
            submitFormData(form, userId);
        });

        console.log('Added direct click handler to button:', newButton.textContent || newButton.value);
    });

    // Mark the form as having a submit handler
    form._hasSubmitHandler = true;

    // Set up the regular form submission handler
    form.onsubmit = function (e) {
        e.preventDefault();
        console.log('Form submission intercepted through onsubmit event for user:', userId);
        submitFormData(form, userId);
    };

    console.log('Form submission handler set up successfully for', userId ? `user #${userId}` : 'new user');
}

/**
 * Handle the actual form data submission - Completely reworked to ensure reliability
 */
function submitFormData(form, userId) {
    console.log('🚀 FORM SUBMISSION STARTED - Force-fixed version');
    console.log('Form ID:', form.id);
    console.log('User ID:', userId);
    showLoading(true);

    try {
        // CRITICAL FIX: First, find ALL possible submit buttons and log them
        const allButtons = form.querySelectorAll('button');
        console.log('All form buttons:', Array.from(allButtons).map(b => ({
            text: b.textContent.trim(),
            type: b.type,
            classes: b.className
        })));

        // Create a fresh FormData object from the form
        const formData = new FormData(form);

        // Disable ALL buttons to prevent double submission
        allButtons.forEach(btn => btn.disabled = true);

        // Debug all form data being submitted
        console.log('📋 Form data being submitted:');
        for (let pair of formData.entries()) {
            console.log(`${pair[0]}: ${pair[1]}`);
        }

        // CRITICAL: Add all necessary security tokens
        // 1. Try meta tag CSRF token
        const metaCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (metaCsrfToken && !formData.has('_token')) {
            console.log('Adding meta CSRF token to form data');
            formData.append('_token', metaCsrfToken);
        }

        // 2. Look for hidden CSRF token fields that might already exist in the form
        const tokenField = form.querySelector('input[name="_token"], input[name="csrf_token"], input[name="token"]');
        if (tokenField && !formData.has('_token')) {
            console.log('Found hidden CSRF token in the form:', tokenField.value);
            formData.append('_token', tokenField.value);
        }

        // 3. Get any tokens from cookies as a last resort
        const csrfCookie = document.cookie.split(';').find(c => c.trim().startsWith('XSRF-TOKEN='));
        if (csrfCookie && !formData.has('_token')) {
            const token = decodeURIComponent(csrfCookie.split('=')[1]);
            console.log('Using CSRF token from cookies');
            formData.append('_token', token);
        }

        // CRITICAL FIX: Ensure the form has a valid action URL
        if (!form.action || form.action === window.location.href || form.action.endsWith('#')) {
            console.warn('⚠️ Form action not properly set, using API route instead');
            if (userId) {
                form.action = replaceIdInUrl(API_ROUTES.updateUser, userId);
            } else {
                form.action = API_ROUTES.createUser;
            }
            console.log('Fixed form action:', form.action);
        }

        // CRITICAL FIX: Ensure form has user ID for updates
        if (userId && !formData.has('id')) {
            console.log('Adding user ID to form data:', userId);
            formData.append('id', userId);
        }

        console.log('⚡ Submitting form data to:', form.action);

        // COMPLETELY NEW APPROACH: Try multiple submission methods in sequence
        // Method 1: Use the Fetch API first (modern approach)
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                console.log('Fetch response received:', response.status);

                // Pass the response to the handler
                return handleSubmissionResponse(response, form, userId, allButtons);
            })
            .catch(error => {
                console.error('Fetch submission failed, trying XMLHttpRequest as backup:', error);

                // Method 2: Fall back to XMLHttpRequest if fetch fails
                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.onload = function () {
                    console.log('XMLHttpRequest response received:', xhr.status);

                    // Create a Response-like object to use with the same handler
                    const responseObj = {
                        ok: xhr.status >= 200 && xhr.status < 300,
                        status: xhr.status,
                        text: () => Promise.resolve(xhr.responseText),
                        json: () => {
                            try {
                                return Promise.resolve(JSON.parse(xhr.responseText));
                            } catch (e) {
                                return Promise.reject(e);
                            }
                        }
                    };

                    handleSubmissionResponse(responseObj, form, userId, allButtons);
                };

                xhr.onerror = function () {
                    console.error('Both submission methods failed!');

                    // Method 3: Last resort - try actual form submission
                    console.log('📮 LAST RESORT: Attempting direct form submission');

                    // Create a temporary iframe to capture the form submission
                    const iframe = document.createElement('iframe');
                    iframe.name = 'submission-frame-' + Date.now();
                    iframe.style.display = 'none';
                    document.body.appendChild(iframe);

                    // Set up form to submit to the iframe
                    const originalAction = form.action;
                    const originalMethod = form.method;
                    const originalTarget = form.target;

                    form.action = originalAction;
                    form.method = 'post';
                    form.target = iframe.name;

                    // After iframe loads, try to extract result
                    iframe.onload = function () {
                        try {
                            const iframeContent = iframe.contentWindow.document.body.innerHTML;
                            console.log('Direct form submission completed');

                            if (iframeContent.includes('success') ||
                                iframeContent.includes('Success') ||
                                iframeContent.includes('created') ||
                                iframeContent.includes('updated')) {
                                // Success
                                showSuccess(userId ? 'User updated successfully' : 'User created successfully');

                                // Close modal
                                const modal = form.closest('.modal');
                                const bsModal = bootstrap.Modal.getInstance(modal);
                                if (bsModal) bsModal.hide();

                                // Reload users list
                                loadUsers(currentPage);
                            } else {
                                // Error
                                showError('Form submission failed. Please try again.');
                            }
                        } catch (e) {
                            console.error('Error handling iframe response:', e);
                            showError('Unknown error during form submission');
                        }

                        // Cleanup
                        form.action = originalAction;
                        form.method = originalMethod;
                        form.target = originalTarget;

                        setTimeout(() => {
                            document.body.removeChild(iframe);
                            showLoading(false);
                            allButtons.forEach(btn => btn.disabled = false);
                        }, 500);
                    };

                    // Submit the form
                    console.log('Submitting form directly...');
                    form.submit();
                };

                xhr.send(formData);
            });

    } catch (error) {
        console.error('❌ Critical error preparing form submission:', error);
        showError('Critical error: ' + error.message);
        showLoading(false);
        form.querySelectorAll('button').forEach(btn => btn.disabled = false);
    }
}

/**
 * Handle the response from a form submission
 */
function handleSubmissionResponse(response, form, userId, allButtons) {
    return response.text().then(text => {
        console.log('Response status:', response.status);
        console.log('Response text (first 100 chars):', text.substring(0, 100));

        if (response.ok) {
            let data;
            try {
                data = JSON.parse(text);
                console.log('Successfully parsed JSON response:', data);
            } catch (e) {
                console.log('Non-JSON response, checking content...');

                // Handle non-JSON successful responses
                if (text.includes('success') || text.includes('Success')) {
                    data = { success: true, message: 'Operation completed successfully' };
                } else {
                    data = { success: true, raw: text };
                }
            }

            // Hide the modal
            const modal = form.closest('.modal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();

            // Show success message
            const message = userId ? 'User updated successfully' : 'User created successfully';
            showSuccess(message);

            // Reload users to show the updated data
            loadUsers(currentPage);

            return data;
        } else {
            // Handle error responses
            console.error('Error response:', response.status, text);

            // Try to extract a meaningful error message
            let errorMessage = `Server error (${response.status})`;
            if (text) {
                // Check for HTML error message
                const errorPattern = /<div[^>]*class="[^"]*alert[^"]*"[^>]*>([\s\S]*?)<\/div>/i;
                const errorMatch = text.match(errorPattern);
                if (errorMatch && errorMatch[1]) {
                    // Remove HTML tags for cleaner error message
                    errorMessage = errorMatch[1].replace(/<\/?[^>]+(>|$)/g, " ").trim();
                } else if (text.length < 200) {
                    errorMessage = text;
                }

                // Try to extract JSON error message if present
                try {
                    const jsonData = JSON.parse(text);
                    if (jsonData.error || jsonData.message) {
                        errorMessage = jsonData.error || jsonData.message;
                    }
                } catch (e) {
                    // Not JSON, continue with the error message we already have
                }
            }

            showError('Failed to save user: ' + errorMessage);
            throw new Error(errorMessage);
        }
    })
        .finally(() => {
            // Always ensure we reset the UI state
            showLoading(false);
            if (allButtons) allButtons.forEach(btn => btn.disabled = false);
        });
}

/**
 * Confirm user deletion
 */
function confirmDeleteUser(userId, userName) {
    if (DEBUG) {
        console.log('Confirm delete user:', userId, userName);
    }

    // Make sure we're using a numeric ID
    const actualUserId = parseInt(userId);

    if (isNaN(actualUserId)) {
        console.error('Invalid user ID for deletion:', userId);
        showError('Invalid user ID provided');
        return;
    }

    const modal = document.getElementById('delete-user-modal');
    if (!modal) {
        console.error('Delete modal not found with ID "delete-user-modal"');
        return;
    }

    // Set user info in the modal
    const userNameSpan = modal.querySelector('#delete-user-name');
    if (userNameSpan) userNameSpan.textContent = userName || actualUserId;

    // Set the user ID in a hidden field
    const idField = modal.querySelector('input[name="id"]');
    if (idField) idField.value = actualUserId;

    // Update form action if applicable
    const form = modal.querySelector('form');
    if (form) {
        // Use helper function to safely replace ID in URL
        form.action = replaceIdInUrl(API_ROUTES.deleteUser, actualUserId);
    }

    // Set up the confirm button - using the correct ID "confirm-delete" from the template
    const confirmButton = modal.querySelector('#confirm-delete');
    if (confirmButton) {
        // Remove any existing event listeners by cloning the button
        const newButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newButton, confirmButton);

        // Add direct click event handler (not using async function wrapper)
        newButton.addEventListener('click', function (event) {
            event.preventDefault();
            console.log('Delete button clicked for user:', actualUserId);
            deleteUser(actualUserId, modal);
        });
    } else {
        console.error('Confirm delete button not found with ID "confirm-delete"');
    }

    // Show the modal - first check if there's an existing instance
    let bsModal = bootstrap.Modal.getInstance(modal);

    if (!bsModal) {
        // No existing instance, create a new one with proper configuration
        bsModal = new bootstrap.Modal(modal, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
    }

    // Show the modal
    bsModal.show();
}

/**
 * Delete a user
 */
function deleteUser(userId, modal) {
    if (DEBUG) {
        console.log('Deleting user:', userId);
    }

    try {
        showLoading(true);

        // Make sure we're using a numeric ID
        const actualUserId = parseInt(userId);

        if (isNaN(actualUserId)) {
            throw new Error('Invalid user ID for deletion: ' + userId);
        }

        const confirmButton = modal.querySelector('#confirm-delete');
        const spinner = confirmButton?.querySelector('.spinner-border');

        // Show spinner
        if (spinner) spinner.classList.remove('d-none');
        if (confirmButton) confirmButton.disabled = true;

        // Prepare the delete URL
        let url = API_ROUTES.deleteUser;
        if (!url) {
            console.error('Delete API URL not defined');
            throw new Error('API configuration error: Missing delete user URL');
        }

        // Replace the ID placeholder in the URL
        url = replaceIdInUrl(url, actualUserId);
        console.log('DELETE request to URL:', url);

        // Delete the user using a simple POST method as required by the server
        // The error message showed Method Not Allowed (Allow: POST)
        fetch(url, {
            method: 'POST', // Using POST method as the server only accepts POST
            headers: {
                'Content-Type': 'application/json'
                // Removed X-HTTP-Method-Override header which might cause issues
            },
            // Simplified body to only include the ID
            body: JSON.stringify({
                id: actualUserId
            })
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`API request failed with status ${response.status}: ${text}`);
                    });
                }

                return response.json().catch(() => {
                    // If we can't parse JSON, just return an empty success object
                    return { success: true };
                });
            })
            .then(data => {
                // Hide the modal
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();

                // Show success message
                showSuccess('User deleted successfully.');

                // Reload users
                loadUsers(currentPage);
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                showError('Failed to delete user: ' + error.message);
            })
            .finally(() => {
                showLoading(false);

                // Reset button state
                if (spinner) spinner.classList.add('d-none');
                if (confirmButton) confirmButton.disabled = false;
            });
    } catch (error) {
        console.error('Error preparing delete request:', error);
        showError('Failed to prepare delete request: ' + error.message);
        showLoading(false);

        // Reset button state
        const confirmButton = modal.querySelector('#confirm-delete-btn');
        const spinner = confirmButton?.querySelector('.spinner-border');

        if (spinner) spinner.classList.add('d-none');
        if (confirmButton) confirmButton.disabled = false;
    }
}

/**
 * Show success message
 */
function showSuccess(message) {
    // Use toastr if available
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else {
        // Show custom styled alert
        showStyledAlert(message, 'success');
    }
}

/**
 * Show error message
 */
function showError(message) {
    // Use toastr if available
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    } else {
        // Show custom styled alert
        showStyledAlert(`Error: ${message}`, 'error');
    }
}

/**
 * Show a styled custom alert
 * @param {string} message - The message to display
 * @param {string} type - The type of alert: 'success', 'error', 'warning', 'info'
 */
function showStyledAlert(message, type = 'info') {
    // Remove any existing alerts
    const existingAlerts = document.querySelectorAll('.custom-alert');
    existingAlerts.forEach(alert => alert.remove());

    // Create alert container
    const alertElement = document.createElement('div');
    alertElement.className = `custom-alert custom-alert-${type}`;

    // Set icon based on alert type
    let icon = '';
    switch (type) {
        case 'success':
            icon = '<i class="fas fa-check-circle"></i>';
            break;
        case 'error':
            icon = '<i class="fas fa-exclamation-circle"></i>';
            break;
        case 'warning':
            icon = '<i class="fas fa-exclamation-triangle"></i>';
            break;
        case 'info':
        default:
            icon = '<i class="fas fa-info-circle"></i>';
    }

    // Create alert content
    alertElement.innerHTML = `
        <div class="custom-alert-content">
            <div class="custom-alert-icon">${icon}</div>
            <div class="custom-alert-message">${message}</div>
            <button class="custom-alert-close" aria-label="Close">×</button>
        </div>
    `;

    // Add styles inline to ensure they're applied
    alertElement.style.position = 'fixed';
    alertElement.style.top = '20px';
    alertElement.style.right = '20px';
    alertElement.style.zIndex = '9999';
    alertElement.style.minWidth = '300px';
    alertElement.style.maxWidth = '450px';
    alertElement.style.padding = '15px';
    alertElement.style.borderRadius = '5px';
    alertElement.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    alertElement.style.animation = 'slideInRight 0.3s forwards';

    // Set type-specific colors
    switch (type) {
        case 'success':
            alertElement.style.backgroundColor = '#dff2e8';
            alertElement.style.color = '#28a745';
            alertElement.style.borderLeft = '4px solid #28a745';
            break;
        case 'error':
            alertElement.style.backgroundColor = '#fde8e8';
            alertElement.style.color = '#dc3545';
            alertElement.style.borderLeft = '4px solid #dc3545';
            break;
        case 'warning':
            alertElement.style.backgroundColor = '#fff3cd';
            alertElement.style.color = '#ffc107';
            alertElement.style.borderLeft = '4px solid #ffc107';
            break;
        case 'info':
        default:
            alertElement.style.backgroundColor = '#e3f2fd';
            alertElement.style.color = '#0d6efd';
            alertElement.style.borderLeft = '4px solid #0d6efd';
    }

    // Style the content
    const content = alertElement.querySelector('.custom-alert-content');
    content.style.display = 'flex';
    content.style.alignItems = 'center';

    // Style the icon
    const iconElement = alertElement.querySelector('.custom-alert-icon');
    iconElement.style.marginRight = '15px';
    iconElement.style.fontSize = '24px';

    // Style the message
    const messageElement = alertElement.querySelector('.custom-alert-message');
    messageElement.style.flex = '1';
    messageElement.style.fontSize = '14px';
    messageElement.style.fontWeight = '500';

    // Style the close button
    const closeBtn = alertElement.querySelector('.custom-alert-close');
    closeBtn.style.background = 'none';
    closeBtn.style.border = 'none';
    closeBtn.style.color = 'inherit';
    closeBtn.style.fontSize = '22px';
    closeBtn.style.cursor = 'pointer';
    closeBtn.style.marginLeft = '10px';
    closeBtn.style.opacity = '0.7';
    closeBtn.style.transition = 'opacity 0.2s';

    // Add hover effect to close button
    closeBtn.addEventListener('mouseover', () => {
        closeBtn.style.opacity = '1';
    });

    closeBtn.addEventListener('mouseout', () => {
        closeBtn.style.opacity = '0.7';
    });

    // Close button functionality
    closeBtn.addEventListener('click', () => {
        alertElement.style.animation = 'slideOutRight 0.3s forwards';
        setTimeout(() => {
            alertElement.remove();
        }, 300);
    });

    // Add to the DOM
    document.body.appendChild(alertElement);

    // Auto close after 5 seconds
    setTimeout(() => {
        if (document.body.contains(alertElement)) {
            alertElement.style.animation = 'slideOutRight 0.3s forwards';
            setTimeout(() => {
                if (document.body.contains(alertElement)) {
                    alertElement.remove();
                }
            }, 300);
        }
    }, 5000);

    // Add animation styles
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        @keyframes slideInRight {
            0% { transform: translateX(100%); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            0% { transform: translateX(0); opacity: 1; }
            100% { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(styleElement);
}

/**
 * Show the modal and ensure form submission works
 */
function showAndSetupEditModal(form, userId, modal) {
    // Get all save/submit buttons in the form
    const allButtons = modal.querySelectorAll('button');
    const saveButtons = Array.from(allButtons).filter(btn =>
        btn.textContent.toLowerCase().includes('save') ||
        btn.textContent.toLowerCase().includes('update') ||
        btn.classList.contains('btn-primary') ||
        btn.classList.contains('btn-success')
    );

    // Log found buttons
    console.log(`Found ${saveButtons.length} potential save buttons:`,
        saveButtons.map(b => `${b.textContent.trim()} (${b.className})`));

    // Add direct click handlers to all potential save buttons
    saveButtons.forEach(btn => {
        // Remove existing click listeners and create a new button
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        // Add a very direct click handler
        newBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('DIRECT SAVE BUTTON CLICK DETECTED');

            // Force submission
            directFormSubmit(form, userId);
            return false;
        });

        console.log(`Added direct click handler to button: ${newBtn.textContent.trim()}`);
    });

    // Also add direct submit handler to the form
    form.onsubmit = function (e) {
        e.preventDefault();
        console.log('DIRECT FORM SUBMIT DETECTED');
        directFormSubmit(form, userId);
        return false;
    };

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
}

/**
 * Final emergency direct form submission - specially tailored for Symfony controller requirements
 */
function directFormSubmit(form, userId) {
    console.log('⚡ FINAL EMERGENCY FORM SUBMISSION');
    showStyledAlert('Processing...', 'info');

    // Determine submission URL
    let submitUrl;
    if (userId) {
        // CRITICAL FIX: Use the correct endpoint for updates that matches your controller
        // The controller endpoint is '/admin/users/update' not '/admin/user/{id}/edit'
        submitUrl = '/admin/users/update';
        console.log(`Using correct update URL: ${submitUrl}`);
    } else {
        submitUrl = API_ROUTES.createUser;
    }

    console.log(`Submitting to URL: ${submitUrl}`);

    // Create a true HTML form for submission (bypassing AJAX completely)
    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = submitUrl;
    tempForm.style.display = 'none';

    // Get all inputs from the original form - don't use const since we'll reassign it later
    const initialData = new FormData(form);

    // Get the CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Create new FormData object with proper structure for the Symfony controller
    // CRITICAL FIX: Backend controller expects direct field names NOT nested within admin_user[field]
    let formData = new FormData();

    // Add the CSRF token
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }

    // Add user ID for edit operations - CRITICAL: Server expects id_user
    if (userId) {
        formData.append('id', userId);
        formData.append('id_user', userId); // This is what the controller looks for
    }

    // Add fields directly (NOT nested in admin_user) to match controller expectations
    const fieldsToInclude = ['name', 'email', 'phone_number', 'gender', 'date_of_birth', 'role', 'account_status', 'profilePicture', 'isVerified'];

    // Copy fields from original form data with direct field names
    for (const field of fieldsToInclude) {
        if (initialData.has(field)) {
            const value = initialData.get(field);
            // Add field directly (not in admin_user[])
            formData.append(field, value);
            console.log(`Added direct field: ${field} = ${value}`);
        }
    }

    // Add any other fields that might be present
    for (let [name, value] of initialData.entries()) {
        // Skip fields we've already processed or admin_user[] fields
        if (fieldsToInclude.includes(name) || name === '_token' || name.includes('admin_user[')) continue;

        // Add direct fields
        formData.append(name, value);
        console.log(`Added additional direct field: ${name} = ${value}`);
    }

    // Add all fields to the temp form - avoid nesting to match controller expectations
    for (let [name, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';

        // Keep the original field name (no nesting)
        input.name = name;

        // Ensure the value is never null or undefined
        if (value === null || value === undefined) {
            input.value = '';
        } else {
            // Convert any non-string values to string to avoid PHP type issues
            input.value = String(value);
        }

        tempForm.appendChild(input);
        console.log(`Added field to direct form: ${input.name} = ${input.value}`);
    }

    // Debug all form field names and values
    console.log("Form fields being submitted:");
    Array.from(tempForm.elements).forEach(el => {
        console.log(`${el.name}: ${el.value}`);
    });

    // Make sure we have id field for the Symfony controller
    if (userId) {
        // Make sure ID field is present for both Symfony admin_user[id] and standard id 
        let hasId = false;
        let hasWrappedId = false;

        for (let input of tempForm.querySelectorAll('input')) {
            if (input.name === 'id') hasId = true;
            if (input.name === 'admin_user[id]') hasWrappedId = true;
        }

        if (!hasId) {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = userId;
            tempForm.appendChild(idInput);
            console.log(`Added ID field: id = ${userId}`);
        }

        if (!hasWrappedId) {
            const wrappedIdInput = document.createElement('input');
            wrappedIdInput.type = 'hidden';
            wrappedIdInput.name = 'admin_user[id]';
            wrappedIdInput.value = userId;
            tempForm.appendChild(wrappedIdInput);
            console.log(`Added wrapped ID field: admin_user[id] = ${userId}`);
        }
    }

    // Append the form to document
    document.body.appendChild(tempForm);

    // Show message
    console.log('Submitting emergency direct form to bypass AJAX issues');

    // Set up an iframe to prevent page refresh and capture the response
    const iframe = document.createElement('iframe');
    iframe.name = 'submission_frame_' + Date.now();
    iframe.style.display = 'none';
    document.body.appendChild(iframe);

    // Set the form target to the iframe to prevent page navigation
    tempForm.target = iframe.name;

    // Add event listener to the iframe to handle the response
    iframe.addEventListener('load', function () {
        try {
            // Get the response content
            const iframeContent = iframe.contentDocument.body.innerHTML;
            console.log('Server response:', iframeContent);

            try {
                // Try to parse the response as JSON
                const jsonStart = iframeContent.indexOf('{');
                const jsonEnd = iframeContent.lastIndexOf('}') + 1;

                if (jsonStart >= 0 && jsonEnd > jsonStart) {
                    const jsonStr = iframeContent.substring(jsonStart, jsonEnd);
                    const responseData = JSON.parse(jsonStr);

                    console.log('Parsed response:', responseData);

                    if (responseData.success === true) {
                        // Handle success
                        showSuccess(responseData.message || 'User updated successfully');

                        // Close modal if open
                        const modal = form.closest('.modal');
                        if (modal) {
                            const bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }

                        // Reload user list
                        setTimeout(() => loadUsers(currentPage), 500);
                        return;
                    } else {
                        // Handle error response
                        console.error('Server returned error:', responseData.message);
                        showError(responseData.message || 'Unknown error occurred');
                        return;
                        // Handle error with structured message
                        showError(responseData.message || 'Unknown error occurred');
                        return;
                    }
                }
            } catch (jsonError) {
                console.warn('Failed to parse response as JSON:', jsonError);
            }

            // Fallback to basic content checking if JSON parsing failed
            if (iframeContent.includes('Error updating user') ||
                iframeContent.includes('Warning') ||
                iframeContent.includes('error') ||
                iframeContent.includes('failed')) {
                // Extract and show error message
                console.error('Server error response:', iframeContent);
                showError(iframeContent);
            } else if (iframeContent.includes('success')) {
                // Success case
                showSuccess('User updated successfully');

                // Close modal if open
                const modal = form.closest('.modal');
                if (modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                }

                // Reload user list
                setTimeout(() => loadUsers(currentPage), 500);
            }
        } catch (error) {
            console.error('Error handling form submission response:', error);
            showError('Failed to process server response');
        } finally {
            // Clean up
            setTimeout(() => {
                document.body.removeChild(iframe);
                document.body.removeChild(tempForm);
            }, 1000);
        }
    });

    // Submit the form targeting the iframe
    tempForm.submit();
}

/**
 * Fix for Symfony form submission with proper nesting
 * This is the original XMLHttpRequest based approach which is now deprecated
 * but we're keeping it here for reference and fallback
 */
function oldDirectFormSubmit(form, userId) {
    console.log('🔄 ORIGINAL AJAX FORM SUBMISSION - NOW DEPRECATED');
    showStyledAlert('Processing...', 'info');

    // Create XMLHttpRequest
    const xhr = new XMLHttpRequest();

    // Determine submission URL
    let submitUrl;
    if (userId) {
        submitUrl = replaceIdInUrl(API_ROUTES.updateUser, userId);
    } else {
        submitUrl = API_ROUTES.createUser;
    }

    // Configure the request
    xhr.open('POST', submitUrl, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    // Create form data
    const rawFormData = new FormData(form);
    const formData = new FormData();

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }

    // Properly nest form data for Symfony form
    for (let pair of rawFormData.entries()) {
        if (pair[0] === '_token') continue; // Skip token, already added

        // Add fields with admin_user[] prefix for Symfony
        formData.append(`admin_user[${pair[0]}]`, pair[1]);
        console.log(`Added nested field: admin_user[${pair[0]}] = ${pair[1]}`);
    }

    // Add ID for updates
    if (userId && !rawFormData.has('id')) {
        formData.append('admin_user[id]', userId);
    }

    // Disable buttons
    const buttons = form.querySelectorAll('button');
    buttons.forEach(btn => btn.disabled = true);

    // Disable modal footer buttons
    const modalFooterButtons = form.closest('.modal')?.querySelectorAll('.modal-footer button');
    if (modalFooterButtons) {
        modalFooterButtons.forEach(btn => btn.disabled = true);
    }

    // Handle response
    xhr.onload = function () {
        console.log('Response received, status:', xhr.status);
        console.log('Response type:', xhr.getResponseHeader('Content-Type'));
        console.log('First 100 chars of response:', xhr.responseText.substring(0, 100));

        // Re-enable all buttons
        buttons.forEach(btn => btn.disabled = false);
        if (modalFooterButtons) {
            modalFooterButtons.forEach(btn => btn.disabled = false);
        }

        if (xhr.status >= 200 && xhr.status < 300) {
            // Success! Hide the modal
            const modal = form.closest('.modal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();

            // Show success message
            showStyledAlert(userId ? 'User updated successfully' : 'User created successfully', 'success');

            // Reload users list
            setTimeout(() => loadUsers(currentPage), 500);
        } else {
            // Extract error message from JSON response if available
            let errorMessage = 'Server error';
            try {
                const jsonResponse = JSON.parse(xhr.responseText);
                if (jsonResponse.message) {
                    errorMessage = jsonResponse.message;
                    console.error('Error details:', errorMessage);
                }
            } catch (e) {
                console.error('Failed to parse error response:', e);
                errorMessage = `Error ${xhr.status}: Server returned an invalid response`;
            }

            showStyledAlert(`Error: ${errorMessage}`, 'error');
        }
    };

    // Handle network errors
    xhr.onerror = function () {
        console.error('Network error during form submission');
        showStyledAlert('Network error. Please check your connection and try again.', 'error');

        // Re-enable all buttons
        buttons.forEach(btn => btn.disabled = false);
        if (modalFooterButtons) {
            modalFooterButtons.forEach(btn => btn.disabled = false);
        }
    };

    // Send the form data with the proper Symfony form structure
    xhr.send(formData);
}

/**
 * Simple debounce function
 */
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

/**
 * Helper function to safely replace ID placeholders in URLs
 * Handles both {id} placeholders and literal 'USER_ID' strings
 * Also ensures the URL starts with the correct prefix (/admin)
 */
function replaceIdInUrl(url, id) {
    if (DEBUG) {
        console.log(`Replacing ID in URL - Input: ${url}, ID: ${id}`);
    }

    if (!url) return '';

    // Step 1: Ensure URL has correct /admin prefix
    let finalUrl = url;
    if (finalUrl.startsWith('/users/')) {
        finalUrl = '/admin' + finalUrl;
        if (DEBUG) {
            console.log(`Fixed URL prefix, now: ${finalUrl}`);
        }
    }

    // Step 2: Replace any placeholder patterns with the actual ID
    const placeholders = ['{id}', 'USER_ID', '__id__'];

    placeholders.forEach(placeholder => {
        if (finalUrl.includes(placeholder)) {
            finalUrl = finalUrl.replace(placeholder, id);
            if (DEBUG) {
                console.log(`Replaced placeholder ${placeholder} with ID ${id}`);
            }
        }
    });

    // Step 3: Verify there are no remaining placeholders
    const remainingPlaceholder = /{[^}]+}|USER_ID|__\w+__/g.test(finalUrl);
    if (remainingPlaceholder) {
        console.warn(`URL still contains placeholders after replacement: ${finalUrl}`);
    }

    if (DEBUG) {
        console.log(`Replacing ID in URL - Output: ${finalUrl}`);
    }

    return finalUrl;
}

/**
 * Initialize DOM elements
 */
function initializeElements() {
    elements = {
        // Stats counters
        totalUsersCount: document.getElementById('total-users-count'),
        activeUsersCount: document.getElementById('active-users-count'),
        suspendedUsersCount: document.getElementById('suspended-users-count'),
        bannedUsersCount: document.getElementById('banned-users-count'),

        // Search and filters
        searchInput: document.getElementById('user-search'),
        filterRole: document.getElementById('filter-role'),
        filterStatus: document.getElementById('filter-status'),
        filterVerified: document.getElementById('filter-verified'),
        applyFiltersBtn: document.getElementById('apply-filters-btn'),
        resetFiltersBtn: document.getElementById('reset-filters-btn'),

        // View toggles
        listViewBtn: document.getElementById('list-view-btn'),
        cardViewBtn: document.getElementById('card-view-btn'),
        listView: document.getElementById('list-view'),
        cardView: document.getElementById('card-view'),

        // User containers
        usersTableBody: document.getElementById('users-table-body'),
        usersCardContainer: document.getElementById('users-card-container'),

        // Pagination
        pageSize: document.getElementById('page-size'),
        pageSizeCards: document.getElementById('page-size-cards'),
        showingResults: document.getElementById('showing-results'),
        totalResults: document.getElementById('total-results'),
        showingResultsCards: document.getElementById('showing-results-cards'),
        totalResultsCards: document.getElementById('total-results-cards'),

        // Loading overlay
        loadingOverlay: document.getElementById('loading-overlay'),

        // Action buttons
        addUserBtn: document.getElementById('add-user-btn'),
    };

    // Log which elements were not found to help with debugging
    if (DEBUG) {
        let missingElements = [];

        for (const [key, value] of Object.entries(elements)) {
            if (!value) {
                missingElements.push(key);
            }
        }

        if (missingElements.length > 0) {
            console.warn('Missing DOM elements:', missingElements);
        }

        if (!elements.usersTableBody) {
            console.error('Critical element missing: usersTableBody (#users-table-body). Users cannot be displayed in table view.');
        }

        if (!elements.usersCardContainer) {
            console.error('Critical element missing: usersCardContainer (#users-card-container). Users cannot be displayed in card view.');
        }
    }
}




document.addEventListener('DOMContentLoaded', init);
