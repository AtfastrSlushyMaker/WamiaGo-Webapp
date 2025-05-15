/**
 * User Stats Modal Handler
 * 
 * This script handles displaying user lists in a modal when stats cards are clicked
 */
document.addEventListener('DOMContentLoaded', function () {
    // Add click handlers to stat cards
    initializeStatsCards();

    // Initialize the modal events
    initializeModalEvents();

    // Handle the View All button in the stats modal
    document.getElementById('stats-view-all-btn').addEventListener('click', function () {
        const status = document.getElementById('users-stats-modal').getAttribute('data-current-status');
        applyStatusFilter(status);
        $('#users-stats-modal').modal('hide');
    });

    // Update the dashboard stats initially
    updateDashboardStats();
});

/**
 * Generate demo users for testing when no data is available
 * @returns {Array} Array of demo user objects
 */
function generateDemoUsers() {
    const statuses = ['ACTIVE', 'SUSPENDED', 'BANNED'];
    const genders = ['MALE', 'FEMALE'];
    const roles = ['ADMIN', 'CLIENT', 'DRIVER'];

    const demoUsers = [];
    for (let i = 1; i <= 20; i++) {
        const statusIndex = i % 3;
        const isVerified = i % 5 !== 0; // 80% verified
        const genderIndex = i % 2;
        const roleIndex = i % 3;

        // Random date of birth (18-60 years old)
        const today = new Date();
        const year = today.getFullYear() - Math.floor(Math.random() * 42 + 18);
        const month = Math.floor(Math.random() * 12) + 1;
        const day = Math.floor(Math.random() * 28) + 1;
        const dob = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

        demoUsers.push({
            id: i,
            first_name: `User${i}`,
            last_name: `Demo`,
            email: `user${i}@example.com`,
            phone_number: `+1234567890${i.toString().padStart(2, '0')}`,
            date_of_birth: dob,
            gender: genders[genderIndex],
            roles: [roles[roleIndex]],
            role_name: roles[roleIndex],
            account_status: statuses[statusIndex],
            is_verified: isVerified,
            avatar: null
        });
    }

    console.log('Generated demo users:', demoUsers.length);
    return demoUsers;
}

/**
 * Initialize the modal events for proper display
 */
function initializeModalEvents() {
    // Make sure stats are displayed when modal is fully shown
    $('#users-stats-modal').on('shown.bs.modal', function (e) {
        const status = $(this).attr('data-current-status') || 'all';
        const title = $(this).find('.modal-title').text();

        console.log(`Modal shown with status: ${status}`);

        // Make sure the close button works
        $(this).find('[data-dismiss="modal"]').on('click', function () {
            $('#users-stats-modal').modal('hide');
        });
    });

    // Clean up when modal is hidden
    $('#users-stats-modal').on('hidden.bs.modal', function (e) {
        console.log('Modal hidden');
    });
}

/**
 * Initialize the stats cards with click handlers
 */
function initializeStatsCards() {
    const statsCards = document.querySelectorAll('.stat-card');

    statsCards.forEach(card => {
        card.addEventListener('click', function (event) {
            // Prevent the default action to ensure our handler works
            event.preventDefault();

            const status = this.getAttribute('data-status');
            const title = this.getAttribute('data-title');
            console.log(`Stat card clicked: ${title} (${status})`);

            // Prepare the modal first
            showUsersInModal(status, title);

            // Explicitly show the modal
            $('#users-stats-modal').modal('show');

            console.log('Showing modal...');
        });

        // Make the cards appear clickable
        card.style.cursor = 'pointer';
    });
}

/**
 * Update the dashboard stat counters
 */
function updateDashboardStats() {
    if (!window.preloadedStats) {
        console.warn('Preloaded stats not available');
        return;
    }

    try {
        // Update the stat card counts
        document.getElementById('total-users-count').textContent = window.preloadedStats.total || 0;
        document.getElementById('active-users-count').textContent = window.preloadedStats.active || 0;
        document.getElementById('suspended-users-count').textContent = window.preloadedStats.suspended || 0;
        document.getElementById('banned-users-count').textContent = window.preloadedStats.banned || 0;

        console.log('Dashboard stats updated:', window.preloadedStats);
    } catch (error) {
        console.error('Error updating dashboard stats:', error);
    }
}

/**
 * Show users filtered by status in the modal
 * 
 * @param {string} status - The user status to filter by (or 'all' for all users)
 * @param {string} title - The title to display in the modal
 */
