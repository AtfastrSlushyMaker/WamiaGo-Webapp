/**
 * WamiaGo Login System - Consolidated JavaScript
 * 
 * This file contains all original functionality from the separate JS files.
 * All original features have been preserved exactly as they were in the separate files.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add CSS for smooth transitions and animations from loginSignup.js
    const styleElement = document.createElement('style');
    styleElement.textContent = `
      @keyframes step-slide-in-right {
          0% { transform: translateX(30px); opacity: 0; }
          100% { transform: translateX(0); opacity: 1; }
      }
      
      @keyframes step-slide-out-left {
          0% { transform: translateX(0); opacity: 1; }
          100% { transform: translateX(-30px); opacity: 0; }
      }
      
      @keyframes step-slide-in-left {
          0% { transform: translateX(-30px); opacity: 0; }
          100% { transform: translateX(0); opacity: 1; }
      }
      
      @keyframes step-slide-out-right {
          0% { transform: translateX(0); opacity: 1; }
          100% { transform: translateX(30px); opacity: 0; }
      }
      
      @keyframes fadeIn {
          0% { opacity: 0; }
          100% { opacity: 1; }
      }
    `;
    document.head.appendChild(styleElement);

    // =================== FROM loginSignup.js ===================
    let isAnimating = false;
    let completedSteps = [1];
    let currentStep = 1;

    // Form Toggle (Login/Register)
    const container = document.querySelector('.container');
    const registerBtn = document.querySelector('.register-btn');
    const loginBtn = document.querySelector('.login-btn');
    
    if (registerBtn) {
        registerBtn.addEventListener('click', () => {
            container.classList.add('active');
        });
    }
    
    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            container.classList.remove('active');
        });
    }
    
    // Multi-step Form Navigation
    const stepContents = document.querySelectorAll('.step-content');
    const stepIndicators = document.querySelectorAll('.step-indicator');
    const stepLines = document.querySelectorAll('.step-line');
    const nextButtons = document.querySelectorAll('.next-btn');
    const prevButtons = document.querySelectorAll('.prev-btn');
    
    // Navigation between steps with slide animation
    function navigateToStep(stepNumber, direction = 'next') {
        if (isAnimating) return;
        isAnimating = true;
        
        const currentStepContent = document.querySelector(`.step-content[data-step="${currentStep}"]`);
        const targetStepContent = document.querySelector(`.step-content[data-step="${stepNumber}"]`);
        
        // Hide current step with appropriate animation
        if (currentStepContent) {
            const outAnim = direction === 'next' ? 'step-slide-out-left' : 'step-slide-out-right';
            currentStepContent.style.animation = `${outAnim} 0.3s forwards`;
            
            setTimeout(() => {
                currentStepContent.classList.remove('active');
                currentStepContent.style.animation = '';
                
                // Show target step with appropriate animation
                if (targetStepContent) {
                    const inAnim = direction === 'next' ? 'step-slide-in-right' : 'step-slide-in-left';
                    targetStepContent.classList.add('active');
                    targetStepContent.style.animation = `${inAnim} 0.3s forwards`;
                    
                    // Update step indicators
                    stepIndicators.forEach(indicator => {
                        const indicatorStep = parseInt(indicator.getAttribute('data-step'));
                        indicator.classList.toggle('active', indicatorStep === stepNumber);
                        indicator.classList.toggle('completed', completedSteps.includes(indicatorStep));
                    });
                    
                    // Update step lines
                    stepLines.forEach((line, index) => {
                        line.classList.toggle('active', index < stepNumber - 1);
                    });
                    
                    currentStep = stepNumber;
                    isAnimating = false;
                }
            }, 300);
        } else {
            isAnimating = false;
        }
    }
    
    // Validate step fields
    function validateStep(stepElement) {
        const inputs = stepElement.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (input.value.trim() === '') {
                input.classList.add('is-invalid');
                const inputBox = input.closest('.input-box, .input-group');
                if (inputBox) {
                    const errorElement = inputBox.querySelector('.form-error');
                    if (errorElement) {
                        errorElement.style.display = 'block';
                    }
                }
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
                const inputBox = input.closest('.input-box, .input-group');
                if (inputBox) {
                    const errorElement = inputBox.querySelector('.form-error');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                }
            }
        });
        
        if (isValid && !completedSteps.includes(currentStep)) {
            completedSteps.push(currentStep);
        }
        
        return isValid;
    }
    
    // Add event listeners to Step Indicators for navigation
    stepIndicators.forEach(indicator => {
        indicator.addEventListener('click', function() {
            const step = parseInt(this.getAttribute('data-step'));
            
            // Only allow going back or to completed steps
            if (step < currentStep || completedSteps.includes(step)) {
                navigateToStep(step, step < currentStep ? 'prev' : 'next');
            }
        });
    });
    
    // Add event listeners to Next buttons
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            const stepContent = document.querySelector(`.step-content[data-step="${currentStep}"]`);
            
            if (validateStep(stepContent)) {
                navigateToStep(currentStep + 1, 'next');
            } else {
                // Add shake animation to current step indicator
                const currentIndicator = document.querySelector(`.step-indicator[data-step="${currentStep}"]`);
                currentIndicator.classList.add('shake');
                setTimeout(() => {
                    currentIndicator.classList.remove('shake');
                }, 600);
            }
        });
    });
    
    // Add event listeners to Previous buttons
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            navigateToStep(currentStep - 1, 'prev');
        });
    });
    
    // =================== FROM eye-animation-exact.js ===================
    console.log("Eye animation with text effect initialized");
    
    // Initialize all password toggle buttons
    document.querySelectorAll('.toggle-password').forEach(button => {
        // Find password input
        const input = button.previousElementSibling;
        
        // Ensure it's a password input
        if (!input || (input.type !== 'password' && input.type !== 'text')) return;
        
        // Animation state
        let busy = false;

        // Eye tracking for cursor following
        const eye = button.querySelector('.eye');
        
        // Only follow cursor when password is hidden
        document.addEventListener('mousemove', (e) => {
            if (busy || input.type !== 'password') return;
            
            // Get the input bounds
            const inputRect = input.getBoundingClientRect();
            const buttonRect = button.getBoundingClientRect();
            
            // Define the area where the eye should track
            const buffer = 50;
            if (
                e.clientX >= inputRect.left - buffer && 
                e.clientX <= inputRect.right + buffer && 
                e.clientY >= inputRect.top - buffer && 
                e.clientY <= inputRect.bottom + buffer
            ) {
                // Calculate center of button
                const buttonCenterX = buttonRect.left + buttonRect.width / 2;
                const buttonCenterY = buttonRect.top + buttonRect.height / 2;
                
                // Calculate distance from button center
                const distX = e.clientX - buttonCenterX;
                const distY = e.clientY - buttonCenterY;
                
                // Calculate movement percentage (max 30%)
                const moveX = Math.min(30, Math.max(-30, (distX / 100) * 30));
                const moveY = Math.min(30, Math.max(-30, (distY / 100) * 30));
                
                // Apply movement
                if (eye) {
                    eye.style.transition = 'none';
                    eye.style.transform = `translate(${moveX}%, ${moveY}%)`;
                    
                    // Re-enable transition after movement for smooth return
                    setTimeout(() => {
                        eye.style.transition = 'transform 0.2s ease';
                    }, 5);
                }
            } else {
                // Return to center position
                if (eye) {
                    eye.style.transition = 'transform 0.2s ease';
                    eye.style.transform = 'translate(0, 0)';
                }
            }
        });
        
        // Handle click event
        button.addEventListener('click', () => {
            // Prevent clicks during animation
            if (busy) return;
            busy = true;
            
            const isPassword = input.type === 'password';
            
            // Update button state
            button.setAttribute('aria-pressed', isPassword);
            
            // Get upper eyelid
            const lidUpper = button.querySelector('.lid--upper');
            
            if (isPassword) {
                // Show password (close eye)
                if (lidUpper) {
                    lidUpper.style.transform = 'translateY(3.5px)';
                }
                
                // Hide eye completely (will override transform)
                if (eye) {
                    eye.style.opacity = '0';
                }
                
                // Change input type after animation
                setTimeout(() => {
                    // Store original value
                    const value = input.value;
                    
                    // Change type to text
                    input.type = 'text';
                    
                    // Re-set value to trigger the CSS animation
                    input.value = value;
                    
                    // Add ripple effect
                    input.classList.add('password-reveal-animation');
                    
                    // Remove ripple class after animation
                    setTimeout(() => {
                        input.classList.remove('password-reveal-animation');
                    }, 600);
                    
                    busy = false;
                }, 200);
            } else {
                // Hide password (open eye)
                if (lidUpper) {
                    lidUpper.style.transform = '';
                }
                
                // Make eye visible again
                if (eye) {
                    eye.style.opacity = '1';
                }
                
                // Change input type after animation
                setTimeout(() => {
                    input.type = 'password';
                    busy = false;
                }, 200);
            }
        });

        // Random blinking for the eye
        if (eye) {
            setInterval(() => {
                if (Math.random() < 0.2 && !busy && input.type === 'password') {
                    const lidUpper = button.querySelector('.lid--upper');
                    if (lidUpper) {
                        lidUpper.style.transform = 'translateY(3.5px)';
                        setTimeout(() => {
                            if (input.type === 'password') {
                                lidUpper.style.transform = '';
                            }
                        }, 150);
                    }
                }
            }, 3000);
        }
    });
    
    // =================== FROM password-validator.js ===================
    // Initialize Gender Button Selection
    const genderInputs = document.querySelectorAll('input[name="registration_form[gender]"]');
    
    genderInputs.forEach(input => {
        const label = document.querySelector(`label[for="${input.id}"]`);
        
        // Set initial state if already checked
        if (input.checked && label) {
            label.classList.add('active');
        }
        
        // Add click event to label
        if (label) {
            label.addEventListener('click', function() {
                // Remove active class from all labels
                document.querySelectorAll('label[for^="gender_"]').forEach(lbl => {
                    lbl.classList.remove('active');
                });
                
                // Add active class to clicked label
                this.classList.add('active');
                
                // Check the input
                input.checked = true;
            });
        }
    });
    
    // Initialize Form Validation
    const registrationForm = document.getElementById('registration-form');
    
    if (registrationForm) {
        // Password confirmation validation
        const passwordInput = document.getElementById('reg_password');
        const confirmPasswordInput = document.getElementById('reg_confirm_password');
        const confirmPasswordError = document.getElementById('confirm-password-error');
        
        if (passwordInput && confirmPasswordInput && confirmPasswordError) {
            function validatePasswords() {
                if (confirmPasswordInput.value && confirmPasswordInput.value !== passwordInput.value) {
                    confirmPasswordInput.classList.add('is-invalid');
                    confirmPasswordError.style.display = 'block';
                    return false;
                } else {
                    confirmPasswordInput.classList.remove('is-invalid');
                    confirmPasswordError.style.display = 'none';
                    return true;
                }
            }
            
            confirmPasswordInput.addEventListener('input', validatePasswords);
            passwordInput.addEventListener('input', validatePasswords);
        }
        
        // Terms validation
        const termsCheckbox = document.getElementById('terms');
        const termsError = document.getElementById('terms-error');
        
        if (termsCheckbox && termsError) {
            termsCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    termsError.style.display = 'none';
                }
            });
        }
        
        // Handle form submission
        registrationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous messages
            const successMessage = document.getElementById('signup-success-message');
            const errorMessage = document.getElementById('signup-error-message');
            
            if (successMessage) successMessage.style.display = 'none';
            if (errorMessage) errorMessage.style.display = 'none';
            
            // Validate all steps
            let isValid = true;
            for (let step = 1; step <= 3; step++) {
                const stepContent = document.querySelector(`.step-content[data-step="${step}"]`);
                if (!stepContent) continue;
                
                const requiredFields = stepContent.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (field.value.trim() === '') {
                        field.classList.add('is-invalid');
                        isValid = false;
                    }
                });
            }
            
            // Validate passwords match
            if (confirmPasswordInput && passwordInput) {
                isValid = validatePasswords() && isValid;
            }
            
            // Validate terms acceptance
            if (termsCheckbox && !termsCheckbox.checked) {
                termsError.style.display = 'block';
                isValid = false;
            }
            
            if (!isValid) {
                // Find first step with errors
                for (let step = 1; step <= 3; step++) {
                    const stepContent = document.querySelector(`.step-content[data-step="${step}"]`);
                    if (stepContent && stepContent.querySelector('.is-invalid')) {
                        // Navigate to the step with errors
                        navigateToStep(step, step < currentStep ? 'prev' : 'next');
                        break;
                    }
                }
                
                if (errorMessage) {
                    errorMessage.style.display = 'flex';
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 5000);
                }
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating account...';
            
            // Submit form via AJAX
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    if (successMessage) {
                        successMessage.style.display = 'flex';
                    }
                    
                    // Show confetti animation
                    createConfetti();
                    
                    // Reset form
                    this.reset();
                    
                    // Reset step indicators
                    completedSteps = [1];
                    navigateToStep(1, 'prev');
                    
                    // Switch back to login panel after delay
                    setTimeout(() => {
                        if (container) {
                            container.classList.remove('active');
                        }
                        
                        if (successMessage) {
                            successMessage.style.display = 'none';
                        }
                    }, 3000);
                } else {
                    // Show error message
                    if (errorMessage) {
                        errorMessage.style.display = 'flex';
                        const errorSpan = errorMessage.querySelector('span');
                        if (errorSpan) {
                            errorSpan.textContent = data.message || 'Registration failed. Please try again.';
                        }
                        
                        setTimeout(() => {
                            errorMessage.style.display = 'none';
                        }, 5000);
                    }
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                if (errorMessage) {
                    errorMessage.style.display = 'flex';
                    const errorSpan = errorMessage.querySelector('span');
                    if (errorSpan) {
                        errorSpan.textContent = 'An error occurred. Please try again.';
                    }
                }
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
        });
    }
    
    // Initialize Login Form Ajax Submission
    const loginForm = document.getElementById('login-ajax-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous messages
            const errorContainer = document.getElementById('login-error-container');
            const successContainer = document.getElementById('login-success-container');
            
            if (errorContainer) errorContainer.style.display = 'none';
            if (successContainer) successContainer.style.display = 'none';
            
            // Client-side validation
            if (!validateLoginForm()) {
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const buttonText = submitButton.querySelector('.button-text');
            const spinner = submitButton.querySelector('.spinner-border');
            
            if (buttonText) buttonText.style.display = 'none';
            if (spinner) spinner.classList.remove('d-none');
            
            // Submit the form via AJAX
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    if (successContainer) {
                        successContainer.querySelector('.success-message').textContent = data.message || 'Login successful! Redirecting...';
                        successContainer.style.display = 'block';
                    }
                    
                    setTimeout(() => {
                        window.location.href = data.redirectUrl || '/';
                    }, 1000);
                } else {
                    if (errorContainer) {
                        errorContainer.querySelector('.error-message').textContent = data.message || 'Login failed. Please check your credentials.';
                        errorContainer.style.display = 'block';
                    }
                    
                    // Highlight invalid fields if specified
                    if (data.field === 'email') {
                        document.getElementById('email').classList.add('is-invalid');
                    } else if (data.field === 'password') {
                        document.getElementById('password').classList.add('is-invalid');
                    }
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                if (errorContainer) {
                    errorContainer.querySelector('.error-message').textContent = 'An error occurred. Please try again.';
                    errorContainer.style.display = 'block';
                }
            })
            .finally(() => {
                // Reset button state
                if (buttonText) buttonText.style.display = 'block';
                if (spinner) spinner.classList.add('d-none');
            });
        });
        
        // Helper function to validate form
        function validateLoginForm() {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            let isValid = true;
            
            // Reset validation state
            if (emailField) emailField.classList.remove('is-invalid');
            if (passwordField) passwordField.classList.remove('is-invalid');
            
            // Validate email
            if (emailField) {
                if (!emailField.value.trim()) {
                    emailField.classList.add('is-invalid');
                    isValid = false;
                } else if (!validateEmail(emailField.value)) {
                    emailField.classList.add('is-invalid');
                    isValid = false;
                }
            }
            
            // Validate password
            if (passwordField && !passwordField.value.trim()) {
                passwordField.classList.add('is-invalid');
                isValid = false;
            }
            
            return isValid;
        }
        
        // Email validation helper
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    }
    
    // Initialize Date Picker if it exists
    if (typeof flatpickr === 'function') {
        flatpickr("#reg_date_of_birth", {
            dateFormat: "Y-m-d",
            maxDate: "today",
            minDate: new Date().getFullYear() - 120 + "-01-01",
            allowInput: true,
            altInput: true,
            altFormat: "F j, Y",
            disableMobile: true
        });
    }
});

/**
 * Create confetti animation effect
 */
function createConfetti() {
    const container = document.createElement('div');
    container.className = 'confetti-container';
    document.body.appendChild(container);
    
    const colors = ['#FF5252', '#4CAF50', '#2196F3', '#FFC107', '#9C27B0', '#FF9800'];
    
    // Create 50 confetti pieces
    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        
        // Random properties
        const color = colors[Math.floor(Math.random() * colors.length)];
        const size = Math.random() * 10 + 5;
        const shape = Math.random() > 0.5 ? 'circle' : 'square';
        
        // Apply styles
        confetti.style.backgroundColor = color;
        confetti.style.width = `${size}px`;
        confetti.style.height = `${size}px`;
        confetti.style.borderRadius = shape === 'circle' ? '50%' : '0';
        confetti.style.left = `${Math.random() * 100}vw`;
        
        // Apply animation
        const duration = Math.random() * 3 + 2;
        confetti.style.animation = `confetti-fall ${duration}s ease-in forwards`;
        
        // Add to container
        container.appendChild(confetti);
    }
    
    // Remove container after animation completes
    setTimeout(() => {
        document.body.removeChild(container);
    }, 5000);
}
