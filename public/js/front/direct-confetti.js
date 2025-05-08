/**
 * Direct Confetti Implementation
 * This is an independent confetti implementation that doesn't rely on other scripts
 * Only triggered on successful signup
 */

console.log("Direct confetti script loaded");

// Direct confetti implementation - only for successful registration
function createDirectConfetti() {
    console.log("Creating direct confetti");
    
    // Force display the success message
    const successMessage = document.getElementById('signup-success-message');
    if (successMessage) {
        successMessage.style.display = 'flex';
        successMessage.style.opacity = '1';
        successMessage.style.animation = 'none';
        void successMessage.offsetWidth; // Trigger reflow
        successMessage.style.animation = 'fadeIn 0.5s ease';
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            successMessage.style.opacity = '0';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 500);
        }, 5000);
    }
    
    // Create container for confetti
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
    
    // Add animation keyframes if they don't exist
    if (!document.getElementById('direct-confetti-styles')) {
        const styleEl = document.createElement('style');
        styleEl.id = 'direct-confetti-styles';
        styleEl.textContent = `
            @keyframes direct-confetti-fall {
                0% {
                    transform: translateY(-10px) rotate(0deg);
                    opacity: 1;
                }
                100% {
                    transform: translateY(100vh) rotate(720deg);
                    opacity: 0;
                }
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        `;
        document.head.appendChild(styleEl);
    }
    
    // Create 150 confetti pieces
    const colors = ['#3a86ff', '#ff006e', '#ffbe0b', '#06d6a0', '#fb5607', '#8338ec'];
    
    for (let i = 0; i < 150; i++) {
        const confetti = document.createElement('div');
        
        // Randomize properties
        const size = Math.random() * 10 + 5;
        const color = colors[Math.floor(Math.random() * colors.length)];
        const left = Math.random() * 100;
        const opacity = Math.random() * 0.7 + 0.3;
        const duration = Math.random() * 3 + 2;
        const delay = Math.random() * 0.5;
        
        // Set styles directly
        confetti.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background-color: ${color};
            left: ${left}%;
            top: -10px;
            opacity: ${opacity};
            border-radius: 50%;
            animation: direct-confetti-fall ${duration}s linear ${delay}s forwards;
        `;
        
        // Add some shape variety
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
    
    // Add pulse animation to form box
    const formBox = document.querySelector('.form-box.register');
    if (formBox) {
        formBox.style.animation = 'none';
        void formBox.offsetWidth; // Trigger reflow
        formBox.style.animation = 'success-pulse 1s ease-in-out';
    }
    
    // Remove container after animation completes
    setTimeout(() => {
        container.remove();
    }, 6000);
}

// Make the function globally available for signup success only
window.createDirectConfetti = createDirectConfetti;

// Add a DOM loaded listener only to ensure the script is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Direct confetti ready for successful registration');
});
