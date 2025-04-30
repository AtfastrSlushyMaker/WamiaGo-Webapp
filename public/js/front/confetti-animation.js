/**
 * Confetti Animation for Successful Registration
 * 
 * This script creates a beautiful confetti animation when a user successfully completes registration
 * It adds a celebratory visual effect to enhance the user experience
 */

console.log("Confetti animation script loaded");

// Make createConfetti function globally available
window.createConfetti = function() {
    console.log("Creating confetti animation on successful signup");
    
    // Make sure we only have one confetti animation at a time
    const existingContainer = document.querySelector('.confetti-container');
    if (existingContainer) {
        existingContainer.remove();
    }
    
    // Create container for confetti pieces
    const container = document.createElement('div');
    container.className = 'confetti-container';
    document.body.appendChild(container);
    
    // Number of confetti pieces to create
    const CONFETTI_COUNT = 150; // Increased for more impact
    
    // Array of confetti colors
    const colors = [
        '#3a86ff', // blue
        '#8338ec', // purple
        '#ff006e', // pink
        '#fb5607', // orange
        '#ffbe0b', // yellow
        '#06d6a0', // teal
    ];
    
    // Create all the confetti pieces
    for (let i = 0; i < CONFETTI_COUNT; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        
        // Randomize confetti properties
        const size = Math.random() * 10 + 5; // between 5-15px
        const color = colors[Math.floor(Math.random() * colors.length)];
        const left = Math.random() * 100; // position horizontally (0-100%)
        const opacity = Math.random() * 0.7 + 0.3; // opacity between 0.3-1
        const duration = Math.random() * 3 + 2; // animation duration between 2-5s
        const delay = Math.random() * 0.5; // delay start by 0-0.5s
        
        // Apply styles directly (more reliable than Object.assign for some browsers)
        confetti.style.width = `${size}px`;
        confetti.style.height = `${size}px`;
        confetti.style.backgroundColor = color;
        confetti.style.left = `${left}%`;
        confetti.style.opacity = opacity.toString();
        confetti.style.position = "absolute";
        confetti.style.top = "-10px";
        confetti.style.borderRadius = "50%";
        confetti.style.animation = `confetti-fall ${duration}s linear ${delay}s forwards`;
        
        // Add some variety in shapes
        if (Math.random() > 0.7) {
            confetti.style.borderRadius = '0'; // square
            confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
        } else if (Math.random() > 0.5) {
            confetti.style.width = `${size / 2}px`;
            confetti.style.height = `${size * 2}px`;
            confetti.style.borderRadius = '5px';
        }
        
        container.appendChild(confetti);
    }
    
    // Add CSS for the animation if it doesn't exist
    if (!document.getElementById('confetti-styles')) {
        const styleEl = document.createElement('style');
        styleEl.id = 'confetti-styles';
        styleEl.textContent = `
            .confetti-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                pointer-events: none;
                overflow: hidden;
            }
            
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
    
    // Remove confetti after animation completes
    setTimeout(() => {
        container.remove();
    }, 6000);
    
    // Show success message
    const successMessage = document.getElementById('signup-success-message');
    if (successMessage) {
        successMessage.querySelector('span').textContent = 'Account created successfully! Redirecting...';
        successMessage.style.display = 'flex';
        
        // Add success animation to the form
        const formBox = document.querySelector('.form-box.register');
        if (formBox) {
            formBox.classList.add('success-animation');
            setTimeout(() => {
                formBox.classList.remove('success-animation');
            }, 1500);
        }
    }
    
    return true;
};

// Function to show success message with confetti
window.showSuccessWithConfetti = function(message) {
    console.log("Showing success with confetti:", message);
    
    // Display the success message
    const successMessage = document.getElementById('signup-success-message');
    if (successMessage) {
        successMessage.querySelector('span').textContent = message || 'Account created successfully! Redirecting...';
        successMessage.style.display = 'flex';
        
        // Create confetti animation
        window.createConfetti();
        
        // Add success animation to the form
        const formBox = document.querySelector('.form-box.register');
        if (formBox) {
            formBox.classList.add('success-animation');
            setTimeout(() => {
                formBox.classList.remove('success-animation');
            }, 1500);
        }
    }
};

// Listen for successful form submission only
document.addEventListener('DOMContentLoaded', function() {
    console.log("Confetti ready for signup success");
    
    // Make sure window functions are available
    if (!window.createConfetti) {
        window.createConfetti = createConfetti;
    }
    if (!window.showSuccessWithConfetti) {
        window.showSuccessWithConfetti = showSuccessWithConfetti;
    }
});
