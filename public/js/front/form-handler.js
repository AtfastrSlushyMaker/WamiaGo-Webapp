/**
 * Form submission handler for the registration form
 * This script handles the AJAX submission of the registration form and shows confetti on success
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Registration form submission handler loaded');
    
    // Get the registration form
    const registrationForm = document.getElementById('registration-form');
    
    if (registrationForm) {
        console.log('Found registration form with action:', registrationForm.action);
        
        // Handle form submission
        registrationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Registration form submitted with action:', this.action);
            
            // Get the submit button
            const submitBtn = document.querySelector('.create-account-btn');
            if (submitBtn) {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating Account...';
            }
            
            // Hide any existing messages
            const successMessage = document.getElementById('signup-success-message');
            const errorMessage = document.getElementById('signup-error-message');
            
            if (successMessage) {
                successMessage.style.display = 'none';
            }
            if (errorMessage) {
                errorMessage.style.display = 'none';
            }
            
            // Submit form using fetch API
            fetch(this.action, {
                method: this.method,
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        throw new Error(data.message || 'Registration failed');
                    }
                    return data;
                });
            })
            .then(data => {
                console.log('Registration successful:', data);
                
                // Show success message
                if (successMessage) {
                    successMessage.querySelector('span').textContent = 'Account created successfully! Redirecting...';
                    successMessage.style.display = 'flex';
                }                // Create confetti animation only on successful signup
                console.log('Creating confetti for successful registration');
                
                // Try to create confetti using different methods
                if (typeof createConfetti === 'function') {
                    console.log('Using direct createConfetti function');
                    createConfetti();
                } else if (window.createConfetti) {
                    console.log('Using window.createConfetti function');
                    window.createConfetti();
                } else {
                    console.error('Confetti function not found, falling back to manual confetti');
                    createManualConfetti();
                }
                
                // Add success animation to form
                const formBox = document.querySelector('.form-box.register');
                if (formBox) {
                    formBox.classList.add('success-animation');
                }
                
                // Redirect after delay (if redirect URL provided)
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                
                // Show error message
                if (errorMessage) {
                    errorMessage.querySelector('span').textContent = error.message || 'There was a problem with your registration. Please try again.';
                    errorMessage.style.display = 'flex';
                }
                
                // Reset button state
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'CREATE ACCOUNT';
                }
            });
        });
    }
    
    // Manual confetti implementation as fallback
    function createManualConfetti() {
        const colors = ['#3a86ff', '#ff006e', '#ffbe0b', '#06d6a0', '#fb5607', '#8338ec'];
        const container = document.createElement('div');
        
        container.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            pointer-events: none;
            overflow: hidden;
        `;
        
        document.body.appendChild(container);
        
        // Create animation style if it doesn't exist
        if (!document.getElementById('manual-confetti-style')) {
            const styleEl = document.createElement('style');
            styleEl.id = 'manual-confetti-style';
            styleEl.innerHTML = `
                @keyframes confetti-fall {
                    0% {
                        transform: translateY(-10px) rotate(0deg);
                        opacity: 1;
                    }
                    100% {
                        transform: translateY(100vh) rotate(720deg);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(styleEl);
        }
        
        // Create 150 confetti pieces
        for (let i = 0; i < 150; i++) {
            const confetti = document.createElement('div');
            const size = Math.random() * 10 + 5;
            const color = colors[Math.floor(Math.random() * colors.length)];
            const left = Math.random() * 100;
            const opacity = Math.random() * 0.7 + 0.3;
            const duration = Math.random() * 3 + 2;
            const delay = Math.random() * 0.5;
            
            confetti.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                background-color: ${color};
                left: ${left}%;
                top: -10px;
                border-radius: 50%;
                opacity: ${opacity};
                animation: confetti-fall ${duration}s linear ${delay}s forwards;
            `;
            
            if (Math.random() > 0.7) {
                confetti.style.borderRadius = '0';
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
            } else if (Math.random() > 0.5) {
                confetti.style.width = `${size / 2}px`;
                confetti.style.height = `${size * 2}px`;
                confetti.style.borderRadius = '5px';
            }
            
            container.appendChild(confetti);
        }
        
        // Remove container after animation completes
        setTimeout(() => {
            container.remove();
        }, 6000);
    }
});
