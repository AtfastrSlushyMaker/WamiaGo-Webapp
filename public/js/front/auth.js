/**
 * WamiaGo Authentication JavaScript
 * Handles AJAX login and registration
 */

document.addEventListener('DOMContentLoaded', function () {
    initializeFormSteps();
    initializePasswordToggles();
    initializeFormSubmission();
    
    // Add specific validation for name fields
    const firstNameField = document.getElementById('reg_first_name');
    const lastNameField = document.getElementById('reg_last_name');
    
    if (firstNameField) {
        firstNameField.addEventListener('blur', function() {
            validateNameField(this, 'first name');
        });
    }
    
    if (lastNameField) {
        lastNameField.addEventListener('blur', function() {
            validateNameField(this, 'last name');
        });
    }

    console.log('Auth JS loaded successfully');
});

/**
 * Initialize multi-step registration form
 */
function initializeFormSteps() {
    const form = document.getElementById('registration-form');
    if (!form) return;

    const steps = form.querySelectorAll('.step-content');
    const indicators = document.querySelectorAll('.step-indicator');
    const nextButtons = form.querySelectorAll('.next-btn');
    const prevButtons = form.querySelectorAll('.prev-btn');

    // Initialize step buttons
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            const currentStep = this.closest('.step-content');
            const currentStepNum = parseInt(currentStep.dataset.step);
            
            // Validate current step
            if (!validateStep(currentStep)) {
                showStepError(currentStep, 'Please fill in all required fields correctly.');
                return;
            }
            
            // Move to next step
            const nextStep = document.querySelector(`.step-content[data-step="${currentStepNum + 1}"]`);
            if (nextStep) {
                currentStep.classList.remove('active');
                nextStep.classList.add('active');
                
                // Update indicators
                document.querySelector(`.step-indicator[data-step="${currentStepNum}"]`).classList.add('completed');
                document.querySelector(`.step-indicator[data-step="${currentStepNum + 1}"]`).classList.add('active');
            }
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            const currentStep = this.closest('.step-content');
            const currentStepNum = parseInt(currentStep.dataset.step);
            
            // Move to previous step
            const prevStep = document.querySelector(`.step-content[data-step="${currentStepNum - 1}"]`);
            if (prevStep) {
                currentStep.classList.remove('active');
                prevStep.classList.add('active');
                
                // Update indicators
                document.querySelector(`.step-indicator[data-step="${currentStepNum}"]`).classList.remove('active');
                document.querySelector(`.step-indicator[data-step="${currentStepNum - 1}"]`).classList.add('active');
            }
        });
    });
}

/**
 * Validate a form step
 */
function validateStep(step) {
    const inputs = step.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    // Remove any previous error messages
    const existingErrorMessages = step.querySelectorAll('.validation-error');
    existingErrorMessages.forEach(element => element.remove());
    
    inputs.forEach(input => {
        // Find the parent input group
        const inputGroup = input.closest('.input-group');
        
        if (input.value.trim() === '') {
            isValid = false;
            input.classList.add('is-invalid');
            
            if (inputGroup) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'validation-error';
                errorDiv.textContent = 'This field is required';
                inputGroup.appendChild(errorDiv);
            }
        } else {
            input.classList.remove('is-invalid');
        }
        
        // Email validation for email field
        if (input.type === 'email' && input.value.trim() !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                isValid = false;
                input.classList.add('is-invalid');
                
                if (inputGroup) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.textContent = 'Please enter a valid email address';
                    inputGroup.appendChild(errorDiv);
                }
            }
        }
        
        // Password validation
        if (input.id === 'reg_password' && input.value.trim() !== '') {
            if (input.value.length < 8) {
                isValid = false;
                input.classList.add('is-invalid');
                
                if (inputGroup) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.textContent = 'Password must be at least 8 characters long';
                    inputGroup.appendChild(errorDiv);
                }
            }
        }
        
        // Confirm password validation
        if (input.id === 'reg_confirm_password' && input.value.trim() !== '') {
            const password = document.getElementById('reg_password');
            if (password && input.value !== password.value) {
                isValid = false;
                input.classList.add('is-invalid');
                
                if (inputGroup) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.textContent = 'Passwords do not match';
                    inputGroup.appendChild(errorDiv);
                }
            }
        }
    });
    
    // Check if any radio button group is required
    const radioGroups = step.querySelectorAll('.custom-radio-buttons');
    radioGroups.forEach(group => {
        const radios = group.querySelectorAll('input[type="radio"]');
        if (radios.length > 0 && radios[0].required) {
            let radioChecked = false;
            radios.forEach(radio => {
                if (radio.checked) {
                    radioChecked = true;
                }
            });
            
            // Find the parent input group
            const inputGroup = group.closest('.input-group');
            
            if (!radioChecked) {
                isValid = false;
                group.classList.add('is-invalid');
                
                if (inputGroup) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.textContent = 'Please select an option';
                    inputGroup.appendChild(errorDiv);
                }
            } else {
                group.classList.remove('is-invalid');
            }
        }
    });
    
    return isValid;
}

