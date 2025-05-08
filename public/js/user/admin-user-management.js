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
                <button type="button" class="btn-action btn-view view-user" data-id="${userId}" data-user-id="${userId}" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn-action btn-edit edit-user" data-id="${userId}" data-user-id="${userId}" title="Edit User">
                    <i class="fas fa-pen"></i>
                </button>
                <button type="button" class="btn-action btn-delete delete-user" data-id="${userId}" data-user-id="${userId}" data-name="${name}" title="Delete User">
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
                    <button type="button" class="btn btn-sm btn-outline-primary action-btn view-user" data-id="${user.id_user}" title="View Details">
                        <i class="fas fa-eye me-1"></i> View
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success action-btn edit-user" data-id="${user.id_user}" title="Edit User">
                        <i class="fas fa-edit me-1"></i> Edit
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger action-btn delete-user" data-id="${user.id_user}" data-name="${name}" title="Delete User">
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
    
    // Find the user in the current users array
    const user = users.find(u => u.id_user == userId);
    
    if (!user) {
        showError('User not found.');
        return;
    }
    
    // Show user details in a modal
    // (Implementation depends on your modal structure)
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
        // Get the edit modal
        const modal = document.getElementById('edit-user-modal');
        if (!modal) throw new Error('Edit modal not found');
        
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
                
                if (!user) throw new Error('User data not found in API response');
                
                // Populate the form fields
                const form = modal.querySelector('form');
                if (!form) throw new Error('Form not found');
                
                // Set the user ID in a hidden field
                const idField = form.querySelector('input[name="id"]');
                if (idField) idField.value = userId;
                
                // Update form action using helper function
                form.action = replaceIdInUrl(API_ROUTES.updateUser, userId);
                
                // Populate all available fields
                // Basic information
                populateField(form, 'name', user.name);
                populateField(form, 'email', user.email);
                populateField(form, 'phone_number', user.phone_number);
                
                // Account settings
                populateSelectField(form, 'role', user.role);
                populateSelectField(form, 'account_status', user.account_status);
                populateSelectField(form, 'gender', user.gender);
                
                // Format date properly for the date input
                if (user.date_of_birth) {
                    const dobField = form.querySelector('input[name="date_of_birth"]');
                    if (dobField) {
                        // Check if date is in ISO format or needs conversion
                        if (user.date_of_birth.includes('T')) {
                            // Already in ISO format, just take the date part
                            dobField.value = user.date_of_birth.split('T')[0];
                        } else {
                            // Try to convert from various formats
                            const parts = user.date_of_birth.split(/[-\/]/);
                            if (parts.length === 3) {
                                // Assuming yyyy-mm-dd or similar
                                const year = parts[0].length === 4 ? parts[0] : parts[2];
                                const month = parts[1].padStart(2, '0');
                                const day = (parts[0].length === 4 ? parts[2] : parts[0]).padStart(2, '0');
                                dobField.value = `${year}-${month}-${day}`;
                            } else {
                                dobField.value = user.date_of_birth;
                            }
                        }
                    }
                }
                
                // Checkboxes require special handling
                const verifiedField = form.querySelector('input[name="isVerified"]');
                if (verifiedField) {
                    verifiedField.checked = user.isVerified === true || user.isVerified === 1 || user.isVerified === '1';
                }
                
                // Setup form submission handler with AJAX
                setupFormSubmitHandler(form, userId);
                
                // Show the modal - first check if there's an existing instance
                let bsModal = bootstrap.Modal.getInstance(modal);
                
                if (!bsModal) {
                    // No existing instance, create a new one with proper configuration
                    bsModal = new bootstrap.Modal(modal, {
                        backdrop: true,  // Set to 'static' to prevent closing when clicking outside
                        keyboard: true,   // Allow ESC key to close the modal
                        focus: true       // Focus on the modal when initialized
                    });
                }
                
                // Show the modal
                bsModal.show();
                
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
    form.onsubmit = function(e) {
        e.preventDefault();
        
        showLoading(true);
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`Server returned ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            // Hide the modal
            const modal = form.closest('.modal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
            
            // Show success message
            showSuccess('User updated successfully');
            
            // Reload users to show the updated data
            loadUsers(currentPage);
        })
        .catch(error => {
            console.error('Error updating user:', error);
            showError('Failed to update user: ' + error.message);
        })
        .finally(() => {
            showLoading(false);
            if (submitBtn) submitBtn.disabled = false;
        });
    };
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
    if (!modal) return;
    
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
    
    // Set up the confirm button
    const confirmButton = modal.querySelector('#confirm-delete-btn');
    if (confirmButton) {
        // Remove existing event listeners
        const newButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newButton, confirmButton);
        
        // Add new event listener
        newButton.addEventListener('click', async () => {
            await deleteUser(userId, modal);
        });
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
async function deleteUser(userId, modal) {
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
        
        const confirmButton = modal.querySelector('#confirm-delete-btn');
        const spinner = confirmButton?.querySelector('.spinner-border');
        
        // Show spinner
        if (spinner) spinner.classList.remove('d-none');
        if (confirmButton) confirmButton.disabled = true;
        
        // Delete the user
        const url = API_ROUTES.deleteUser.replace('{id}', actualUserId);
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
        });
        
        if (!response.ok) {
            throw new Error(`API request failed with status ${response.status}`);
        }
        
        // Hide the modal
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) bsModal.hide();
        
        // Reload users
        await loadUsers(currentPage);
        
        // Show success message
        showSuccess('User deleted successfully.');
    } catch (error) {
        console.error('Error deleting user:', error);
        showError('Failed to delete user.');
    } finally {
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
    // Implement toast or alert for success message
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else {
        alert(message);
    }
}

/**
 * Show error message
 */
function showError(message) {
    // Implement toast or alert for error message
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    } else {
        alert(`Error: ${message}`);
    }
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
