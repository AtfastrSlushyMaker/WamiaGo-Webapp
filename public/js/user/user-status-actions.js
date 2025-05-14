/**
 * User Status Actions - Additional functionality for user management
 */

// Global variables
let updateUrlTemplate = '/admin/user/{id}/edit';

document.addEventListener('DOMContentLoaded', function() {
    // Extract the update URL template from window variables if available
    if (window.userUpdateUrl) {
        updateUrlTemplate = window.userUpdateUrl;
        console.log('Using update URL template from window:', updateUrlTemplate);
    }
    
    // Validate API URLs first
    console.log('Initializing user status actions with URLs:', {
        userUpdateUrl: window.userUpdateUrl
    });
    
    // Ensure userUpdateUrl is correctly set
    if (!window.userUpdateUrl) {
        console.error('userUpdateUrl is not defined. Ban/unban functionality will not work correctly.');
        window.userUpdateUrl = '/admin/users/{id}/edit'; // Fallback URL
    }
    
    // Add proper /admin prefix if missing
    if (window.userUpdateUrl && window.userUpdateUrl.startsWith('/users/')) {
        window.userUpdateUrl = '/admin' + window.userUpdateUrl;
        console.log('Added /admin prefix to userUpdateUrl:', window.userUpdateUrl);
    }
    
    // Inject Ban/Unban buttons into user table rows and cards
    function setupStatusToggleButtons() {
        // Only proceed if we're on the admin user management page
        if (!document.querySelector('#users-table-body') && !document.querySelector('#users-card-container')) {
            return;
        }
        
        // Add event delegation for status toggle buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.toggle-status-btn')) {
                const button = e.target.closest('.toggle-status-btn');
                const userId = button.dataset.id;
                const currentStatus = button.dataset.status;
                const userName = button.dataset.name;
                
                if (currentStatus === 'BANNED') {
                    confirmUnbanUser(userId, userName);
                } else {
                    confirmBanUser(userId, userName);
                }
            }
        });
        
        // Add event listener to refresh toggle buttons when users are loaded
        document.addEventListener('usersLoaded', function() {
            addToggleButtonsToTable();
            addToggleButtonsToCards();
        });
    }
    
    // Add toggle buttons to table rows
    function addToggleButtonsToTable() {
        const tableRows = document.querySelectorAll('#users-table-body tr');
        
        tableRows.forEach(row => {
            const actionsCell = row.querySelector('.actions-cell');
            if (!actionsCell) return;
            
            const userId = row.dataset.userId;
            if (!userId) return;
            
            const statusCell = row.querySelector('.status-cell');
            if (!statusCell) return;
            
            const currentStatus = statusCell.textContent.trim();
            const userName = row.querySelector('td:nth-child(2)').textContent.trim();
            
            // Remove existing toggle button if any
            const existingToggleBtn = actionsCell.querySelector('.toggle-status-btn');
            if (existingToggleBtn) {
                existingToggleBtn.remove();
            }
            
            // Create new toggle button
            const toggleButton = document.createElement('button');
            toggleButton.type = 'button';
            toggleButton.className = currentStatus === 'BANNED' 
                ? 'btn-action btn-success toggle-status-btn ms-2'
                : 'btn-action btn-warning toggle-status-btn ms-2';
            toggleButton.title = currentStatus === 'BANNED' ? 'Unban User' : 'Ban User';
            toggleButton.dataset.id = userId;
            toggleButton.dataset.status = currentStatus;
            toggleButton.dataset.name = userName;
            
            // Add icon
            const icon = document.createElement('i');
            icon.className = currentStatus === 'BANNED' ? 'fas fa-unlock' : 'fas fa-ban';
            toggleButton.appendChild(icon);
            
            // Add button to actions cell
            actionsCell.appendChild(toggleButton);
        });
    }
    
    // Add toggle buttons to card view
    function addToggleButtonsToCards() {
        const cards = document.querySelectorAll('.modern-user-card');
        
        cards.forEach(card => {
            const actionsContainer = card.querySelector('.modern-card-actions');
            if (!actionsContainer) return;
            
            // Find view button to extract user ID
            const viewButton = card.querySelector('.view-user');
            if (!viewButton) return;
            
            const userId = viewButton.dataset.id;
            const userName = card.querySelector('.modern-card-title').textContent.trim();
            
            // Check if card has the banned class
            const isBanned = card.classList.contains('user-banned');
            const currentStatus = isBanned ? 'BANNED' : 'ACTIVE';
            
            // Remove existing toggle button if any
            const existingToggleBtn = actionsContainer.querySelector('.toggle-status-btn');
            if (existingToggleBtn) {
                existingToggleBtn.remove();
            }
            
            // Create new toggle button
            const toggleButton = document.createElement('button');
            toggleButton.type = 'button';
            toggleButton.className = isBanned 
                ? 'action-button action-unban toggle-status-btn'
                : 'action-button action-ban toggle-status-btn';
            toggleButton.title = isBanned ? 'Unban User' : 'Ban User';
            toggleButton.dataset.id = userId;
            toggleButton.dataset.status = currentStatus;
            toggleButton.dataset.name = userName;
            
            // Add icon
            const icon = document.createElement('i');
            icon.className = isBanned ? 'fas fa-unlock' : 'fas fa-ban';
            toggleButton.appendChild(icon);
            
            // Add button to actions container
            actionsContainer.appendChild(toggleButton);
        });
    }
    
    // Confirm user ban
    function confirmBanUser(userId, userName) {
        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="confirm-ban-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Ban User</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-slash fa-3x text-danger mb-3"></i>
                                <h5>Are you sure you want to ban ${userName}?</h5>
                                <p class="text-muted">This user will be unable to log in to the platform.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirm-ban-btn">Ban User</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Append modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('confirm-ban-modal'));
        modal.show();
        
        // Handle confirm button click
        document.getElementById('confirm-ban-btn').addEventListener('click', function() {
            banUser(userId);
            modal.hide();
            
            // Remove modal from DOM after hiding
            document.getElementById('confirm-ban-modal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        });
    }
    
    // Confirm user unban
    function confirmUnbanUser(userId, userName) {
        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="confirm-unban-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Unban User</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                                <h5>Are you sure you want to unban ${userName}?</h5>
                                <p class="text-muted">This user will be able to log in to the platform again.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success" id="confirm-unban-btn">Unban User</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Append modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('confirm-unban-modal'));
        modal.show();
        
        // Handle confirm button click
        document.getElementById('confirm-unban-btn').addEventListener('click', function() {
            unbanUser(userId);
            modal.hide();
            
            // Remove modal from DOM after hiding
            document.getElementById('confirm-unban-modal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        });
    }
    
    // Fallback method using a direct form submission
    function fallbackFormSubmit(url, params, successCallback, errorCallback) {
        console.log('Using fallback form submission to:', url);
        console.log('With params:', params);
        
        // Create a hidden form
        const form = document.createElement('form');
        form.style.display = 'none';
        form.method = 'POST';
        form.action = url;
        
        // Add a hidden iframe to capture the response
        const iframe = document.createElement('iframe');
        const iframeName = 'submit_frame_' + Date.now();
        iframe.name = iframeName;
        iframe.style.display = 'none';
        
        // Add form fields for each param
        Object.entries(params).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        });
        
        // Add CSRF token if available
        const tokenElement = document.querySelector('meta[name="csrf-token"]');
        if (tokenElement) {
            const csrfToken = tokenElement.getAttribute('content');
            if (csrfToken) {
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = csrfToken;
                form.appendChild(tokenInput);
            }
        }
        
        // Set form target to the iframe
        form.target = iframeName;
        
        // Handle iframe load event
        iframe.addEventListener('load', function() {
            try {
                // Try to get response from iframe
                const iframeContent = iframe.contentDocument?.body?.innerHTML || '';
                console.log('Iframe response received:', iframeContent.substring(0, 200));
                
                // Consider any response a success unless it explicitly contains "error" or "failed"
                const isSuccess = !(iframeContent.toLowerCase().includes('error') || 
                                   iframeContent.toLowerCase().includes('failed'));
                
                if (isSuccess) {
                    successCallback();
                } else {
                    errorCallback('Form submission failed');
                }
            } catch (error) {
                console.error('Error handling iframe response:', error);
                errorCallback('Error processing response');
            } finally {
                // Clean up
                setTimeout(() => {
                    document.body.removeChild(iframe);
                    document.body.removeChild(form);
                }, 500);
            }
        });
        
        // Add elements to DOM
        document.body.appendChild(iframe);
        document.body.appendChild(form);
        
        // Submit the form
        form.submit();
        console.log('Fallback form submitted');
    }
    
    /**
     * Ban a user
     */
    function banUser(userId) {
        console.log('Banning user', userId);
        
        // Get user data from DOM
        const userData = getUserDataFromDOM(userId);
        
        // Use the EXACT controller route defined in your Symfony app
        const directEndpoint = '/admin/users/update-status';

        fetch(directEndpoint, {
            method: 'POST', // Use POST as defined in the controller annotation
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'id': userId,
                'account_status': 'BANNED'
            })
        })
        .then(response => {
            console.log('Direct endpoint response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            return response.text().then(text => {
                try {
                    // Try to parse it as JSON if possible
                    return JSON.parse(text);
                } catch (error) {
                    console.log('Response is not valid JSON, using as plain text');
                    return { success: true, message: text };
                }
            });
        })
        .then(data => {
            console.log('Ban successful with direct endpoint:', data);
            showToast('Success', 'User banned successfully', 'success');
            reloadUsers();
        })
        .catch(error => {
            console.error('Error with direct endpoint, trying fallback:', error);
            
            // Try standard update endpoint with user ID
            const updateUrl = updateUrlTemplate || '/admin/user/{id}/edit';
            const url = replaceIdInUrl(updateUrl, userId);
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'account_status': 'BANNED',
                    'name': userData?.name || '',
                    'email': userData?.email || '',
                    'phone_number': userData?.phone || '',
                    'role': userData?.role || 'CLIENT'
                })
            })
            .then(response => {
                console.log('Ban user response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Ban response:', text.substring(0, 200));
                
                if (text.includes('success') && !text.includes('error')) {
                    showToast('Success', 'User banned successfully', 'success');
                    reloadUsers();
                } else {
                    throw new Error('Error banning user with standard update');
                }
            })
            .catch(error => {
                console.error('Error banning user with standard update, trying other methods:', error);
                
                // Try the specialized endpoint as last resort
                setTimeout(() => {
                    tryStatusUpdateEndpoint(userId, 'BANNED', userData?.name)
                        .then(result => {
                            console.log('Status update endpoint succeeded:', result);
                            showToast('Success', 'User banned successfully', 'success');
                            reloadUsers();
                        })
                        .catch(error => {
                            console.error('Error with status update endpoint:', error);
                            
                            // Last resort - try the direct update approach
                            setTimeout(() => {
                                tryDirectUpdate(userId, 'BANNED', userData?.name)
                                    .then(result => {
                                        console.log('Direct update succeeded:', result);
                                        showToast('Success', 'User banned successfully', 'success');
                                        reloadUsers();
                                    })
                                    .catch(finalError => {
                                        console.error('All attempts to ban user failed:', finalError);
                                        showToast('Error', 'Failed to ban user after multiple attempts', 'error');
                                    });
                            }, 100);
                        });
                }, 100);
            });
        });
    }
    
    /**
     * Unban a user
     */
    function unbanUser(userId) {
        console.log('Unbanning user', userId);
        
        // Get user data from DOM
        const userData = getUserDataFromDOM(userId);
        
        // Use the EXACT controller route defined in your Symfony app
        const directEndpoint = '/admin/users/update-status';

        fetch(directEndpoint, {
            method: 'POST', // Use POST as defined in the controller annotation
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'id': userId,
                'account_status': 'ACTIVE'
            })
        })
        .then(response => {
            console.log('Direct endpoint response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            return response.text().then(text => {
                try {
                    // Try to parse it as JSON if possible
                    return JSON.parse(text);
                } catch (error) {
                    console.log('Response is not valid JSON, using as plain text');
                    return { success: true, message: text };
                }
            });
        })
        .then(data => {
            console.log('Unban successful with direct endpoint:', data);
            showToast('Success', 'User unbanned successfully', 'success');
            reloadUsers();
        })
        .catch(error => {
            console.error('Error with direct endpoint, trying fallback:', error);
            
            // Try standard update endpoint with user ID
            const updateUrl = updateUrlTemplate || '/admin/user/{id}/edit';
            const url = replaceIdInUrl(updateUrl, userId);
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    'account_status': 'ACTIVE',
                    'name': userData?.name || '',
                    'email': userData?.email || '',
                    'phone_number': userData?.phone || '',
                    'role': userData?.role || 'CLIENT'
                })
            })
            .then(response => {
                console.log('Unban user response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Unban response:', text.substring(0, 200));
                
                if (text.includes('success') && !text.includes('error')) {
                    showToast('Success', 'User unbanned successfully', 'success');
                    reloadUsers();
                } else {
                    throw new Error('Error unbanning user with standard update');
                }
            })
            .catch(error => {
                console.error('Error unbanning user with standard update, trying other methods:', error);
                
                // Try the specialized endpoint as last resort
                setTimeout(() => {
                    tryStatusUpdateEndpoint(userId, 'ACTIVE', userData?.name)
                        .then(result => {
                            console.log('Status update endpoint succeeded:', result);
                            showToast('Success', 'User unbanned successfully', 'success');
                            reloadUsers();
                        })
                        .catch(error => {
                            console.error('Error with status update endpoint:', error);
                            
                            // Last resort - try the direct update approach
                            setTimeout(() => {
                                tryDirectUpdate(userId, 'ACTIVE', userData?.name)
                                    .then(result => {
                                        console.log('Direct update succeeded:', result);
                                        showToast('Success', 'User unbanned successfully', 'success');
                                        reloadUsers();
                                    })
                                    .catch(finalError => {
                                        console.error('All attempts to unban user failed:', finalError);
                                        showToast('Error', 'Failed to unban user after multiple attempts', 'error');
                                    });
                            }, 100);
                        });
                }, 100);
            });
        });
    }
    
    // Show toast notification
    function showToast(title, message, type) {
        // Check if toastr is available
        if (typeof toastr !== 'undefined') {
            toastr[type](message, title);
            return;
        }
        
        // Create a simple toast if toastr is not available
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong>: ${message}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Add toast to container
        toastContainer.appendChild(toast);
        
        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
    
    // Reload users (assuming reloadUsers function exists in the main script)
    function reloadUsers() {
        if (typeof window.loadUsers === 'function') {
            window.loadUsers();
        } else {
            // If loadUsers function doesn't exist, try to dispatch a custom event
            document.dispatchEvent(new CustomEvent('reloadUsers'));
            
            // Or reload the page as a last resort
            // setTimeout(() => { window.location.reload(); }, 1000);
        }
    }
    
    // Add CSS for action buttons
    function addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .action-ban {
                background: linear-gradient(135deg, #dc2626, #991b1b);
            }
            
            .action-unban {
                background: linear-gradient(135deg, #10b981, #059669);
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Helper function to safely replace ID placeholders in URLs
     * Handles both {id} placeholders and literal 'USER_ID' strings
     */
    function replaceIdInUrl(url, id) {
        if (!url) return '';
        
        console.log(`Replacing ID in URL - Input: ${url}, ID: ${id}`);
        
        // Create a local copy of the URL to avoid modifying global variables
        let finalUrl = url;
        
        // Replace any placeholder patterns with the actual ID
        const placeholders = ['{id}', 'USER_ID', '__id__'];
        
        placeholders.forEach(placeholder => {
            if (finalUrl.includes(placeholder)) {
                finalUrl = finalUrl.replace(placeholder, id);
                console.log(`Replaced placeholder ${placeholder} with ID ${id}`);
            }
        });
        
        console.log(`Replacing ID in URL - Output: ${finalUrl}`);
        return finalUrl;
    }
    
    /**
     * Try to find user data in the DOM from various possible locations
     * This looks for user data in table rows, cards, or other UI elements
     */
    function getUserDataFromDOM(userId) {
        console.log('Looking for user data in DOM for user ID:', userId);
        
        // Try to find a table row for this user
        const rows = document.querySelectorAll('#users-table-body tr');
        let tableRow = null;
        
        // Find the row with this user ID by checking the buttons with data-id attribute
        for (const row of rows) {
            const idButton = row.querySelector(`.btn-action[data-id="${userId}"]`);
            if (idButton) {
                tableRow = row;
                break;
            }
        }
        
        if (tableRow) {
            try {
                // Extract data from the table row
                const nameElement = tableRow.querySelector('.fw-bold');
                const name = nameElement ? nameElement.textContent.trim() : '';
                
                const emailDiv = tableRow.querySelector('.fa-envelope')?.closest('div');
                const email = emailDiv ? emailDiv.textContent.replace(/\s+/g, ' ').trim() : '';
                
                const phoneDiv = tableRow.querySelector('.fa-phone-alt')?.closest('div');
                const phone = phoneDiv ? phoneDiv.textContent.replace(/\s+/g, ' ').trim() : '';
                
                const genderElement = tableRow.querySelector('.gender-badge');
                const gender = genderElement ? genderElement.textContent.trim() : 'MALE';
                
                const roleElement = tableRow.querySelector('.badge-role');
                const role = roleElement ? roleElement.textContent.trim() : 'CLIENT';
                
                console.log('Found user data in table row:', { name, email, phone, gender, role });
                
                return {
                    name: name || 'User ' + userId,
                    email: email || 'user' + userId + '@example.com',
                    phone_number: phone || '',
                    gender: gender.includes('Male') ? 'MALE' : (gender.includes('Female') ? 'FEMALE' : 'MALE'),
                    role: role || 'CLIENT'
                };
            } catch (e) {
                console.error('Error extracting data from table row:', e);
            }
        }
        
        // If not found in table, try cards
        const cards = document.querySelectorAll('.user-card-modern');
        let userCard = null;
        
        // Find the card for this user
        for (const card of cards) {
            const idButton = card.querySelector(`.action-btn[data-id="${userId}"]`);
            if (idButton) {
                userCard = card;
                break;
            }
        }
        
        if (userCard) {
            try {
                // Extract data from the card
                const nameElement = userCard.querySelector('.card-title');
                const name = nameElement ? nameElement.textContent.trim() : '';
                
                const emailElement = userCard.querySelector('.fa-envelope')?.closest('.user-detail');
                const email = emailElement ? emailElement.textContent.replace(/\s+/g, ' ').trim() : '';
                
                const phoneElement = userCard.querySelector('.fa-phone')?.closest('.user-detail');
                const phone = phoneElement ? phoneElement.textContent.replace(/\s+/g, ' ').trim() : '';
                
                const genderElement = userCard.querySelector('.fa-mars, .fa-venus, .fa-genderless')?.closest('.user-detail');
                const gender = genderElement ? genderElement.textContent.trim() : 'MALE';
                
                const roleElement = userCard.querySelector('.role-badge');
                const role = roleElement ? roleElement.textContent.trim() : 'CLIENT';
                
                console.log('Found user data in card:', { name, email, phone, gender, role });
                
                return {
                    name: name || 'User ' + userId,
                    email: email || 'user' + userId + '@example.com',
                    phone_number: phone || '',
                    gender: gender.includes('male') ? 'MALE' : (gender.includes('female') ? 'FEMALE' : 'MALE'),
                    role: role || 'CLIENT'
                };
            } catch (e) {
                console.error('Error extracting data from card:', e);
            }
        }
        
        // Last attempt - try to find the edit button and extract data from modal after clicking it
        const editButtons = document.querySelectorAll(`.edit-user[data-id="${userId}"]`);
        if (editButtons.length > 0) {
            try {
                console.log('Found edit button, attempting to extract data from existing form fields');
                
                // Look for form fields without opening modal
                const editModal = document.getElementById('edit-user-modal');
                if (editModal) {
                    const form = editModal.querySelector('form');
                    if (form) {
                        // Check if form already has the user's ID
                        const idField = form.querySelector('input[name="id"]');
                        if (idField && idField.value == userId) {
                            // Form is already populated with this user's data!
                            const nameField = form.querySelector('input[name="name"]');
                            const emailField = form.querySelector('input[name="email"]');
                            const phoneField = form.querySelector('input[name="phone_number"]');
                            const genderField = form.querySelector('select[name="gender"]');
                            const roleField = form.querySelector('select[name="role"]');
                            
                            console.log('Found user data in existing edit form');
                            
                            return {
                                name: nameField?.value || 'User ' + userId,
                                email: emailField?.value || 'user' + userId + '@example.com',
                                phone_number: phoneField?.value || '',
                                gender: genderField?.value || 'MALE',
                                role: roleField?.value || 'CLIENT'
                            };
                        }
                    }
                }
            } catch (e) {
                console.error('Error extracting data from edit form:', e);
            }
        }
        
        // If we're here, we couldn't find user data
        console.log('Could not find user data in DOM, using fallback values');
        return {
            name: 'User ' + userId,
            email: 'user' + userId + '@example.com',
            phone_number: '',
            gender: 'MALE',
            role: 'CLIENT'
        };
    }
    
    /**
     * Try direct update approach - implementation for the undefined function
     */
    function tryDirectUpdate(userId, status, userName) {
        console.log('Trying direct update approach for user:', userId, 'to status:', status);
        
        // Get user data from DOM
        const userData = getUserDataFromDOM(userId);
        
        // Construct the URL - EXACT match to what's defined in the controller
        const url = '/admin/users/update-status';
        
        console.log('Using exact controller URL:', url);
        
        // Create a form to submit
        const form = document.createElement('form');
        form.method = 'POST'; // Use POST as defined in the controller annotation
        form.action = url;
        form.style.display = 'none';
        
        // Add fields
        const addField = (name, value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        };
        
        // Add the key fields expected by the controller
        addField('id', userId);
        addField('account_status', status);
        
        // Add additional fields that might help
        if (userData) {
            if (userData.name) addField('name', userData.name);
            if (userData.email) addField('email', userData.email);
            if (userData.phone) addField('phone_number', userData.phone);
            if (userData.role) addField('role', userData.role);
        }
        
        // Add CSRF protection if available
        const tokenElement = document.querySelector('meta[name="csrf-token"]');
        if (tokenElement) {
            const token = tokenElement.getAttribute('content');
            if (token) {
                addField('_token', token);
            }
        }
        
        // Append to document
        document.body.appendChild(form);
        
        // Create hidden iframe to handle response
        const iframe = document.createElement('iframe');
        const iframeName = 'status_update_frame_' + Date.now();
        iframe.name = iframeName;
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
        
        // Set target to iframe
        form.target = iframeName;
        
        // Handle iframe load
        return new Promise((resolve, reject) => {
            iframe.addEventListener('load', function() {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    const response = doc.body.innerHTML;
                    
                    console.log('Direct update response:', response.substring(0, 200));
                    
                    // Check if the response indicates success
                    if (response.includes('success') && !response.includes('false') && !response.includes('error')) {
                        resolve({ success: true, message: 'Status updated successfully' });
                    } else {
                        reject(new Error('Failed to update status: ' + response.substring(0, 100)));
                    }
                } catch (error) {
                    reject(error);
                } finally {
                    // Clean up
                    setTimeout(() => {
                        document.body.removeChild(form);
                        document.body.removeChild(iframe);
                    }, 500);
                }
            });
            
            // Submit the form
            console.log('Direct update form submitted');
            form.submit();
        });
    }
    
    /**
     * Try to update user status using the endpoint approach
     */
    function tryStatusUpdateEndpoint(userId, accountStatus, userName) {
        console.log('Attempting endpoint update for user:', userId, 'New status:', accountStatus);
        
        // Use the exact controller route without manipulating paths
        const endpoint = '/admin/users/update-status';
        
        console.log('Using endpoint:', endpoint);
        
        // Set up formData for the request
        const formData = new FormData();
        formData.append('id', userId);
        formData.append('account_status', accountStatus);
        
        // Include user name if available
        if (userName) {
            formData.append('name', userName);
        }
        
        // Attempt direct POST to the controller
        return fetch(endpoint, {
            method: 'POST', // Use POST to match controller expectations
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Status update response code:', response.status);
            
            if (!response.ok) {
                throw new Error(`Status update failed with code ${response.status}`);
            }
            
            return response.text().then(text => {
                console.log('Status update success response:', text.substring(0, 100));
                return { success: true, message: 'Status updated successfully' };
            });
        })
        .catch(error => {
            console.error('Status update endpoint failed:', error);
            // Let the caller know this method failed
            return { success: false, message: error.message };
        });
    }
    
    /**
     * Set up initial handlers for buttons that might already exist in the DOM
     * This is useful for buttons that are created by server-side rendering
     */
    function setupInitialBanUnbanButtons() {
        console.log('Setting up initial ban/unban button handlers');
        
        // Find all toggle buttons
        const toggleButtons = document.querySelectorAll('.toggle-status-btn');
        
        if (toggleButtons.length > 0) {
            console.log(`Found ${toggleButtons.length} pre-existing toggle buttons`);
            
            toggleButtons.forEach(button => {
                // Remove any existing click handlers to avoid duplicates
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                // Add new click handler
                newButton.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const currentStatus = this.getAttribute('data-status');
                    
                    console.log('Toggle button clicked for user:', name, 'Current status:', currentStatus);
                    
                    // Confirm before action
                    if (currentStatus === 'BANNED' || currentStatus === 'banned') {
                        confirmUnbanUser(userId, name);
                    } else {
                        confirmBanUser(userId, name);
                    }
                });
            });
        } else {
            console.log('No pre-existing toggle buttons found');
        }
    }
    
    // Initialize
    addStyles();
    setupStatusToggleButtons();
    
    // Expose the function to any other scripts that might need them
    window.addToggleButtonsToTable = addToggleButtonsToTable;
    window.addToggleButtonsToCards = addToggleButtonsToCards;
    
    // Expose status change functions for use by other scripts
    window.banUser = banUser;
    window.unbanUser = unbanUser;
    window.confirmBanUser = confirmBanUser;
    window.confirmUnbanUser = confirmUnbanUser;
    
    // Call this function immediately to set up handlers for buttons that might already exist
    setupInitialBanUnbanButtons();
});
