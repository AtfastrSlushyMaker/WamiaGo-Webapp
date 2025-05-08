/**
 * Enhanced User Status Actions
 * Provides instant visual feedback and optimistic UI updates when changing user status
 */
document.addEventListener('DOMContentLoaded', function() {
    // Cache DOM selectors for better performance
    const statusCountElements = {
        total: document.getElementById('total-users-count'),
        active: document.getElementById('active-users-count'),
        suspended: document.getElementById('suspended-users-count'),
        banned: document.getElementById('banned-users-count')
    };
    
    // Action types with their corresponding status changes
    const ACTION_TYPES = {
        ACTIVATE: { newStatus: 'ACTIVE', icon: 'check-circle', color: '#28a745' },
        SUSPEND: { newStatus: 'SUSPENDED', icon: 'clock', color: '#ffc107' },
        BAN: { newStatus: 'BANNED', icon: 'ban', color: '#dc3545' }
    };
    
    // Initialize status counts from preloaded data
    function initializeStatusCounts() {
        if (window.preloadedStats) {
            statusCountElements.total.textContent = window.preloadedStats.total;
            statusCountElements.active.textContent = window.preloadedStats.active;
            statusCountElements.suspended.textContent = window.preloadedStats.suspended;
            statusCountElements.banned.textContent = window.preloadedStats.banned;
        }
    }
    
    // Update status counts when a status changes
    function updateStatusCounts(oldStatus, newStatus) {
        // Decrement old status count
        if (oldStatus && statusCountElements[oldStatus.toLowerCase()]) {
            const oldElement = statusCountElements[oldStatus.toLowerCase()];
            oldElement.textContent = (parseInt(oldElement.textContent) - 1).toString();
            
            // Add number change animation
            oldElement.classList.add('number-change');
            setTimeout(() => oldElement.classList.remove('number-change'), 1000);
        }
        
        // Increment new status count
        if (newStatus && statusCountElements[newStatus.toLowerCase()]) {
            const newElement = statusCountElements[newStatus.toLowerCase()];
            newElement.textContent = (parseInt(newElement.textContent) + 1).toString();
            
            // Add number change animation
            newElement.classList.add('number-change');
            setTimeout(() => newElement.classList.remove('number-change'), 1000);
        }
    }
    
    // Handle status change action for a user
    function handleStatusChange(userId, actionType) {
        // Find all user elements (card view and list view)
        const userCardElement = document.querySelector(`.user-card-modern[data-user-id="${userId}"]`);
        const userTableRow = document.querySelector(`tr[data-user-id="${userId}"]`);
        
        if (!userCardElement && !userTableRow) return;
        
        // Get current status before changing
        const currentStatus = userCardElement ? 
            userCardElement.getAttribute('data-status') : 
            userTableRow.getAttribute('data-status');
        
        const { newStatus, icon, color } = ACTION_TYPES[actionType];
        
        // Show temporary visual feedback
        const toast = createToast(`User status changing to ${newStatus.toLowerCase()}...`, icon, color);
        document.body.appendChild(toast);
        
        // Optimistically update UI before API call
        if (userCardElement) updateCardStatus(userCardElement, newStatus);
        if (userTableRow) updateTableRowStatus(userTableRow, newStatus);
        
        // Update status counts
        updateStatusCounts(currentStatus, newStatus);
        
        // Call API to update status
        const apiEndpoint = window.userUpdateUrl.replace('USER_ID', userId);
        
        fetch(apiEndpoint, {
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
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            return response.json();
        })
        .then(data => {
            // Success - update toast
            toast.querySelector('.toast-body').textContent = `User status updated to ${newStatus.toLowerCase()}`;
            toast.classList.add('bg-success');
            setTimeout(() => toast.remove(), 3000);
        })
        .catch(error => {
            console.error('Error updating user status:', error);
            
            // Revert optimistic UI updates
            if (userCardElement) updateCardStatus(userCardElement, currentStatus);
            if (userTableRow) updateTableRowStatus(userTableRow, currentStatus);
            
            // Revert status counts
            updateStatusCounts(newStatus, currentStatus);
            
            // Update toast to show error
            toast.querySelector('.toast-body').textContent = `Failed to update user status: ${error.message}`;
            toast.classList.add('bg-danger');
            setTimeout(() => toast.remove(), 5000);
        });
    }
    
    // Update card status with animation
    function updateCardStatus(card, newStatus) {
        const oldStatus = card.getAttribute('data-status');
        const statusBadge = card.querySelector('.status-badge');
        
        // Store old values for animation
        const oldBadgeHtml = statusBadge.innerHTML;
        const oldStatusClass = `status-${oldStatus.toLowerCase()}`;
        const newStatusClass = `status-${newStatus.toLowerCase()}`;
        
        // Animate status change
        statusBadge.style.transform = 'translateY(-10px)';
        statusBadge.style.opacity = '0';
        
        setTimeout(() => {
            // Update badge content
            statusBadge.classList.remove(oldStatusClass);
            statusBadge.classList.add(newStatusClass);
            statusBadge.innerHTML = `<i class="fas fa-${getStatusIcon(newStatus)} mr-1"></i>${newStatus}`;
            
            // Update card data attribute
            card.setAttribute('data-status', newStatus);
            
            // Animate back in
            statusBadge.style.transform = 'translateY(0)';
            statusBadge.style.opacity = '1';
        }, 300);
    }
    
    // Update table row status with animation
    function updateTableRowStatus(row, newStatus) {
        const oldStatus = row.getAttribute('data-status');
        const statusCell = row.querySelector('td.user-status');
        const statusBadge = statusCell.querySelector('.status-badge');
        
        // Store old values for animation
        const oldStatusClass = `status-${oldStatus.toLowerCase()}`;
        const newStatusClass = `status-${newStatus.toLowerCase()}`;
        
        // Animate status change
        statusBadge.style.transform = 'scale(0.8)';
        statusBadge.style.opacity = '0.5';
        
        setTimeout(() => {
            // Update badge content
            statusBadge.classList.remove(oldStatusClass);
            statusBadge.classList.add(newStatusClass);
            statusBadge.innerHTML = `<i class="fas fa-${getStatusIcon(newStatus)} mr-1"></i>${newStatus}`;
            
            // Update row data attribute
            row.setAttribute('data-status', newStatus);
            
            // Animate back in
            statusBadge.style.transform = 'scale(1)';
            statusBadge.style.opacity = '1';
        }, 300);
    }
    
    // Get appropriate icon for status
    function getStatusIcon(status) {
        switch(status) {
            case 'ACTIVE': return 'check-circle';
            case 'SUSPENDED': return 'clock';
            case 'BANNED': return 'ban';
            default: return 'question-circle';
        }
    }
    
    // Create toast notification
    function createToast(message, icon, color) {
        const toast = document.createElement('div');
        toast.className = 'toast position-fixed top-0 end-0 m-4 show';
        toast.style.zIndex = '1050';
        toast.style.minWidth = '300px';
        toast.style.borderLeft = `4px solid ${color}`;
        
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas fa-${icon} mr-2" style="color: ${color}"></i>
                <strong class="mr-auto">Status Update</strong>
                <small>just now</small>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        // Add dismissal behavior
        toast.querySelector('.close').addEventListener('click', () => toast.remove());
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
        
        return toast;
    }
    
    // Add quick status change buttons to user cards and rows
    function addQuickStatusButtons() {
        // For card view
        document.querySelectorAll('.user-card-modern').forEach(card => {
            const userId = card.getAttribute('data-user-id');
            const currentStatus = card.getAttribute('data-status');
            const actionsContainer = card.querySelector('.quick-actions');
            
            if (!actionsContainer) return;
            
            // Create quick action status buttons based on current status
            if (currentStatus !== 'ACTIVE') {
                addQuickActionButton(actionsContainer, userId, 'ACTIVATE', 'check-circle', 'Activate user');
            }
            
            if (currentStatus !== 'SUSPENDED') {
                addQuickActionButton(actionsContainer, userId, 'SUSPEND', 'clock', 'Suspend user');
            }
            
            if (currentStatus !== 'BANNED') {
                addQuickActionButton(actionsContainer, userId, 'BAN', 'ban', 'Ban user');
            }
        });
        
        // For list view
        document.querySelectorAll('tr[data-user-id]').forEach(row => {
            const userId = row.getAttribute('data-user-id');
            const currentStatus = row.getAttribute('data-status');
            const actionsCell = row.querySelector('td.user-actions');
            
            if (!actionsCell) return;
            
            // Create status action dropdown if it doesn't exist
            let dropdown = actionsCell.querySelector('.dropdown');
            if (!dropdown) {
                dropdown = document.createElement('div');
                dropdown.className = 'dropdown d-inline-block ml-1';
                dropdown.innerHTML = `
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-user-shield"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right status-actions"></div>
                `;
                actionsCell.appendChild(dropdown);
            }
            
            const dropdownMenu = dropdown.querySelector('.status-actions');
            dropdownMenu.innerHTML = ''; // Clear existing items
            
            // Add status options based on current status
            if (currentStatus !== 'ACTIVE') {
                addDropdownItem(dropdownMenu, userId, 'ACTIVATE', 'check-circle', 'Activate User', 'text-success');
            }
            
            if (currentStatus !== 'SUSPENDED') {
                addDropdownItem(dropdownMenu, userId, 'SUSPEND', 'clock', 'Suspend User', 'text-warning');
            }
            
            if (currentStatus !== 'BANNED') {
                addDropdownItem(dropdownMenu, userId, 'BAN', 'ban', 'Ban User', 'text-danger');
            }
        });
    }
    
    // Add quick action button to container
    function addQuickActionButton(container, userId, actionType, icon, tooltip) {
        const button = document.createElement('button');
        button.className = `quick-action-btn ${actionType.toLowerCase()}-btn`;
        button.setAttribute('data-toggle', 'tooltip');
        button.setAttribute('title', tooltip);
        button.innerHTML = `<i class="fas fa-${icon}"></i>`;
        
        // Set button style based on action type
        switch(actionType) {
            case 'ACTIVATE':
                button.style.color = '#28a745';
                break;
            case 'SUSPEND':
                button.style.color = '#ffc107';
                break;
            case 'BAN':
                button.style.color = '#dc3545';
                break;
        }
        
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            handleStatusChange(userId, actionType);
        });
        
        container.appendChild(button);
        
        // Initialize tooltip if Bootstrap's tooltip is available
        if (typeof $(button).tooltip === 'function') {
            $(button).tooltip();
        }
    }
    
    // Add dropdown item for status actions in list view
    function addDropdownItem(container, userId, actionType, icon, text, textClass) {
        const item = document.createElement('a');
        item.className = `dropdown-item ${textClass}`;
        item.href = '#';
        item.innerHTML = `<i class="fas fa-${icon} mr-2"></i>${text}`;
        
        item.addEventListener('click', (e) => {
            e.preventDefault();
            handleStatusChange(userId, actionType);
        });
        
        container.appendChild(item);
    }
    
    // Initialize module
    function init() {
        // Add necessary styles
        const style = document.createElement('style');
        style.textContent = `
            .number-change {
                animation: numberPulse 1s ease-in-out;
            }
            
            @keyframes numberPulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
            
            .toast {
                transition: opacity 0.5s ease;
            }
            
            .quick-action-btn.activate-btn:hover {
                background: rgba(40, 167, 69, 0.1);
            }
            
            .quick-action-btn.suspend-btn:hover {
                background: rgba(255, 193, 7, 0.1);
            }
            
            .quick-action-btn.ban-btn:hover {
                background: rgba(220, 53, 69, 0.1);
            }
            
            .status-badge {
                transition: all 0.3s ease;
            }
        `;
        document.head.appendChild(style);
        
        // Initialize counters
        initializeStatusCounts();
        
        // Setup event delegation for dynamic content
        document.addEventListener('click', function(e) {
            // Handle status action buttons that are dynamically added
            if (e.target.matches('.status-action-btn') || e.target.closest('.status-action-btn')) {
                const button = e.target.matches('.status-action-btn') ? e.target : e.target.closest('.status-action-btn');
                const userId = button.getAttribute('data-user-id');
                const actionType = button.getAttribute('data-action');
                
                if (userId && actionType) {
                    e.preventDefault();
                    handleStatusChange(userId, actionType);
                }
            }
        });
        
        // Add status buttons to initial content
        addQuickStatusButtons();
        
        // Watch for content updates and add buttons to new content
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && 
                   (mutation.target.id === 'users-card-container' || 
                    mutation.target.id === 'users-table-body')) {
                    addQuickStatusButtons();
                }
            });
        });
        
        // Observe both card container and table body
        const cardContainer = document.getElementById('users-card-container');
        const tableBody = document.getElementById('users-table-body');
        
        if (cardContainer) {
            observer.observe(cardContainer, { childList: true });
        }
        
        if (tableBody) {
            observer.observe(tableBody, { childList: true });
        }
    }
    
    // Run initialization
    init();
});
