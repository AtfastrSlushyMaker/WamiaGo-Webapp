/**
 * Consolidated JavaScript for Login/Signup Page
 * Combines all functionality from separate files into a single optimized file
 */

document.addEventListener('DOMContentLoaded', function () {
  console.log('Login/Signup system initialized');

  // =================== Login/Signup Toggle ===================
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

  // =================== Password Toggle ===================
  document.querySelectorAll('.toggle-password').forEach(button => {
    const input = button.previousElementSibling;

    if (!input || (input.type !== 'password' && input.type !== 'text')) return;

    button.addEventListener('click', () => {
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      button.setAttribute('aria-pressed', !isPassword);
    });
  });

  // =================== Multi-step Form ===================
  const stepContents = document.querySelectorAll('.step-content');
  const stepIndicators = document.querySelectorAll('.step-indicator');
  const stepLines = document.querySelectorAll('.step-line');
  const nextButtons = document.querySelectorAll('.next-btn');
  const prevButtons = document.querySelectorAll('.prev-btn');

  let currentStep = 1;

  function navigateToStep(stepNumber) {
    stepContents.forEach((content, index) => {
      content.classList.toggle('active', index + 1 === stepNumber);
    });

    stepIndicators.forEach((indicator, index) => {
      indicator.classList.toggle('active', index + 1 === stepNumber);
      indicator.classList.toggle('completed', index + 1 < stepNumber);
    });

    stepLines.forEach((line, index) => {
      line.classList.toggle('active', index + 1 < stepNumber);
    });

    currentStep = stepNumber;
  }

  nextButtons.forEach(button => {
    button.addEventListener('click', () => {
      if (currentStep < stepContents.length) {
        navigateToStep(currentStep + 1);
      }
    });
  });

  prevButtons.forEach(button => {
    button.addEventListener('click', () => {
      if (currentStep > 1) {
        navigateToStep(currentStep - 1);
      }
    });
  });

  // =================== Form Validation ===================
  const registrationForm = document.getElementById('registration-form');

  if (registrationForm) {
    registrationForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const inputs = registrationForm.querySelectorAll('input[required], select[required]');
      let isValid = true;

      inputs.forEach(input => {
        if (!input.value.trim()) {
          input.classList.add('is-invalid');
          isValid = false;
        } else {
          input.classList.remove('is-invalid');
        }
      });

      if (isValid) {
        console.log('Form submitted successfully');
        // Add AJAX submission logic here
      } else {
        console.log('Form validation failed');
      }
    });
  }

  // =================== Flatpickr Initialization ===================
  if (typeof flatpickr === 'function') {
    flatpickr('#reg_date_of_birth', {
      dateFormat: 'Y-m-d',
      maxDate: 'today',
      allowInput: true,
    });
  }
});
