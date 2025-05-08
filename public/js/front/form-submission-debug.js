/**
 * Form Submission Handler with Confetti Animation
 * This script handles form submissions and triggers celebratory animations on success
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log("Form submission handler enabled");
    
    // Get the registration form
    const registrationForm = document.getElementById('registration-form');
    
    // Fallback confetti function if the main one is unavailable
    function createFallbackConfetti() {
        console.log("Using fallback confetti");
        // Create container for confetti pieces
        const container = document.createElement('div');
        container.className = 'confetti-container';
        document.body.appendChild(container);
        
        // Number of confetti pieces
        const confettiCount = 100;
        const colors = ['#3a86ff', '#ff006e', '#ffbe0b', '#06d6a0', '#fb5607'];
        
        // Create confetti pieces
        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            
            // Style the confetti
            const size = Math.random() * 10 + 5;
            const color = colors[Math.floor(Math.random() * colors.length)];
            const left = Math.random() * 100;
            const opacity = Math.random() * 0.7 + 0.3;
            const animationDuration = Math.random() * 2 + 2;
            
            Object.assign(confetti.style, {
                width: `${size}px`,
                height: `${size}px`,
                backgroundColor: color,
                left: `${left}%`,
                opacity: opacity,
                animation: `confetti-fall ${animationDuration}s linear forwards`
            });
            
            container.appendChild(confetti);
        }
        
        // Remove after animation completes
        setTimeout(() => container.remove(), 6000);
    }
    
    // Add submit event listener to the form
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            // Prevent default form submission and handle it with AJAX
            e.preventDefault();
            
            console.log("Registration form submitted");
            
            // Trigger confetti immediately (for testing)
            window.triggerTestConfetti();
            
            // Get submit button and show loading state
            const submitBtn = document.querySelector('.create-account-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating Account...';
            }
            
            // Log all form field values (for debugging)
            const formData = new FormData(this);
            
            // Submit the form using AJAX
            fetch(this.action, {
                method: this.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Server error');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log("Registration successful:", data);
                
                // Show success message and confetti
                window.triggerTestConfetti();
                
                // Hide any error messages
                const errorMessage = document.getElementById('signup-error-message');
                if (errorMessage) {
                    errorMessage.style.display = 'none';
                }
                
                // Redirect after delay (if redirect URL provided)
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 3000);
                }
            })
            .catch(error => {
                console.error("Registration failed:", error);
                
                // Show error message
                const errorMessage = document.getElementById('signup-error-message');
                if (errorMessage) {
                    errorMessage.querySelector('span').textContent = error.message || 'Registration failed. Please try again.';
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
});