function showUsersInModal(status, title) {
    try {
        // Set the modal title
        document.getElementById('users-stats-modal-label').textContent = title;

        // Store the current status filter for the "View All in Main Table" button
        document.getElementById('users-stats-modal').setAttribute('data-current-status', status);

        // Show loading indicator
        document.getElementById('loading-users-message').classList.remove('d-none');

        // First check if we have access to the API_ROUTES object to fetch users
        if (typeof API_ROUTES !== 'undefined') {
            // Use the API to fetch actual user data
            fetchUsersFromAPI(status);
        } else {
            // Fall back to using preloaded users (which may not be available)
            processPreloadedUsers(status);
        }

        console.log(`Modal prepared for status: ${status}`);
    } catch (error) {
        console.error('Error showing users in modal:', error);
        // Display error message
        const tableBody = document.getElementById('stats-users-table-body');
        tableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle mr-2"></i> Error loading user data. Please try again.</td></tr>';

        // Hide loading indicator
        document.getElementById('loading-users-message').classList.add('d-none');
    }
}

/**
 * Attempt to fetch users from the API
 */
function fetchUsersFromAPI(status) {
    try {
        // Setup the URL
        const apiUrl = API_ROUTES.get || '/admin/users/api';

        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Fetched users from API:', data.length || 'unknown length');
                let users = Array.isArray(data) ? data : (data.users || []);

                // Apply status filter if needed
                let filteredUsers = users;
                if (status !== 'all') {
                    filteredUsers = users.filter(user => user && user.account_status === status);
                }

                // Process the users
                populateStatsTable(filteredUsers);
                displayStatistics(filteredUsers);

                // Update the counter
                document.getElementById('stats-showing-results').textContent = filteredUsers.length;

                // Hide loading indicator
                document.getElementById('loading-users-message').classList.add('d-none');
            })
            .catch(error => {
                console.error('Error fetching users from API:', error);
                // Fall back to preloaded data
                processPreloadedUsers(status);
            });
    } catch (error) {
        console.error('Error setting up API fetch:', error);
        // Fall back to preloaded data
        processPreloadedUsers(status);
    }
}

/**
 * Process preloaded users data as fallback
 */
function processPreloadedUsers(status) {
    // Ensure preloadedUsers is available and is an array
    if (!window.preloadedUsers) {
        console.warn('Preloaded users data not available, generating demo data');
        // Create some demo data for testing
        window.preloadedUsers = generateDemoUsers();
    }

    // Convert to array if it's not already (could be a JSON object)
    let usersArray = [];
    if (!Array.isArray(window.preloadedUsers)) {
        // If it's an object with numeric keys, convert to array
        if (typeof window.preloadedUsers === 'object' && window.preloadedUsers !== null) {
            usersArray = Object.values(window.preloadedUsers);
        } else {
            console.error('Preloaded users is not in expected format:', typeof window.preloadedUsers);
            // Create demo data as fallback
            usersArray = generateDemoUsers();
        }
    } else {
        usersArray = window.preloadedUsers;
    }

    // Get users based on status filter
    let filteredUsers = [];

    if (status === 'all') {
        // All users
        filteredUsers = usersArray;
    } else {
        // Filter by status if we have users
        if (usersArray.length > 0) {
            filteredUsers = usersArray.filter(user => user && user.account_status === status);
        }
    }

    // Populate the table
    populateStatsTable(filteredUsers);

    // Update the user count in the modal footer
    document.getElementById('stats-showing-results').textContent = filteredUsers.length;

    // Calculate and display statistics
    displayStatistics(filteredUsers);

    // Hide loading indicator
    document.getElementById('loading-users-message').classList.add('d-none');

    console.log(`Processed ${filteredUsers.length} users for status: ${status}`);
}

/**
 * Calculate and display statistics for the filtered users
 * 
 * @param {Array} users - The array of user objects to display
 */
