/**
 * WamiaGo User Profile JavaScript
 * Handles all interactive functionality for the user profile page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Profile picture preview
    initializeProfilePicturePreview();
    
    // Password strength meter
    initializePasswordStrengthMeter();
    
    // Password visibility toggle
    initializePasswordToggle();
    
    // Form validation
    initializeFormValidation();
    
    // Tab persistence
    initializeTabPersistence();
});

/**
 * Initialize profile picture preview functionality
 */
function initializeProfilePicturePreview() {
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const previewContainer = document.getElementById('previewContainer');
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
}

/**
 * Initialize password strength meter
 */
function initializePasswordStrengthMeter() {
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordStrength = document.getElementById('passwordStrength');
    const savePasswordBtn = document.getElementById('savePasswordBtn');
    const passwordMatch = document.getElementById('passwordMatch');
    
    // Password requirement checks
    const lengthCheck = document.getElementById('length-check');
    const uppercaseCheck = document.getElementById('uppercase-check');
    const lowercaseCheck = document.getElementById('lowercase-check');
    const numberCheck = document.getElementById('number-check');
    const specialCheck = document.getElementById('special-check');
    
    if (newPassword && passwordStrength) {
        newPassword.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check length
            if (password.length >= 8) {
                strength += 20;
                updateCheck(lengthCheck, true);
            } else {
                updateCheck(lengthCheck, false);
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                strength += 20;
                updateCheck(uppercaseCheck, true);
            } else {
                updateCheck(uppercaseCheck, false);
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                strength += 20;
                updateCheck(lowercaseCheck, true);
            } else {
                updateCheck(lowercaseCheck, false);
            }
            
            // Check numbers
            if (/[0-9]/.test(password)) {
                strength += 20;
                updateCheck(numberCheck, true);
            } else {
                updateCheck(numberCheck, false);
            }
            
            // Check special characters
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 20;
                updateCheck(specialCheck, true);
            } else {
                updateCheck(specialCheck, false);
            }
            
            // Update progress bar
            passwordStrength.style.width = strength + '%';
            
            // Update color based on strength
            if (strength < 40) {
                passwordStrength.className = 'progress-bar bg-danger';
            } else if (strength < 80) {
                passwordStrength.className = 'progress-bar bg-warning';
            } else {
                passwordStrength.className = 'progress-bar bg-success';
            }
            
            // Check if passwords match
            checkPasswordMatch();
        });
    }
    
    // Check if passwords match
    if (confirmPassword && newPassword) {
        confirmPassword.addEventListener('input', checkPasswordMatch);
        newPassword.addEventListener('input', checkPasswordMatch);
    }
    
    function checkPasswordMatch() {
        if (newPassword.value && confirmPassword.value) {
            if (newPassword.value === confirmPassword.value) {
                passwordMatch.textContent = 'Passwords match!';
                passwordMatch.className = 'form-text text-success';
                
                if (newPassword.value.length >= 8 && 
                    /[A-Z]/.test(newPassword.value) && 
                    /[a-z]/.test(newPassword.value) && 
                    /[0-9]/.test(newPassword.value) && 
                    /[^A-Za-z0-9]/.test(newPassword.value)) {
                    savePasswordBtn.disabled = false;
                } else {
                    savePasswordBtn.disabled = true;
                }
            } else {
                passwordMatch.textContent = 'Passwords do not match!';
                passwordMatch.className = 'form-text text-danger';
                savePasswordBtn.disabled = true;
            }
        } else {
            passwordMatch.textContent = '';
            savePasswordBtn.disabled = true;
        }
    }
    
    function updateCheck(element, isValid) {
        if (isValid) {
            element.classList.add('text-success');
            element.classList.remove('text-muted');
        } else {
            element.classList.remove('text-success');
            element.classList.add('text-muted');
        }
    }
}

/**
 * Initialize password visibility toggle
 */
function initializePasswordToggle() {
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const profileEditForm = document.getElementById('profile-edit-form');
    
    if (profileEditForm) {
        profileEditForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Simple validation for required fields
            profileEditForm.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showFormError('Please fill out all required fields');
            }
        });
    }
    
    function showFormError(message) {
        // Create or show error message
        let errorDiv = document.getElementById('form-error-message');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'form-error-message';
            errorDiv.className = 'alert alert-danger mt-3';
            profileEditForm.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }
}

/**
 * Initialize tab persistence using localStorage
 */
function initializeTabPersistence() {
    // Store active tab in localStorage
    const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
    const ACTIVE_TAB_KEY = 'wamiaga_active_profile_tab';
    
    // Set active tab based on localStorage
    const activeTabId = localStorage.getItem(ACTIVE_TAB_KEY);
    if (activeTabId) {
        const tab = document.querySelector(`[data-bs-target="${activeTabId}"]`);
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
    
    // Store tab ID when changed
    tabLinks.forEach(tabLink => {
        tabLink.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('data-bs-target');
            localStorage.setItem(ACTIVE_TAB_KEY, targetId);
        });
    });
}
