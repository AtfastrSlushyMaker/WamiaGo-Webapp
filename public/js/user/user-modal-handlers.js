/**
 * User Modal Handlers
 * Enhances form validation and submission for user modals
 */

document.addEventListener('DOMContentLoaded', function() {
    setupFormValidation();
    setupModalCleanup();
});

/**
 * Set up form validation for all user forms
 */
function setupFormValidation() {
    const forms = document.querySelectorAll('form.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Find the first invalid field and focus it
                const invalidField = form.querySelector(':invalid');
                if (invalidField) {
                    invalidField.focus();
                }
            }
            
            form.classList.add('was-validated');
        }, false);
        
        // Add custom validation for specific fields
        setupCustomValidation(form);
    });
}

/**
 * Set up custom validation for specific fields
 */
function setupCustomValidation(form) {
    // Email validation
    const emailField = form.querySelector('input[type="email"]');
    if (emailField) {
        emailField.addEventListener('input', function() {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            
            if (emailField.value && !emailRegex.test(emailField.value)) {
                emailField.setCustomValidity('Please enter a valid email address');
            } else {
                emailField.setCustomValidity('');
            }
        });
    }
    
    // Phone number validation
    const phoneField = form.querySelector('input[name="phone_number"]');
    if (phoneField) {
        phoneField.addEventListener('input', function() {
            const phoneRegex = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
            
            if (phoneField.value && !phoneRegex.test(phoneField.value)) {
                phoneField.setCustomValidity('Please enter a valid phone number');
            } else {
                phoneField.setCustomValidity('');
            }
        });
    }
    
    // Password strength validation for new users
    const passwordField = form.querySelector('input[name="password"]');
    if (passwordField) {
        const strengthMeter = document.createElement('div');
        strengthMeter.className = 'password-strength mt-1';
        strengthMeter.innerHTML = '<div class="progress" style="height: 5px;"><div class="progress-bar" role="progressbar" style="width: 0%"></div></div>';
        
        // Insert the strength meter after the password field
        passwordField.parentNode.insertBefore(strengthMeter, passwordField.nextSibling);
        
        passwordField.addEventListener('input', function() {
            const value = passwordField.value;
            let strength = 0;
            
            // Length check
            if (value.length >= 8) strength += 25;
            
            // Character type checks
            if (/[A-Z]/.test(value)) strength += 25;
            if (/[0-9]/.test(value)) strength += 25;
            if (/[^A-Za-z0-9]/.test(value)) strength += 25;
            
            // Update the strength meter
            const progressBar = strengthMeter.querySelector('.progress-bar');
            progressBar.style.width = strength + '%';
            
            // Set color based on strength
            if (strength < 50) {
                progressBar.className = 'progress-bar bg-danger';
                passwordField.setCustomValidity('Password is too weak');
            } else if (strength < 75) {
                progressBar.className = 'progress-bar bg-warning';
                passwordField.setCustomValidity('Password could be stronger');
            } else {
                progressBar.className = 'progress-bar bg-success';
                passwordField.setCustomValidity('');
            }
        });
    }
}

/**
 * Reset forms when modals are closed
 */
function setupModalCleanup() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                
                // Reset any custom validation states
                const invalidFields = form.querySelectorAll(':invalid');
                invalidFields.forEach(field => {
                    field.setCustomValidity('');
                });
                
                // Remove any password strength meters
                const strengthMeter = form.querySelector('.password-strength');
                if (strengthMeter) {
                    strengthMeter.remove();
                }
            }
        });
    });
}

/**
 * Handle form submission with AJAX
 */
function submitFormWithAjax(form, successCallback, errorCallback) {
    if (!form) return;
    
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Disable submit button and show loading state
    if (submitBtn) {
        submitBtn.disabled = true;
        const loadingSpinner = submitBtn.querySelector('.spinner-border');
        if (loadingSpinner) {
            loadingSpinner.classList.remove('d-none');
        }
    }
    
    fetch(form.action, {
        method: form.method || 'POST',
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
        if (typeof successCallback === 'function') {
            successCallback(data);
        }
        
        // Close the modal
        const modal = form.closest('.modal');
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        
        if (typeof errorCallback === 'function') {
            errorCallback(error);
        } else {
            // Show error message
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger mt-3';
            errorAlert.textContent = error.message;
            
            // Insert error message at the top of the form
            form.prepend(errorAlert);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                errorAlert.remove();
            }, 5000);
        }
    })
    .finally(() => {
        // Re-enable submit button and hide loading state
        if (submitBtn) {
            submitBtn.disabled = false;
            const loadingSpinner = submitBtn.querySelector('.spinner-border');
            if (loadingSpinner) {
                loadingSpinner.classList.add('d-none');
            }
        }
    });
}