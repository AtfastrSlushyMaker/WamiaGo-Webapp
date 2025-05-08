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
        firstNameField.addEventListener('blur', function () {
            validateNameField(this, 'first name');
        });
    }

    if (lastNameField) {
        lastNameField.addEventListener('blur', function () {
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
        button.addEventListener('click', function () {
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
        button.addEventListener('click', function () {
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

    // First remove any existing validation timers
    if (step.validationTimer) {
        clearTimeout(step.validationTimer);
        step.validationTimer = null;
    }

    // Properly clean up all previous error messages
    const existingErrorMessages = step.querySelectorAll('.validation-error');
    existingErrorMessages.forEach(element => element.remove());

    // Remove invalid state from all inputs
    step.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });

    inputs.forEach(input => {
        // Find the parent input box or group
        const inputBox = input.closest('.input-box') || input.closest('.input-group');

        if (input.value.trim() === '') {
            isValid = false;
            input.classList.add('is-invalid');

            if (inputBox && !inputBox.querySelector('.validation-error')) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'validation-error';
                errorDiv.textContent = 'This field is required';
                inputBox.appendChild(errorDiv);

                // Add event listener to clear error when input changes
                input.addEventListener('input', function clearError() {
                    if (this.value.trim() !== '') {
                        this.classList.remove('is-invalid');
                        const errorEl = inputBox.querySelector('.validation-error');
                        if (errorEl) {
                            errorEl.classList.add('fade-out');
                            setTimeout(() => {
                                if (errorEl.parentNode) {
                                    errorEl.remove();
                                }
                            }, 500);
                        }
                        input.removeEventListener('input', clearError); // Remove listener after clearing error
                    }
                });
            }
        }

        // Email validation for email field
        if (input.type === 'email' && input.value.trim() !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                isValid = false;
                input.classList.add('is-invalid');

                if (inputBox && !inputBox.querySelector('.validation-error')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.textContent = 'Please enter a valid email address';
                    inputBox.appendChild(errorDiv);
                }
            }
        }

        // Password validation
        if (input.id === 'reg_password' && input.value.trim() !== '') {
            if (input.value.length < 8) {
                isValid = false;
                input.classList.add('is-invalid');

                if (inputBox && !inputBox.querySelector('.validation-error')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.textContent = 'Password must be at least 8 characters long';
                    inputBox.appendChild(errorDiv);
                }
            }
        }

        // Confirm password validation
        if (input.id === 'reg_confirm_password' && input.value.trim() !== '') {
            const password = document.getElementById('reg_password');
            if (password && input.value !== password.value) {
                isValid = false;
                input.classList.add('is-invalid');

                if (inputBox && !inputBox.querySelector('.validation-error')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error';
                    errorDiv.textContent = 'Passwords do not match';
                    inputBox.appendChild(errorDiv);
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

                if (inputGroup && !inputGroup.querySelector('.validation-error')) {
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

    // Set a timeout to remove all validation errors after 6 seconds
    if (!isValid) {
        setTimeout(() => {
            const errorMessages = step.querySelectorAll('.validation-error');
            errorMessages.forEach(element => {
                // Add fade-out class instead of just setting opacity
                element.classList.add('fade-out');
                // Wait for the animation to complete before removing
                setTimeout(() => {
                    if (element.parentNode) {
                        element.remove();
                    }
                }, 500); // Match this to the CSS transition duration
            });

            // Also remove the invalid state from inputs after timeout
            step.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
        }, 6000);
    }

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
        toggle.addEventListener('click', function () {
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

// Form validation rules
const validationRules = {
    required: (value) => value.trim() !== '',
    email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
    minLength: (value, length) => value.length >= parseInt(length),
};

// Validation error messages
const defaultMessages = {
    required: 'This field is required',
    email: 'Please enter a valid email address',
    minLength: (length) => `Must be at least ${length} characters long`
};

function validateField(input) {
    const validations = input.dataset.validation?.split('|') || [];
    const customMessage = input.dataset.validationMessage;
    
    for (const validation of validations) {
        const [rule, param] = validation.split(':');
        const isValid = validationRules[rule](input.value, param);
        
        if (!isValid) {
            input.classList.add('is-invalid');
            const feedback = input.parentElement.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = customMessage || (param ? defaultMessages[rule](param) : defaultMessages[rule]);
            }
            return false;
        }
    }
    
    input.classList.remove('is-invalid');
    const feedback = input.parentElement.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.textContent = '';
    }
    return true;
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[data-validation]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function showError(message, container = null) {
    const errorContainer = container || document.getElementById('login-error-container');
    if (errorContainer) {
        const messageElement = errorContainer.querySelector('.error-message') || errorContainer;
        messageElement.textContent = message;
        errorContainer.style.display = 'block';
        
        // Scroll error into view
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function hideError(container = null) {
    const errorContainer = container || document.getElementById('login-error-container');
    if (errorContainer) {
        errorContainer.style.display = 'none';
    }
}

function togglePasswordVisibility(icon) {
    const input = icon.previousElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bxs-lock-alt');
        icon.classList.add('bxs-lock-open-alt');
    } else {
        input.type = 'password';
        icon.classList.remove('bxs-lock-open-alt');
        icon.classList.add('bxs-lock-alt');
    }
}

function setLoading(form, isLoading) {
    const button = form.querySelector('button[type="submit"]');
    const spinner = button.querySelector('.spinner-border');
    const buttonText = button.querySelector('.button-text');
    
    if (isLoading) {
        button.disabled = true;
        spinner.classList.remove('d-none');
        buttonText.classList.add('d-none');
    } else {
        button.disabled = false;
        spinner.classList.add('d-none');
        buttonText.classList.remove('d-none');
    }
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[novalidate]');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[data-validation]');
        
        inputs.forEach(input => {
            input.addEventListener('input', () => validateField(input));
            input.addEventListener('blur', () => validateField(input));
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
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            hideError();

            // Reset validation state
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            if (emailField) emailField.classList.remove('is-invalid');
            if (passwordField) passwordField.classList.remove('is-invalid');

            // Validate form
            if (!validateForm(this)) {
                return;
            }

            setLoading(this, true);
            const formData = new FormData(this);

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

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        if (!response.ok) {
                            throw {
                                message: data.message || 'An error occurred during login',
                                errorCode: data.error_code || 'unknown_error',
                                field: data.field || null
                            };
                        }
                        return data;
                    });
                } else if (!response.ok) {
                    throw {
                        message: 'An error occurred during login',
                        errorCode: 'unknown_error',
                        field: null
                    };
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                
                // Show error message
                showError(error.message || 'An error occurred during login. Please try again.');
                
                // Highlight the specific field if provided
                if (error.field === 'email' && emailField) {
                    emailField.classList.add('is-invalid');
                    emailField.focus();
                } else if (error.field === 'password' && passwordField) {
                    passwordField.classList.add('is-invalid');
                    passwordField.focus();
                }
                
                // Add specific guidance based on error code
                if (error.errorCode === 'invalid_email_format') {
                    // Show email format guidance
                    showFieldError(emailField, 'Please enter a valid email address (e.g., user@example.com)');
                } else if (error.errorCode === 'email_not_found' || error.errorCode === 'user_not_found') {
                    // Show account creation suggestion
                    const errorContainer = document.getElementById('login-error-container');
                    if (errorContainer) {
                        const signupLink = document.createElement('a');
                        signupLink.href = '#';
                        signupLink.textContent = 'Create a new account';
                        signupLink.onclick = function(e) {
                            e.preventDefault();
                            document.querySelector('.container').classList.add('active');
                        };
                        
                        const suggestionElement = document.createElement('div');
                        suggestionElement.className = 'error-suggestion';
                        suggestionElement.innerHTML = 'Don\'t have an account? ';
                        suggestionElement.appendChild(signupLink);
                        
                        const existingSuggestion = errorContainer.querySelector('.error-suggestion');
                        if (existingSuggestion) {
                            existingSuggestion.remove();
                        }
                        
                        errorContainer.appendChild(suggestionElement);
                    }
                } else if (error.errorCode === 'too_many_attempts') {
                    // Show countdown or additional information for locked accounts
                    const errorContainer = document.getElementById('login-error-container');
                    if (errorContainer) {
                        const suggestionElement = document.createElement('div');
                        suggestionElement.className = 'error-suggestion';
                        suggestionElement.innerHTML = 'For security reasons, your account is temporarily locked. Try again after one hour or use the password recovery option.';
                        
                        const existingSuggestion = errorContainer.querySelector('.error-suggestion');
                        if (existingSuggestion) {
                            existingSuggestion.remove();
                        }
                        
                        errorContainer.appendChild(suggestionElement);
                    }
                }
            })
            .finally(() => {
                setLoading(this, false);
            });
        });
    }

    // Registration form submission
    const registrationForm = document.getElementById('registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function (e) {
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

/**
 * Show field-specific error
 */
function showFieldError(inputElement, message) {
    if (!inputElement) return;
    
    // Find the parent input box or group
    const inputBox = inputElement.closest('.input-box') || inputElement.closest('.input-group');
    if (!inputBox) return;
    
    // Remove existing error message
    const existingError = inputBox.querySelector('.validation-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Create new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'validation-error';
    errorDiv.textContent = message;
    inputBox.appendChild(errorDiv);
    
    // Add event listener to clear error when input changes
    inputElement.addEventListener('input', function clearError() {
        inputElement.classList.remove('is-invalid');
        const errorEl = inputBox.querySelector('.validation-error');
        if (errorEl) {
            errorEl.classList.add('fade-out');
            setTimeout(() => {
                if (errorEl.parentNode) {
                    errorEl.remove();
                }
            }, 500);
        }
        inputElement.removeEventListener('input', clearError);
    });
}