/**
 * Show error message in a step
 */
function showStepError(step, message) {
    let errorContainer = step.querySelector('.step-error');
    if (!errorContainer) {
        errorContainer = document.createElement('div');
        errorContainer.className = 'step-error alert alert-danger mt-3';
        step.appendChild(errorContainer);
    }
    
    errorContainer.textContent = message;
    errorContainer.style.display = 'block';
    
    // Hide error after 3 seconds
    setTimeout(() => {
        errorContainer.style.display = 'none';
    }, 3000);
}

/**
 * Initialize password visibility toggles
 */
function initializePasswordToggles() {
    const toggles = document.querySelectorAll('.password-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.dataset.target || this.previousElementSibling.id;
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.querySelector('i').classList.remove('fa-eye');
                    this.querySelector('i').classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    this.querySelector('i').classList.remove('fa-eye-slash');
                    this.querySelector('i').classList.add('fa-eye');
                }
            }
        });
    });
}

/**
 * Initialize AJAX form submission
 */
function initializeFormSubmission() {
    // Login form submission
    const loginForm = document.getElementById('login-ajax-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const errorContainer = document.getElementById('login-error-container');
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                
                if (!response.ok) {
                    if (response.status === 419 || response.status === 422) {
                        // CSRF token error or validation error
                        return { 
                            success: false,
                            message: "CSRF token is invalid. The page will be reloaded to get a fresh token.",
                            csrf_error: true
                        };
                    }
                }
                
                // Check content type to avoid parsing HTML as JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // If the response is not JSON, handle it as an error
                    return {
                        success: false,
                        message: "An unexpected server error occurred. Please try again later.",
                        server_error: true
                    };
                }
            })
            .then(data => {
                if (!data) return;
                
                if (data.csrf_error || data.server_error) {
                    errorContainer.textContent = data.message;
                    errorContainer.style.display = 'block';
                    
                    if (data.csrf_error) {
                        // Wait a moment and reload the page to get a fresh token
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    }
                    return;
                }
                
                if (data && !data.success) {
                    errorContainer.textContent = data.message;
                    errorContainer.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                errorContainer.textContent = 'An unexpected error occurred. Please try again.';
                errorContainer.style.display = 'block';
            });
        });
    }
    
    // Registration form submission
    const registrationForm = document.getElementById('registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate all steps
            const steps = this.querySelectorAll('.step-content');
            let isValid = true;
            
            steps.forEach(step => {
                if (!validateStep(step)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                document.getElementById('signup-error-message').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('signup-error-message').style.display = 'none';
                }, 3000);
                return;
            }
            
            // Check if password and confirm password match
            const password = document.getElementById('reg_password');
            const confirmPassword = document.getElementById('reg_confirm_password');
            
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                document.getElementById('signup-error-message').textContent = 'Passwords do not match.';
                document.getElementById('signup-error-message').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('signup-error-message').style.display = 'none';
                }, 3000);
                return;
            }
            
            // Check terms agreement
            const termsCheckbox = document.getElementById('terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                document.getElementById('signup-error-message').textContent = 'You must agree to the Terms and Privacy Policy.';
                document.getElementById('signup-error-message').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('signup-error-message').style.display = 'none';
                }, 3000);
                return;
            }
            
            // Submit form via AJAX
            const formData = new FormData(this);
            
            // Add X-CSRF-Token header from the hidden input field
            const csrfToken = document.querySelector('input[name="_csrf_token"]');
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                // Important: include credentials to send cookies with the request
                credentials: 'same-origin'
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return { success: true };
                }
                
                if (!response.ok) {
                    if (response.status === 419 || response.status === 422) {
                        // CSRF token error or validation error
                        return { 
                            success: false,
                            message: "CSRF token is invalid. The page will be reloaded to get a fresh token.",
                            csrf_error: true
                        };
                    }
                }
                
                // Check content type to avoid parsing HTML as JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    // If the response is not JSON, handle it as an error
                    return {
                        success: false,
                        message: "An unexpected server error occurred. Please try again later.",
                        server_error: true
                    };
                }
            })
            .then(data => {
                if (!data) return;
                
                if (data.csrf_error || data.server_error) {
                    document.getElementById('signup-error-message').textContent = data.message;
                    document.getElementById('signup-error-message').style.display = 'block';
                    
                    if (data.csrf_error) {
                        // Wait a moment and reload the page to get a fresh token
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    }
                    return;
                }
                
                if (data.success) {
                    // Show success message
                    document.getElementById('signup-success-message').style.display = 'block';
                    document.getElementById('signup-error-message').style.display = 'none';
                    
                    // Reset form
                    this.reset();
                    
                    // Redirect after a delay
                    setTimeout(() => {
                        window.location.href = data.redirect || '/login';
                    }, 2000);
                } else {
                    // Show error message
                    const errorMessage = document.getElementById('signup-error-message');
                    errorMessage.textContent = data.message || 'Registration failed. Please check your information and try again.';
                    errorMessage.style.display = 'block';
                    
                    // Remove any previous error messages
                    const existingErrorMessages = document.querySelectorAll('.validation-error');
                    existingErrorMessages.forEach(element => element.remove());
                    
                    // Clear previous invalid states
                    const formFields = this.querySelectorAll('.form-input');
                    formFields.forEach(field => field.classList.remove('is-invalid'));
                    
                    // If there are field-specific errors, highlight them and show specific messages
                    if (data.fieldErrors) {
                        Object.keys(data.fieldErrors).forEach(fieldName => {
                            const input = this.querySelector(`[name*="${fieldName}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                
                                // Create and append error message element
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'validation-error';
                                errorDiv.textContent = data.fieldErrors[fieldName].join(' ');
                                
                                // Find the parent input group
                                const inputGroup = input.closest('.input-group');
                                if (inputGroup) {
                                    inputGroup.appendChild(errorDiv);
                                }
                            }
                        });
                    }
                    
                    // If there are general errors, also highlight them
                    if (data.errors && data.errors.length > 0) {
                        // Display general errors
                        errorMessage.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>${data.errors.join('<br>')}</span>`;
                    }
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                document.getElementById('signup-error-message').textContent = 'An unexpected error occurred. Please try again.';
                document.getElementById('signup-error-message').style.display = 'block';
            });
        });
    }
}

/**
 * Validate name field
 */
function validateNameField(field, fieldName) {
    const inputGroup = field.closest('.input-group');
    const existingError = inputGroup.querySelector('.validation-error');
    if (existingError) {
        existingError.remove();
    }
    
    if (field.value.trim() === '') {
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error';
        errorDiv.textContent = `Please enter your ${fieldName}`;
        inputGroup.appendChild(errorDiv);
        return false;
    } else if (field.value.trim().length < 2) {
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error';
        errorDiv.textContent = `${fieldName} must be at least 2 characters`;
        inputGroup.appendChild(errorDiv);
        return false;
    } else {
        field.classList.remove('is-invalid');
        return true;
    }
} 