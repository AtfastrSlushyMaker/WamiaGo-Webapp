/**
 * Enhanced password strength validation for reset password form
 * WamiaGo Web Application
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find password field
    const passwordField = document.getElementById('new_password');
    if (!passwordField) return;
    
    // Get strength elements
    const strengthMeter = document.querySelector('.password-strength-progress');
    const strengthText = document.querySelector('.password-strength-text');
    const strengthContainer = document.querySelector('.password-strength-container');
    
    // Get or create requirement elements
    let passwordRequirements = document.querySelector('.password-requirements');
    
    // Create password requirements section if it doesn't exist
    if (!passwordRequirements) {
        passwordRequirements = document.createElement('div');
        passwordRequirements.className = 'password-requirements';
        passwordRequirements.innerHTML = `
            <p>Password must contain:</p>
            <ul>
                <li id="req-length" class="requirement">At least 8 characters</li>
                <li id="req-uppercase" class="requirement">At least one uppercase letter</li>
                <li id="req-lowercase" class="requirement">At least one lowercase letter</li>
                <li id="req-number" class="requirement">At least one number</li>
                <li id="req-special" class="requirement">At least one special character</li>
            </ul>
        `;
        
        // Insert after the password strength container
        if (strengthContainer && strengthContainer.parentNode) {
            strengthContainer.parentNode.insertBefore(passwordRequirements, strengthContainer.nextSibling);
        }
    }
    
    // Get the requirement elements after ensuring they exist
    const reqLength = document.getElementById('req-length');
    const reqUppercase = document.getElementById('req-uppercase');
    const reqLowercase = document.getElementById('req-lowercase');
    const reqNumber = document.getElementById('req-number');
    const reqSpecial = document.getElementById('req-special');
    
    // Skip if any strength element is missing
    if (!strengthMeter || !strengthText) return;
    
    // Show password requirements container only when field is focused
    passwordRequirements.style.display = 'none';
    passwordField.addEventListener('focus', function() {
        passwordRequirements.style.display = 'block';
        passwordRequirements.style.animation = 'floatUp 0.3s ease-out forwards';
    });
    
    passwordField.addEventListener('blur', function() {
        if (!this.value) {
            passwordRequirements.style.display = 'none';
        }
    });
    
    // Initialize with hidden state
    strengthText.style.opacity = '0';
    
    // Password validation criteria
    const criteria = [
        { regex: /.{8,}/, description: "At least 8 characters", weight: 1, element: reqLength },
        { regex: /[A-Z]/, description: "At least one uppercase letter", weight: 1, element: reqUppercase },
        { regex: /[a-z]/, description: "At least one lowercase letter", weight: 1, element: reqLowercase },
        { regex: /[0-9]/, description: "At least one number", weight: 1, element: reqNumber },
        { regex: /[^A-Za-z0-9]/, description: "At least one special character", weight: 1, element: reqSpecial }
    ];
    
    // Additional high-risk patterns to check
    const highRiskPatterns = [
        { regex: /^123456/, penalty: 2, message: "Sequential numbers are too easy to guess" },
        { regex: /^password$/i, penalty: 2, message: "Using 'password' is not secure" },
        { regex: /^qwerty/i, penalty: 2, message: "Keyboard patterns are too easy to guess" },
        { regex: /(.)\1{2,}/, penalty: 1, message: "Repeated characters reduce security" } // 3+ repeated chars
    ];
    
    // Strength levels with enhanced colors and descriptions
    const strengthLevels = [
        { threshold: 0, color: '#ff7675', text: 'Very weak', icon: '⚠️' },
        { threshold: 2, color: '#fdcb6e', text: 'Weak', icon: '⚠️' },
        { threshold: 3, color: '#74b9ff', text: 'Moderate', icon: '✓' },
        { threshold: 4, color: '#55efc4', text: 'Strong', icon: '✓✓' },
        { threshold: 5, color: '#00b894', text: 'Very strong', icon: '✓✓✓' }
    ];
    
    // Add focus/blur handlers for styling inputs
    const allInputs = document.querySelectorAll('.form-control');
    allInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.closest('.input-box')?.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.closest('.input-box')?.classList.remove('focused');
        });
    });
    
    // Password input handler
    passwordField.addEventListener('input', function() {
        const password = this.value;
        
        // If empty, hide strength indicator
        if (!password) {
            strengthMeter.style.width = '0';
            strengthText.style.opacity = '0';
            // Reset all requirements
            criteria.forEach(criterion => {
                if (criterion.element) {
                    criterion.element.classList.remove('valid');
                }
            });
            return;
        }
        
        // Count passed criteria
        let strengthScore = 0;
        let totalWeight = 0;
        
        criteria.forEach(criterion => {
            totalWeight += criterion.weight;
            const isMet = criterion.regex.test(password);
            
            // Update requirement UI
            if (criterion.element) {
                if (isMet) {
                    criterion.element.classList.add('valid');
                    strengthScore += criterion.weight;
                } else {
                    criterion.element.classList.remove('valid');
                }
            } else if (isMet) {
                strengthScore += criterion.weight;
            }
        });
        
        // Check for high-risk patterns
        let warnings = [];
        highRiskPatterns.forEach(pattern => {
            if (pattern.regex.test(password)) {
                strengthScore = Math.max(0, strengthScore - pattern.penalty);
                warnings.push(pattern.message);
            }
        });
        
        // Add bonus for length beyond 12 characters (diminishing returns)
        if (password.length > 12) {
            const lengthBonus = Math.min(1, (password.length - 12) / 8);
            strengthScore += lengthBonus;
        }
        
        // Determine strength level
        let strengthLevel = strengthLevels[0]; // Default to lowest
        for (let i = strengthLevels.length - 1; i >= 0; i--) {
            if (strengthScore >= strengthLevels[i].threshold) {
                strengthLevel = strengthLevels[i];
                break;
            }
        }
        
        // Update UI with smooth transitions
        const percentage = Math.min(100, (strengthScore / totalWeight) * 100);
        strengthMeter.style.width = percentage + '%';
        strengthMeter.style.backgroundColor = strengthLevel.color;
        
        // Add animation effect
        strengthMeter.classList.add('animated');
        setTimeout(() => {
            strengthMeter.classList.remove('animated');
        }, 500);
        
        // Show warning if any detected
        if (warnings.length > 0) {
            strengthText.textContent = warnings[0];
            strengthText.style.color = '#e74c3c';
        } else {
            strengthText.textContent = `${strengthLevel.icon} ${strengthLevel.text}`;
            strengthText.style.color = strengthLevel.color;
        }
        
        strengthText.style.opacity = '1';
        
        // Validate confirm password if it has content
        const confirmPasswordField = document.getElementById('confirm_password');
        const matchError = document.querySelector('.match-error');
        if (confirmPasswordField && confirmPasswordField.value && matchError) {
            if (confirmPasswordField.value !== password) {
                matchError.style.display = 'block';
            } else {
                matchError.style.display = 'none';
            }
        }
    });
    
    // Add captcha checkbox styling
    const captchaCheckbox = document.getElementById('captcha-checkbox');
    if (captchaCheckbox) {
        captchaCheckbox.addEventListener('change', function() {
            const captchaError = document.getElementById('captcha-error');
            if (captchaError) {
                captchaError.style.display = this.checked ? 'none' : 'block';
            }
            
            // Add a ripple effect when checked
            if (this.checked) {
                const captchaContainer = this.closest('.simple-captcha');
                captchaContainer.classList.add('captcha-verified');
                
                setTimeout(() => {
                    captchaContainer.classList.remove('captcha-verified');
                }, 1000);
            }
        });
    }
    
    // Add form submission animation
    const form = document.querySelector('.reset-password-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validate the form before showing the loading spinner
            
            // Password strength check
            const password = passwordField.value;
            if (password) {
                let strengthScore = 0;
                
                criteria.forEach(criterion => {
                    if (criterion.regex.test(password)) {
                        strengthScore += criterion.weight;
                    }
                });
                
                // If password is too weak (below 'Moderate'), warn the user
                if (strengthScore < 3 && !confirm('Your password is weak. Are you sure you want to continue?')) {
                    e.preventDefault();
                    return;
                }
            }
            
            // Show spinner on submit
            const button = form.querySelector('button[type="submit"]');
            const buttonText = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner-border');
            
            if (buttonText && spinner) {
                buttonText.style.display = 'none';
                spinner.classList.remove('d-none');
            }
        });
    }
});

// Add keypress animation to input fields
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-control');
    
    inputs.forEach(input => {
        input.addEventListener('keydown', function() {
            this.classList.add('keypress-effect');
            setTimeout(() => {
                this.classList.remove('keypress-effect');
            }, 150);
        });
    });
});
