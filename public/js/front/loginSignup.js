document.addEventListener('DOMContentLoaded', function () {
  let isAnimating = false;
  let completedSteps = [1];
  let currentStep = 1;

  // Add CSS for smooth transitions and animations
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
      
      @keyframes shake-horizontal {
          0%, 100% { transform: translateX(0); }
          10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
          20%, 40%, 60%, 80% { transform: translateX(5px); }
      }
      
      .shake-error {
          animation: shake-horizontal 0.6s ease;
      }
      
      @keyframes confetti-fall {
          0% {
              transform: translateY(-100vh) rotate(0deg);
              opacity: 1;
          }
          100% {
              transform: translateY(100vh) rotate(360deg);
              opacity: 0;
          }
      }
      
      .confetti-container {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          z-index: 9999;
          pointer-events: none;
      }
      
      .confetti {
          position: absolute;
          width: 10px;
          height: 10px;
          top: -10px;
          border-radius: 50%;
          animation: confetti-fall linear forwards;
      }
      
      .shake-animation {
          animation: shake 0.5s ease-in-out;
      }
      
      @keyframes shake {
          0%, 100% { transform: translateX(0); }
          10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
          20%, 40%, 60%, 80% { transform: translateX(5px); }
      }
      
      .success-animation {
          animation: success-pulse 1s ease-in-out;
      }
      
      @keyframes success-pulse {
          0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
          50% { transform: scale(1.02); box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
          100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
      }
      
      .error-animation {
          animation: shake 0.5s ease-in-out;
      }
      
      .pulse-animation {
          animation: pulse 0.5s ease-in-out;
      }
      
      @keyframes pulse {
          0% { transform: scale(1); }
          50% { transform: scale(1.2); }
          100% { transform: scale(1); }
      }
      
      .input-tooltip {
          position: absolute;
          bottom: -30px;
          left: 0;
          background-color: #f44336;
          color: white;
          padding: 5px 10px;
          border-radius: 4px;
          font-size: 0.8rem;
          z-index: 10;
          animation: fadeIn 0.3s ease;
      }
      
      .input-tooltip:before {
          content: '';
          position: absolute;
          top: -5px;
          left: 20px;
          border-width: 0 5px 5px 5px;
          border-style: solid;
          border-color: transparent transparent #f44336 transparent;
      }

      .field-error {
          color: #f44336;
          font-size: 0.8rem;
          margin-top: 5px;
          padding: 5px 10px;
          border-radius: 4px;
          background-color: rgba(244, 67, 54, 0.1);
          display: flex;
          align-items: center;
          gap: 5px;
          animation: fadeIn 0.3s ease;
      }

      .field-error i {
          font-size: 14px;
      }

      .is-invalid {
          border-color: #f44336 !important;
          box-shadow: 0 0 0 1px #f44336 !important;
      }

      .error-message, .success-message {
          padding: 10px 15px;
          border-radius: 5px;
          margin: 10px 0;
          display: none;
          align-items: center;
          gap: 10px;
          animation: fadeIn 0.3s ease;
      }

      .error-message {
          background-color: rgba(244, 67, 54, 0.1);
          color: #f44336;
          border: 1px solid rgba(244, 67, 54, 0.2);
      }

      .success-message {
          background-color: rgba(76, 175, 80, 0.1);
          color: #4caf50;
          border: 1px solid rgba(76, 175, 80, 0.2);
      }

      .error-message i, .success-message i {
          font-size: 18px;
      }

      .step-tooltip {
          position: absolute;
          bottom: -30px;
          left: 50%;
          transform: translateX(-50%);
          background-color: rgba(0, 0, 0, 0.8);
          color: white;
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 12px;
          white-space: nowrap;
          pointer-events: none;
          opacity: 0;
          animation: fadeIn 0.2s ease forwards;
      }
  `;
  document.head.appendChild(styleElement);

  // Form elements
  const container = document.querySelector('.container');
  const registerBtn = document.querySelector('.register-btn');
  const loginBtn = document.querySelector('.login-btn');
  const loginForm = document.getElementById('login-ajax-form');
  const registrationForm = document.getElementById('registration-form');
  const stepIndicators = document.querySelectorAll('.step-indicator');
  const stepContents = document.querySelectorAll('.step-content');
  const stepLines = document.querySelectorAll('.step-line');
  const nextButtons = document.querySelectorAll('.next-btn');
  const prevButtons = document.querySelectorAll('.prev-btn');

  // Form toggle functionality with animation lock
  function toggleForms(showRegister = false) {
    if (isAnimating) return;
    isAnimating = true;

    if (showRegister) {
      container.classList.add('active');
      resetRegistrationForm();
    } else {
      container.classList.remove('active');
      resetLoginForm();
    }

    setTimeout(() => {
      isAnimating = false;
    }, 600);
  }
  // New validation function to fix the issues
  function validateStep(stepNumber) {
    const stepContent = document.querySelector(`.step-content[data-step="${stepNumber}"]`);
    if (!stepContent) return false;

    console.log(`Validating step ${stepNumber} with fixed validation`);

    // Always start with valid and prove invalid
    let isValid = true;

    // CRITICAL FIX: Check for ANY server-side errors on form fields (especially email)
    // This is the key part that was failing before
    const serverErrors = stepContent.querySelectorAll('.form-error');
    let hasServerErrors = false;

    serverErrors.forEach(error => {
      const errorText = error.textContent.trim();
      if (errorText !== '') {
        // This is a critical change - force the form to be invalid
        isValid = false;
        hasServerErrors = true;

        // Make the error visible and attach it to the field
        const parentBox = error.closest('.input-box, .input-group');
        if (parentBox) {
          const field = parentBox.querySelector('input, select, textarea');
          if (field) field.classList.add('is-invalid');

          // Make existing error super visible
          error.style.display = 'block';
          error.style.visibility = 'visible';
          error.style.color = '#ff0000';
          error.style.fontWeight = 'bold';
          error.style.fontSize = '0.9rem';
        }
      }
    });

    // Special check for email existence errors - critical part
    const emailField = stepContent.querySelector('input[type="email"]');
    if (emailField) {
      const emailBox = emailField.closest('.input-box');
      const emailError = emailBox?.querySelector('.form-error');

      if (emailError && emailError.textContent.trim() !== '') {
        console.log("EMAIL ERROR: " + emailError.textContent);
        // Force invalid when email has an error
        isValid = false;
        hasServerErrors = true;

        // Add strong visual indication
        emailField.style.border = '2px solid red';

        // Make the error message prominent
        emailError.style.display = 'block';
        emailError.style.visibility = 'visible';
        emailError.style.color = '#ff0000';
        emailError.style.fontWeight = 'bold';
      }
    }

    // If we have server errors, don't bother with client validation
    if (hasServerErrors) {
      console.log("VALIDATION FAILED: Server-side errors detected");
      return false; // Critical change: immediately fail validation
    }

    // Now handle client-side validation
    // Find ALL required inputs in this step
    const inputs = stepContent.querySelectorAll('input, select, textarea');
    const requiredFields = Array.from(inputs).filter(input =>
      input.hasAttribute('required') ||
      input.getAttribute('aria-required') === 'true' ||
      input.classList.contains('required-field')
    );

    // Create map for radio buttons
    const radioGroups = new Map();

    // Basic field validation
    requiredFields.forEach(field => {
      // Handle radio buttons specially
      if (field.type === 'radio') {
        if (!radioGroups.has(field.name)) {
          radioGroups.set(field.name, []);
        }
        radioGroups.get(field.name).push(field);
        return;
      }

      // Regular fields - check if empty
      if (!field.value || field.value.trim() === '') {
        console.log(`Field is empty: ${field.id || field.name}`);
        field.classList.add('is-invalid');
        isValid = false;
      }

      // Email format validation
      if (field.type === 'email' && field.value.trim() !== '') {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(field.value)) {
          console.log(`Invalid email format: ${field.value}`);
          field.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    // Validate radio groups
    radioGroups.forEach((radios, name) => {
      let groupValid = radios.some(radio => radio.checked);
      if (!groupValid) {
        radios.forEach(radio => radio.classList.add('is-invalid'));
        isValid = false;
        console.log(`Radio group not selected: ${name}`);
      }
    });

    // Special validation for terms on step 3
    if (stepNumber === 3) {
      const termsCheckbox = stepContent.querySelector('#terms');
      if (termsCheckbox && !termsCheckbox.checked) {
        termsCheckbox.classList.add('is-invalid');
        const termsError = document.getElementById('terms-error');
        if (termsError) termsError.style.display = 'block';
        isValid = false;
      }
    }

    // If validation failed, show visual feedback
    if (!isValid) {
      // Focus on the first invalid field
      const firstInvalid = stepContent.querySelector('.is-invalid');
      if (firstInvalid) {
        setTimeout(() => {
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstInvalid.focus();
        }, 100);
      }

      console.log(`VALIDATION FAILED for step ${stepNumber}`);
    }

    return isValid;
  }

  // Reset form states
  function resetRegistrationForm() {
    if (!registrationForm) return;
    registrationForm.reset();
    document.querySelectorAll('.step-content').forEach(step => step.classList.remove('active'));
    document.querySelector('.step-content[data-step="1"]').classList.add('active');
    completedSteps = [1];
    currentStep = 1;
    updateStepIndicators();
  }

  function resetLoginForm() {
    if (!loginForm) return;
    loginForm.reset();
    const errorContainer = document.getElementById('login-error-container');
    if (errorContainer) errorContainer.style.display = 'none';
  }

  // Event listeners for form switching
  if (registerBtn) {
    registerBtn.addEventListener('click', (e) => {
      e.preventDefault();
      toggleForms(true);
    });
  }

  if (loginBtn) {
    loginBtn.addEventListener('click', (e) => {
      e.preventDefault();
      toggleForms(false);
    });
  }

  // Enhanced next button handlers with server validation
  nextButtons.forEach(button => {
    button.addEventListener('click', async function () {
      if (isAnimating) return;

      const currentStepContent = this.closest('.step-content');
      const currentStepNumber = parseInt(currentStepContent.dataset.step);

      // Client-side validation first
      if (!validateStep(currentStepNumber)) {
        button.classList.add('shake');
        setTimeout(() => button.classList.remove('shake'), 500);
        return;
      }

      // Server-side validation
      const formData = new FormData(registrationForm);

      try {
        const response = await fetch(`/validate-step/${currentStepNumber}`, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();

        if (data.success) {
          if (!completedSteps.includes(currentStepNumber)) {
            completedSteps.push(currentStepNumber);
          }
          navigateToStep(currentStepNumber + 1);
        } else {
          if (data.fieldErrors) {
            Object.entries(data.fieldErrors).forEach(([fieldName, errors]) => {
              const field = registrationForm.querySelector(`[name*="[${fieldName}]"]`);
              if (field) {
                showError(field, errors.join(' '));
                field.classList.add('shake');
                setTimeout(() => field.classList.remove('shake'), 500);
              }
            });
          }
        }
      } catch (error) {
        console.error('Step validation error:', error);
        const errorMessage = document.getElementById('signup-error-message');
        errorMessage.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Server validation failed. Please try again.</span>';
        errorMessage.style.display = 'flex';
      }
    });
  });

  // Previous button handlers
  prevButtons.forEach(button => {
    button.addEventListener('click', function () {
      if (isAnimating) return;
      const currentStepContent = this.closest('.step-content');
      const currentStepNumber = parseInt(currentStepContent.dataset.step);
      navigateToStep(currentStepNumber - 1);
    });
  });

  // Enhanced form submission with server validation
  if (registrationForm) {
    registrationForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (isAnimating) return;

      // Validate all steps on both client and server
      for (let step = 1; step <= 3; step++) {
        if (!validateStep(step)) {
          navigateToStep(step);
          return;
        }

        try {
          const stepFormData = new FormData(this);
          const validationResponse = await fetch(`/validate-step/${step}`, {
            method: 'POST',
            body: stepFormData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });

          const validationData = await validationResponse.json();
          if (!validationData.success) {
            navigateToStep(step);
            if (validationData.fieldErrors) {
              Object.entries(validationData.fieldErrors).forEach(([fieldName, errors]) => {
                const field = this.querySelector(`[name*="[${fieldName}]"]`);
                if (field) {
                  showError(field, errors.join(' '));
                }
              });
            }
            return;
          }
        } catch (error) {
          console.error('Step validation error:', error);
          return;
        }
      }

      // Submit form if all validations pass
      const formData = new FormData(this);
      const successMessage = document.getElementById('signup-success-message');
      const errorMessage = document.getElementById('signup-error-message');

      try {
        const response = await fetch(this.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();

        if (data.success) {
          // Hide error message if visible
          errorMessage.style.display = 'none';
          
          // Show success message
          successMessage.style.display = 'flex';
          successMessage.innerHTML = '<i class="fas fa-check-circle"></i><span>Account created successfully! Redirecting to login...</span>';
          
          // Create confetti effect
          createConfetti();
          
          // Reset form
          this.reset();
          
          // Switch to login panel after a short delay
          setTimeout(() => {
            toggleForms(false); // Switch to login panel
            successMessage.style.display = 'none';
          }, 2000);
        } else {
          successMessage.style.display = 'none';
          errorMessage.innerHTML = `<i class="fas fa-exclamation-circle"></i><span>${data.message || 'Registration failed. Please check your information.'}</span>`;
          errorMessage.style.display = 'flex';

          if (data.fieldErrors) {
            handleFieldErrors(data.fieldErrors);
          }
        }
      } catch (error) {
        console.error('Registration error:', error);
        successMessage.style.display = 'none';
        errorMessage.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>An unexpected error occurred. Please try again.</span>';
        errorMessage.style.display = 'flex';
      }
    });
  }

  // Helper function to validate a step
  function validateStep(step) {
    const stepContent = document.querySelector(`.step-content[data-step="${step}"]`);
    if (!stepContent) return false;

    let isValid = true;
    const fields = stepContent.querySelectorAll('input, select, textarea');

    fields.forEach(field => {
      // Clear previous errors
      removeError(field);

      if (field.hasAttribute('required') && !field.value.trim()) {
        showError(field, 'This field is required');
        isValid = false;
        return;
      }

      // Email validation
      if (field.type === 'email' && field.value.trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value.trim())) {
          showError(field, 'Please enter a valid email address');
          isValid = false;
          return;
        }
      }

      // Password validation
      if (field.id === 'reg_password' && field.value.trim()) {
        if (field.value.length < 8) {
          showError(field, 'Password must be at least 8 characters long');
          isValid = false;
          return;
        }
      }

      // Confirm password validation
      if (field.id === 'reg_confirm_password' && field.value.trim()) {
        const password = document.getElementById('reg_password');
        if (password && field.value !== password.value) {
          showError(field, 'Passwords do not match');
          isValid = false;
          return;
        }
      }

      // Phone number validation
      if (field.id === 'reg_phone' && field.value.trim()) {
        const phoneRegex = /^\+?[1-9][0-9]{7,14}$/;
        if (!phoneRegex.test(field.value.replace(/\s/g, ''))) {
          showError(field, 'Please enter a valid phone number');
          isValid = false;
          return;
        }
      }
    });

    return isValid;
  }

  // Helper function to show error message
  function showError(field, message) {
    removeError(field);
    field.classList.add('is-invalid');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i>${message}`;

    const parent = field.parentElement;
    if (parent) {
      parent.appendChild(errorDiv);
    }
  }

  // Helper function to remove error message
  function removeError(field) {
    field.classList.remove('is-invalid');
    const parent = field.parentElement;
    if (parent) {
      const errorDiv = parent.querySelector('.field-error');
      if (errorDiv) {
        errorDiv.remove();
      }
    }
  }

  // Helper function to handle field errors
  function handleFieldErrors(fieldErrors) {
    Object.entries(fieldErrors).forEach(([fieldName, errors]) => {
      const field = registrationForm.querySelector(`[name*="[${fieldName}]"]`);
      if (field) {
        showError(field, errors.join(' '));
      }
    });
  }

  /**
 * Handles navigation between steps with validation
 * @param {number} targetStep - The step to navigate to
 * @param {boolean} force - Whether to skip validation (for backward navigation)
 * @returns {boolean} - True if navigation was successful
 */
  async function navigateToStep(targetStep, force = false) {
    const currentStep = parseInt(document.querySelector('.step-content.active').dataset.step);

    // Don't do anything if we're already on this step
    if (targetStep === currentStep) return false;

    // Backward navigation doesn't require validation
    if (targetStep < currentStep || force) {
      updateStepUI(targetStep);
      return true;
    }

    // Forward navigation requires validation of all intermediate steps
    for (let step = currentStep; step < targetStep; step++) {
      if (!validateStep(step)) {
        // Show error for the first invalid step
        const firstInvalid = document.querySelector(`.step-content[data-step="${step}"] .is-invalid`);
        if (firstInvalid) {
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstInvalid.focus();
        }
        return false;
      }
    }

    // All validations passed - proceed with navigation
    updateStepUI(targetStep);
    return true;
  }

  function updateStepUI(targetStep) {
    // Update step content visibility
    document.querySelectorAll('.step-content').forEach(content => {
      content.classList.toggle('active', parseInt(content.dataset.step) === targetStep);
    });

    // Update step indicators
    document.querySelectorAll('.step-indicator').forEach(indicator => {
      const step = parseInt(indicator.dataset.step);
      indicator.classList.toggle('active', step === targetStep);
      indicator.classList.toggle('completed', step < targetStep);
    });

    // Update step lines
    document.querySelectorAll('.step-line').forEach((line, index) => {
      line.classList.toggle('active', (index + 1) < targetStep);
    });
  }
  // Helper function to update step indicators
  function updateStepIndicators() {
    stepIndicators.forEach((indicator, index) => {
      const step = index + 1;
      indicator.classList.remove('active', 'completed');

      if (step === currentStep) {
        indicator.classList.add('active');
      } else if (completedSteps.includes(step)) {
        indicator.classList.add('completed');
      }
    });

    // Update connecting lines
    stepLines.forEach((line, index) => {
      const beforeStep = index + 1;
      const afterStep = index + 2;

      line.classList.remove('active');
      if (completedSteps.includes(beforeStep) && completedSteps.includes(afterStep)) {
        line.classList.add('active');
      }
    });
  }

  // Helper function to create confetti effect
  function createConfetti() {
    const confettiContainer = document.createElement('div');
    confettiContainer.className = 'confetti-container';
    document.body.appendChild(confettiContainer);

    const colors = ['#f44336', '#2196f3', '#4caf50', '#ffeb3b', '#9c27b0'];

    for (let i = 0; i < 100; i++) {
      const confetti = document.createElement('div');
      confetti.className = 'confetti';
      confetti.style.left = Math.random() * 100 + 'vw';
      confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
      confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
      confetti.style.animationDelay = (Math.random() * 2) + 's';
      confettiContainer.appendChild(confetti);
    }

    setTimeout(() => {
      confettiContainer.remove();
    }, 5000);
  }

  // Helper function to create ripple effect
  function createRippleEffect(element, event) {
    const ripple = document.createElement('span');
    ripple.className = 'ripple-effect';

    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height) * 2;

    ripple.style.width = ripple.style.height = `${size}px`;

    const x = event ? event.clientX - rect.left - size / 2 : rect.width / 2 - size / 2;
    const y = event ? event.clientY - rect.top - size / 2 : rect.height / 2 - size / 2;

    ripple.style.left = `${x}px`;
    ripple.style.top = `${y}px`;

    element.appendChild(ripple);

    setTimeout(() => {
      ripple.remove();
    }, 600);
  }

  // Enhanced step navigation handler
  async function handleStepNavigation(targetStep) {
    if (isAnimating) return false;

    const currentStepElement = document.querySelector('.step-content.active');
    const currentStep = parseInt(currentStepElement.dataset.step);

    // Don't do anything if clicking the current step
    if (targetStep === currentStep) return false;

    // Allow backward navigation without validation
    if (targetStep < currentStep) {
      navigateToStep(targetStep);
      return true;
    }

    // For forward navigation, validate all steps in between
    for (let step = currentStep; step < targetStep; step++) {
      try {
        const formData = new FormData(registrationForm);
        const response = await fetch(`/validate-step/${step}`, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();

        if (!data.success) {
          // Show validation errors
          if (data.fieldErrors) {
            // Focus on the first field with error
            let firstErrorField = null;
            Object.entries(data.fieldErrors).forEach(([fieldName, errors]) => {
              const field = registrationForm.querySelector(`[name*="[${fieldName}]"]`);
              if (field) {
                if (!firstErrorField) firstErrorField = field;
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.textContent = errors.join(' ');
                field.parentElement.appendChild(errorDiv);
                field.classList.add('is-invalid');
              }
            });
            if (firstErrorField) {
              firstErrorField.focus();
              firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
          }
          return false;
        }

        // Add step to completed steps if not already included
        if (!completedSteps.includes(step)) {
          completedSteps.push(step);
        }
      } catch (error) {
        console.error('Step validation error:', error);
        return false;
      }
    }

    // If all validations pass, navigate to target step
    navigateToStep(targetStep);
    return true;
  }

  // Make step indicators clickable with enhanced navigation
  stepIndicators.forEach(indicator => {
    indicator.addEventListener('click', async function (event) {
      const targetStep = parseInt(this.dataset.step);
      const validStep = await handleStepNavigation(targetStep);

      if (!validStep) {
        // Visual feedback for invalid navigation attempt
        this.classList.add('shake-animation');
        setTimeout(() => this.classList.remove('shake-animation'), 500);
      } else {
        // Visual feedback for successful navigation
        createRippleEffect(this, event);
      }
    });

    // Add hover effect to show step description
    indicator.addEventListener('mouseenter', function () {
      const stepNumber = parseInt(this.dataset.step);
      const descriptions = {
        1: 'Basic Information',
        2: 'Personal Details',
        3: 'Security'
      };

      const tooltip = document.createElement('div');
      tooltip.className = 'step-tooltip';
      tooltip.textContent = descriptions[stepNumber] || `Step ${stepNumber}`;
      this.appendChild(tooltip);
    });

    indicator.addEventListener('mouseleave', function () {
      const tooltip = this.querySelector('.step-tooltip');
      if (tooltip) {
        tooltip.remove();
      }
    });
  });

  // Initialize password toggle functionality
  const passwordToggles = document.querySelectorAll('.password-toggle');
  passwordToggles.forEach(toggle => {
    toggle.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target') || 'password';
      const passwordInput = document.getElementById(targetId);

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        this.classList.remove('bxs-lock-alt');
        this.classList.add('bxs-lock-open-alt');
      } else {
        passwordInput.type = 'password';
        this.classList.remove('bxs-lock-open-alt');
        this.classList.add('bxs-lock-alt');
      }

      this.classList.add('pulse-animation');
      setTimeout(() => {
        this.classList.remove('pulse-animation');
      }, 500);
    });
  });
});
