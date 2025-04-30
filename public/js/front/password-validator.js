/**
 * Password Validator
 * A utility for validating password strength across forms
 */

class PasswordValidator {
    constructor(options = {}) {
        this.options = {
            minLength: options.minLength || 8,
            requireUppercase: options.requireUppercase !== undefined ? options.requireUppercase : true,
            requireLowercase: options.requireLowercase !== undefined ? options.requireLowercase : true,
            requireNumbers: options.requireNumbers !== undefined ? options.requireNumbers : true,
            requireSpecial: options.requireSpecial !== undefined ? options.requireSpecial : true,
            specialChars: options.specialChars || '!@#$%^&*()_+{}[];:,.<>?~-',
            onValidate: options.onValidate || null
        };
    }

    /**
     * Validates a password against the defined rules
     * @param {string} password - The password to validate
     * @returns {object} - Validation results with reasons for failure
     */
    validate(password) {
        const result = {
            isValid: true,
            errors: [],
            strength: 0 // 0-100 score
        };

        // Check minimum length
        if (!password || password.length < this.options.minLength) {
            result.isValid = false;
            result.errors.push(`Password must be at least ${this.options.minLength} characters long`);
        } else {
            // Add points for length
            result.strength += Math.min((password.length / 20) * 30, 30);
        }

        // Check for uppercase
        if (this.options.requireUppercase && !/[A-Z]/.test(password)) {
            result.isValid = false;
            result.errors.push('Password must include at least one uppercase letter');
        } else if (/[A-Z]/.test(password)) {
            result.strength += 15;
        }

        // Check for lowercase
        if (this.options.requireLowercase && !/[a-z]/.test(password)) {
            result.isValid = false;
            result.errors.push('Password must include at least one lowercase letter');
        } else if (/[a-z]/.test(password)) {
            result.strength += 10;
        }

        // Check for numbers
        if (this.options.requireNumbers && !/\d/.test(password)) {
            result.isValid = false;
            result.errors.push('Password must include at least one number');
        } else if (/\d/.test(password)) {
            result.strength += 20;
        }

        // Check for special characters
        const specialCharsRegex = new RegExp(`[${this.options.specialChars.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')}]`);
        if (this.options.requireSpecial && !specialCharsRegex.test(password)) {
            result.isValid = false;
            result.errors.push('Password must include at least one special character');
        } else if (specialCharsRegex.test(password)) {
            result.strength += 25;
        }

        // Check for common patterns
        if (/123/.test(password) || /abc/.test(password.toLowerCase())) {
            result.strength -= 10;
        }

        // Cap strength at 100
        result.strength = Math.max(0, Math.min(100, result.strength));

        // Call onValidate callback if provided
        if (typeof this.options.onValidate === 'function') {
            this.options.onValidate(result);
        }

        return result;
    }

    /**
     * Helper function to attach live validation to a password field
     * @param {HTMLInputElement} passwordInput - Input element
     * @param {HTMLElement} feedbackElement - Element to show feedback
     */
    attachToInput(passwordInput, feedbackElement) {
        if (!passwordInput) return;

        passwordInput.addEventListener('input', () => {
            const result = this.validate(passwordInput.value);
            
            if (feedbackElement) {
                feedbackElement.textContent = result.errors.join('. ');
                feedbackElement.style.display = result.errors.length > 0 ? 'block' : 'none';
                
                // You could also show password strength indicator if desired
                // Example: feedbackElement.dataset.strength = result.strength;
            }
            
            // Set validity on the input
            if (result.isValid) {
                passwordInput.classList.remove('is-invalid');
                passwordInput.classList.add('is-valid');
            } else {
                passwordInput.classList.remove('is-valid');
                passwordInput.classList.add('is-invalid');
            }
        });
    }
}

// Export if in module environment
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PasswordValidator;
}
