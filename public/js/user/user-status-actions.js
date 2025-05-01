/**
 * User Status Actions - Additional functionality for user management
 */

document.addEventListener('DOMContentLoaded', function() {
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
    
    // Ban user
    function banUser(userId) {
        const updateUrl = window.userUpdateUrl.replace('USER_ID', userId);
        
        // Show loading overlay
        document.getElementById('loading-overlay').style.display = 'flex';
        
        // Send API request
        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                account_status: 'BANNED'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showToast('Success', 'User has been banned successfully', 'success');
                
                // Reload user data
                reloadUsers();
            } else {
                showToast('Error', data.error || 'Failed to ban user', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'An unexpected error occurred', 'error');
        })
        .finally(() => {
            // Hide loading overlay
            document.getElementById('loading-overlay').style.display = 'none';
        });
    }
    
    // Unban user
    function unbanUser(userId) {
        const updateUrl = window.userUpdateUrl.replace('USER_ID', userId);
        
        // Show loading overlay
        document.getElementById('loading-overlay').style.display = 'flex';
        
        // Send API request
        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                account_status: 'ACTIVE'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showToast('Success', 'User has been unbanned successfully', 'success');
                
                // Reload user data
                reloadUsers();
            } else {
                showToast('Error', data.error || 'Failed to unban user', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'An unexpected error occurred', 'error');
        })
        .finally(() => {
            // Hide loading overlay
            document.getElementById('loading-overlay').style.display = 'none';
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
    
    // Initialize
    addStyles();
    setupStatusToggleButtons();
    
    // Expose the function to any other scripts that might need them
    window.addToggleButtonsToTable = addToggleButtonsToTable;
    window.addToggleButtonsToCards = addToggleButtonsToCards;
});
