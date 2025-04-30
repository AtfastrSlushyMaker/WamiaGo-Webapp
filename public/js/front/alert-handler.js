/**
 * Alert Handler
 * - Handles automatic closing of alerts after a delay
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Fix any incorrectly displayed alert messages on page load
    const alertsToHide = document.querySelectorAll('.alert:not([style*="display: block"]):not(.show)');
    alertsToHide.forEach(alert => {
        alert.style.display = 'none';
    });
    
    // Hide success/error messages that don't have content
    document.querySelectorAll('.success-message, .error-message').forEach(message => {
        const contentSpan = message.querySelector('span');
        if (contentSpan && !contentSpan.textContent.trim()) {
            message.style.display = 'none';
        }
    });
    
    // Close buttons for alerts
    document.querySelectorAll('.btn-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const alert = this.closest('.alert, .success-message, .error-message');
            if (alert) {
                alert.style.display = 'none';
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds if they're currently visible
    document.querySelectorAll('.alert[style*="display: block"], .success-message[style*="display: block"], .error-message[style*="display: block"]').forEach(alert => {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    });
    
    // Handle alert display in AJAX forms
    const loginForm = document.getElementById('login-ajax-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Hide any existing alerts
            document.getElementById('login-error-container').style.display = 'none';
            document.getElementById('login-success-container').style.display = 'none';
        });
    }
    
    const registrationForm = document.getElementById('registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            // Hide any existing alerts
            document.getElementById('signup-error-message').style.display = 'none';
            document.getElementById('signup-success-message').style.display = 'none';
        });
    }
});