function displayStatistics(users) {
    try {
        // Make sure users is an array
        if (!Array.isArray(users)) {
            console.error('Users is not an array in displayStatistics:', typeof users);
            users = [];
        }

        // Calculate statistics
        const totalUsers = users.length;

        // Verified users
        const verifiedUsers = users.filter(user => user && user.is_verified).length;
        const verifiedPercent = totalUsers > 0 ? Math.round((verifiedUsers / totalUsers) * 100) : 0;

        // Gender counts
        const maleUsers = users.filter(user => user && user.gender && user.gender.toUpperCase() === 'MALE').length;
        const femaleUsers = users.filter(user => user && user.gender && user.gender.toUpperCase() === 'FEMALE').length;

        // Calculate average age
        let totalAge = 0;
        let usersWithAge = 0;

        users.forEach(user => {
            if (user && user.date_of_birth) {
                const birthDate = new Date(user.date_of_birth);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }

                if (!isNaN(age) && age > 0 && age < 120) { // Sanity check for valid ages
                    totalAge += age;
                    usersWithAge++;
                }
            }
        });

        const averageAge = usersWithAge > 0 ? Math.round(totalAge / usersWithAge) : '-';

        // Update the UI with statistics
        document.getElementById('stats-modal-total').textContent = totalUsers;
        document.getElementById('stats-modal-verified').textContent = verifiedUsers;
        document.getElementById('stats-modal-verified-percent').textContent = `${verifiedPercent}%`;
        document.getElementById('stats-modal-avg-age').textContent = averageAge === '-' ? '-' : `${averageAge} yrs`;
        document.getElementById('stats-modal-male-count').textContent = maleUsers;
        document.getElementById('stats-modal-female-count').textContent = femaleUsers;

        // Update colors based on verification rate
        const verifiedElement = document.getElementById('stats-modal-verified');
        if (verifiedPercent < 40) {
            verifiedElement.classList.remove('text-success');
            verifiedElement.classList.add('text-danger');
        } else if (verifiedPercent < 70) {
            verifiedElement.classList.remove('text-success');
            verifiedElement.classList.add('text-warning');
        } else {
            verifiedElement.classList.remove('text-warning', 'text-danger');
            verifiedElement.classList.add('text-success');
        }

        console.log('Statistics displayed:', {
            totalUsers,
            verifiedUsers,
            verifiedPercent,
            averageAge,
            maleUsers,
            femaleUsers
        });
    } catch (error) {
        console.error('Error displaying statistics:', error);
        // Set default values in case of error
        document.getElementById('stats-modal-total').textContent = '0';
        document.getElementById('stats-modal-verified').textContent = '0';
        document.getElementById('stats-modal-verified-percent').textContent = '0%';
        document.getElementById('stats-modal-avg-age').textContent = '-';
        document.getElementById('stats-modal-male-count').textContent = '0';
        document.getElementById('stats-modal-female-count').textContent = '0';
    }
}

/**
 * Populate the stats modal table with the filtered users
 * 
 * @param {Array} users - The array of user objects to display
 */
