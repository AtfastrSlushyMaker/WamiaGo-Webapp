/**
 * Step Navigation Fix
 * This script addresses issues with multi-step form navigation in registration form
 * It provides improved error handling, better debugging, and reliable step transitions
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Step navigation fix initialized');
    
    // Get all required form elements
    const registrationForm = document.getElementById('registration-form');
    const nextButtons = document.querySelectorAll('.next-btn');
    const prevButtons = document.querySelectorAll('.prev-btn');
    const stepContents = document.querySelectorAll('.step-content');
    const stepIndicators = document.querySelectorAll('.step-indicator');
    
    // Helper function to display error messages
    function showErrorMessage(message) {
        const errorMessage = document.getElementById('signup-error-message');
        if (errorMessage) {
            errorMessage.querySelector('span').textContent = message;
            errorMessage.style.display = 'flex';
            
            // Auto-hide after 8 seconds
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 8000);
        }
    }
    
    // Helper function to navigate to a specific step
    function navigateToStep(stepNumber) {
        console.log(`Navigating to step ${stepNumber}`);
        
        // Hide current step and show the new step
        stepContents.forEach(step => {
            const currentStep = parseInt(step.dataset.step);
            step.classList.toggle('active', currentStep === stepNumber);
        });
        
        // Update step indicators
        stepIndicators.forEach(indicator => {
            const indicatorStep = parseInt(indicator.dataset.step);
            indicator.classList.toggle('active', indicatorStep === stepNumber);
            indicator.classList.toggle('completed', indicatorStep < stepNumber);
        });
    }
    
    // Next button clicks with direct DOM navigation (no AJAX)
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            const currentStepContent = this.closest('.step-content');
            const currentStepNumber = parseInt(currentStepContent.dataset.step);
            const nextStepNumber = currentStepNumber + 1;
            
            console.log(`Attempting to move from step ${currentStepNumber} to step ${nextStepNumber}`);
            
            // Basic front-end validation to prevent empty field submission
            let isValid = true;
            const requiredFields = currentStepContent.querySelectorAll('input[required], select[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    field.addEventListener('input', function() {
                        if (this.value.trim()) {
                            this.classList.remove('is-invalid');
                        }
                    }, { once: true });
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                showErrorMessage("Please fill in all required fields");
                return;
            }
            
            // If validation passes, move to next step directly
            navigateToStep(nextStepNumber);
        });
    });
      // Previous button clicks - simple navigation, no validation needed
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            const currentStepContent = this.closest('.step-content');
            const currentStepNumber = parseInt(currentStepContent.dataset.step);
            const prevStepNumber = currentStepNumber - 1;
            
            if (prevStepNumber > 0) {
                navigateToStep(prevStepNumber);
            }
        });
    });
      // Handle form submission validation
    if (registrationForm) {
        // Get the submit button
        const submitButton = document.querySelector('.create-account-btn');
        
        // Add a special flag to track if we're attempting to submit
        let isSubmitting = false;
        
        // Add click event to the submit button to validate before submitting
        if (submitButton) {
            submitButton.addEventListener('click', function(e) {
                // Don't prevent default here - let the form handler handle the submission
                // We just want to validate the form first
                
                console.log("Create Account button clicked");
                
                // Don't allow multiple submission attempts
                if (isSubmitting) {
                    console.log("Already submitting, ignoring click");
                    return;
                }
                
                // Form validation before submission
                // Check password confirmation
                const password = document.getElementById('reg_password');
                const confirmPassword = document.getElementById('reg_confirm_password');
                const termsCheckbox = document.getElementById('terms');
                
                let isValid = true;
                
                // Validate password match
                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    document.getElementById('confirm-password-error').style.display = 'block';
                    confirmPassword.classList.add('is-invalid');
                    isValid = false;
                } else if (password && confirmPassword) {
                    document.getElementById('confirm-password-error').style.display = 'none';
                    confirmPassword.classList.remove('is-invalid');
                }
                
                // Validate terms checkbox
                if (termsCheckbox && !termsCheckbox.checked) {
                    document.getElementById('terms-error').style.display = 'block';
                    isValid = false;
                } else if (termsCheckbox) {
                    document.getElementById('terms-error').style.display = 'none';
                }
                
                // If validation fails, don't submit
                if (!isValid) {
                    console.log("Form validation failed");
                    e.preventDefault(); // Stop the form submission
                    showErrorMessage("Please fix the errors and try again");
                    return;
                }
                
                console.log("Form validation passed, proceeding with submission");
                // Let the form handler handle the actual submission
                // The form-handler.js script will take over from here
            });
        }
    }
    
    // Initialize the form to the first step
    navigateToStep(1);
});
