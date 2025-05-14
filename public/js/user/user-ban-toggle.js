/**
 * User Ban/Unban Toggle Functionality
 * This file provides a specialized way to update user status
 * focused on just the ban/unban functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('User ban toggle initializing...');
    
    // Create a status update endpoint if it doesn't exist
    createStatusUpdateEndpoint();
    
    // Initialize
    setupToggleButtons();
});

/**
 * Create a specialized endpoint for status updates
 */
function createStatusUpdateEndpoint() {
    console.log('Setting up status update endpoint handler');
    
    // Create a hidden form to handle the status update
    const form = document.createElement('form');
    form.id = 'user-status-update-form';
    form.style.display = 'none';
    // Use POST method to match the controller's expected method
    form.method = 'POST';
    
    // Use EXACT path to match controller without any base URL manipulation
    form.action = '/admin/users/update-status'; // Exact controller route path
    
    console.log('Form action set to:', form.action, 'with method:', form.method);
    
    // Create the input fields needed
    const idField = document.createElement('input');
    idField.type = 'hidden';
    idField.name = 'id';
    idField.id = 'status-update-id';
    
    const statusField = document.createElement('input');
    statusField.type = 'hidden';
    statusField.name = 'account_status';
    statusField.id = 'status-update-status';
    
    // Add CSRF protection
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const tokenField = document.createElement('input');
        tokenField.type = 'hidden';
        tokenField.name = '_token';
        tokenField.value = csrfToken.getAttribute('content');
        form.appendChild(tokenField);
    }
    
    // Add the fields to the form
    form.appendChild(idField);
    form.appendChild(statusField);
    
    // Add the form to the document
    document.body.appendChild(form);
    
    console.log('Status update endpoint handler created with form action:', form.action);
}

/**
 * Set up toggle buttons
 */
function setupToggleButtons() {
    console.log('Setting up toggle buttons');
    
    // Find all toggle buttons
    const toggleButtons = document.querySelectorAll('.toggle-status-btn');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
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
    
    console.log('Toggle buttons set up:', toggleButtons.length);
}

/**
 * Confirm ban user
 */
function confirmBanUser(userId, name) {
    console.log('Confirming ban for user:', name);
    
    if (confirm(`Are you sure you want to ban ${name}?`)) {
        console.log('Ban confirmed for user:', name);
        banUser(userId);
    }
}

/**
 * Ban user
 */
function banUser(userId) {
    console.log('Banning user through direct endpoint:', userId);
    
    // Update the form fields
    document.getElementById('status-update-id').value = userId;
    document.getElementById('status-update-status').value = 'BANNED';
    
    // Submit through AJAX to avoid page reload
    const form = document.getElementById('user-status-update-form');
    const formData = new FormData(form);
    
    // Use EXACT Symfony controller route
    const endpoint = '/admin/users/update-status';
    
    console.log('Submitting to endpoint:', endpoint, 'with method: POST');
    
    fetch(endpoint, {
        method: 'POST', // Match the controller's expected method
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Ban response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`Ban operation failed with status ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Ban response:', text.substring(0, 200));
        
        // Show success message
        if (typeof showToast === 'function') {
            showToast('Success', 'User banned successfully', 'success');
        } else {
            alert('User banned successfully');
        }
        
        // Reload the page or update the UI
        setTimeout(() => {
            if (typeof reloadUsers === 'function') {
                reloadUsers();
            } else {
                window.location.reload();
            }
        }, 1000);
    })
    .catch(error => {
        console.error('Error during ban operation:', error);
        
        // Try fallback method - directly submitting the form
        console.log('Trying fallback method for ban operation');
        
        // Set the form to target _self
        form.target = '_self';
        form.action = endpoint;
        
        // Submit the form directly
        form.submit();
    });
}

/**
 * Confirm unban user
 */
function confirmUnbanUser(userId, name) {
    console.log('Confirming unban for user:', name);
    
    if (confirm(`Are you sure you want to unban ${name}?`)) {
        console.log('Unban confirmed for user:', name);
        unbanUser(userId);
    }
}

/**
 * Unban user
 */
function unbanUser(userId) {
    console.log('Unbanning user through direct endpoint:', userId);
    
    // Update the form fields
    document.getElementById('status-update-id').value = userId;
    document.getElementById('status-update-status').value = 'ACTIVE';
    
    // Submit through AJAX to avoid page reload
    const form = document.getElementById('user-status-update-form');
    const formData = new FormData(form);
    
    // Use EXACT Symfony controller route
    const endpoint = '/admin/users/update-status';
    
    console.log('Submitting to endpoint:', endpoint, 'with method: POST');
    
    fetch(endpoint, {
        method: 'POST', // Match the controller's expected method
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Unban response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`Unban operation failed with status ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Unban response:', text.substring(0, 200));
        
        // Show success message
        if (typeof showToast === 'function') {
            showToast('Success', 'User unbanned successfully', 'success');
        } else {
            alert('User unbanned successfully');
        }
        
        // Reload the page or update the UI
        setTimeout(() => {
            if (typeof reloadUsers === 'function') {
                reloadUsers();
            } else {
                window.location.reload();
            }
        }, 1000);
    })
    .catch(error => {
        console.error('Error during unban operation:', error);
        
        // Try fallback method - directly submitting the form
        console.log('Trying fallback method for unban operation');
        
        // Set the form to target _self
        form.target = '_self';
        form.action = endpoint;
        
        // Submit the form directly
        form.submit();
    });
} 