function populateStatsTable(users) {
    try {
        // Make sure users is an array
        if (!Array.isArray(users)) {
            console.error('Users is not an array in populateStatsTable:', typeof users);
            users = [];
        }

        const tableBody = document.getElementById('stats-users-table-body');
        tableBody.innerHTML = ''; // Clear existing rows

        if (users.length === 0) {
            // No users found
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="9" class="text-center py-4"><i class="fas fa-user-slash mr-2"></i>No users found matching this criteria</td>';
            tableBody.appendChild(row);
            return;
        }

        // Add each user to the table
        users.forEach((user, index) => {
            if (!user) {
                console.warn('Invalid user object at index', index);
                return; // Skip this iteration
            }

            const row = document.createElement('tr');

            // Format the user information, with fallbacks for missing data
            const userId = user.id || 'N/A';

            // Special handling for user names - more robust checks
            let userName = 'User';
            if (user.first_name && user.last_name) {
                userName = `${user.first_name} ${user.last_name}`;
            } else if (user.name) {
                userName = user.name;
            } else if (user.first_name) {
                userName = user.first_name;
            } else if (user.last_name) {
                userName = user.last_name;
            } else if (user.username) {
                userName = user.username;
            } else if (user.email) {
                // If no name is available, use the part of the email before @
                const emailParts = user.email.split('@');
                userName = emailParts[0];
            }

            const userEmail = user.email || 'N/A';
            const userPhone = user.phone_number || user.phone || user.phoneNumber || 'N/A';
            const userDob = formatDate(user.date_of_birth || user.dob || user.birthdate) || 'N/A';
            const userGender = user.gender || 'N/A';

            // More robust role detection
            let userRole = 'N/A';
            if (user.role_name) {
                userRole = user.role_name;
            } else if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
                userRole = user.roles[0];
            } else if (user.role) {
                userRole = user.role;
            }

            // Status with fallbacks
            const userStatus = user.account_status || user.status || 'N/A';
            const isVerified = user.is_verified || user.verified || user.isVerified ? 'Verified' : 'Not Verified';

            // Determine status indicator color
            let statusIndicatorClass = '';
            if (userStatus.toUpperCase() === 'ACTIVE') {
                statusIndicatorClass = '';
            } else if (userStatus.toUpperCase() === 'BANNED') {
                statusIndicatorClass = 'banned';
            } else if (userStatus.toUpperCase() === 'SUSPENDED') {
                statusIndicatorClass = 'suspended';
            }

            // Create the row content with enhanced design
            row.innerHTML = `
                <td class="text-center align-middle">${userId}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-container mr-3">
                            <img src="${user.avatar || user.profile_image || '/images/default-avatar.png'}" alt="Avatar" class="rounded-circle">
                            <div class="user-status-indicator ${statusIndicatorClass}"></div>
                        </div>
                        <div>
                            <div class="font-weight-bold">${userName}</div>
                            <small class="text-muted">${userRole}</small>
                        </div>
                    </div>
                </td>
                <td class="align-middle">
                    <div class="d-flex flex-column">
                        <div><i class="fas fa-envelope text-primary mr-1"></i> ${userEmail}</div>
                        <div><i class="fas fa-phone text-success mr-1"></i> ${userPhone}</div>
                    </div>
                </td>
                <td class="align-middle">${userDob}</td>
                <td class="align-middle text-center">
                    <span class="badge badge-${getGenderBadgeClass(userGender)} px-3 py-2">${userGender}</span>
                </td>
                <td class="align-middle text-center">
                    <span class="badge badge-${getRoleBadgeClass(userRole)} px-3 py-2">${userRole}</span>
                </td>
                <td class="align-middle text-center">
                    <span class="badge badge-${getStatusBadgeClass(userStatus)} px-3 py-2">${userStatus}</span>
                </td>
                <td class="align-middle text-center">
                    <span class="badge badge-${isVerified === 'Verified' ? 'success' : 'warning'} px-3 py-2">
                        <i class="fas fa-${isVerified === 'Verified' ? 'check' : 'clock'} mr-1"></i> ${isVerified}
                    </span>
                </td>
            `;

            tableBody.appendChild(row);
        });

        // Initialize any tooltips
        $('[data-toggle="tooltip"]').tooltip();

        console.log(`Populated table with ${users.length} users`);
    } catch (error) {
        console.error('Error populating stats table:', error);
        // Show error message in table
        const tableBody = document.getElementById('stats-users-table-body');
        tableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle mr-2"></i> Error loading user data. Please try again.</td></tr>';
    }
}

/**
 * Apply a status filter to the main user table and switch to list view
 * 
 * @param {string} status - The status to filter by
 */
function applyStatusFilter(status) {
    // Make sure we are in list view
    if (document.getElementById('card-view').style.display !== 'none') {
        document.getElementById('list-view-btn').click();
    }

    // Reset all filters first
    document.getElementById('reset-filters-btn').click();

    // If we have a specific status (not 'all'), set the status filter
    if (status !== 'all') {
        document.getElementById('filter-status').value = status;
        document.getElementById('apply-filters-btn').click();
    }

    // Scroll to the user table
    document.getElementById('users-table').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Format a date string for display
 * 
 * @param {string} dateString - The date string to format
 * @returns {string} - The formatted date string
 */
function formatDate(dateString) {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (e) {
        return dateString;
    }
}

/**
 * Get the appropriate badge class for a gender
 * 
 * @param {string} gender - The gender value
 * @returns {string} - The badge class
 */
function getGenderBadgeClass(gender) {
    switch (gender.toUpperCase()) {
        case 'MALE':
            return 'info';
        case 'FEMALE':
            return 'primary';
        case 'OTHER':
            return 'secondary';
        default:
            return 'light';
    }
}

/**
 * Get the appropriate badge class for a role
 * 
 * @param {string} role - The role value
 * @returns {string} - The badge class
 */
function getRoleBadgeClass(role) {
    switch (role.toUpperCase()) {
        case 'ADMIN':
        case 'ADMINISTRATOR':
        case 'ROLE_ADMIN':
            return 'danger';
        case 'DRIVER':
        case 'ROLE_DRIVER':
            return 'primary';
        case 'CLIENT':
        case 'ROLE_CLIENT':
            return 'success';
        default:
            return 'secondary';
    }
}

/**
 * Get the appropriate badge class for a status
 * 
 * @param {string} status - The status value
 * @returns {string} - The badge class
 */
function getStatusBadgeClass(status) {
    switch (status.toUpperCase()) {
        case 'ACTIVE':
            return 'success';
        case 'SUSPENDED':
            return 'warning';
        case 'BANNED':
            return 'danger';
        default:
            return 'secondary';
    }
}
