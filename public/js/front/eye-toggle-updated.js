/**
 * Updated Eye Animation with improved cursor tracking and blinking
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all password toggle buttons
    document.querySelectorAll('.toggle-password').forEach(button => {
        // Find password input
        const input = button.previousElementSibling;
        
        // Ensure it's a password input
        if (!input || (input.type !== 'password' && input.type !== 'text')) return;
        
        // Animation state
        let busy = false;
        
        // Eye elements
        const eyeBall = button.querySelector('.eye-ball');
        const eyePupil = button.querySelector('.eye-pupil');
        const eyeShape = button.querySelectorAll('.eye-shape');
        
        // Mouse tracking for eye movement
        document.addEventListener('mousemove', (e) => {
            // Skip if busy with animation or password is visible (eye closed)
            if (busy || input.type !== 'password' || !eyeBall || !eyePupil) return;
            
            // Get the entire input field area
            const inputRect = input.getBoundingClientRect();
            const buttonRect = button.getBoundingClientRect();
            
            // Check if the input field is visible and has focus or cursor is near it
            const buffer = 300; // Much larger buffer area
            
            // Check if the input has focus
            const hasFocus = document.activeElement === input;
            
            // Track eye even when typing if input has focus
            if (hasFocus || (
                e.clientX >= inputRect.left - buffer && 
                e.clientX <= inputRect.right + buffer && 
                e.clientY >= inputRect.top - buffer && 
                e.clientY <= inputRect.bottom + buffer
            )) {
                const buttonCenterX = buttonRect.left + buttonRect.width / 2;
                const buttonCenterY = buttonRect.top + buttonRect.height / 2;
                
                // Calculate distance from button center
                const distX = e.clientX - buttonCenterX;
                const distY = e.clientY - buttonCenterY;
                
                // Increased tracking range and responsiveness
                const distance = Math.sqrt(distX * distX + distY * distY);
                
                // Always move eye when input has focus, otherwise use distance check
                if (hasFocus || distance < 400) {
                    // Map distance to movement with increased range
                    const moveX = (distX / 80) * 2.5;
                    const moveY = (distY / 80) * 2.5;
                    
                    // Expanded movement range
                    const limitedX = Math.min(3, Math.max(-3, moveX));
                    const limitedY = Math.min(3, Math.max(-3, moveY));
                    
                    // Apply movement with smoother transition
                    eyeBall.style.transition = 'transform 0.2s ease-out';
                    eyePupil.style.transition = 'transform 0.2s ease-out';
                    eyeBall.style.transform = `translate(${limitedX}px, ${limitedY}px)`;
                    // Make pupil move more for better effect
                    eyePupil.style.transform = `translate(${limitedX * 2}px, ${limitedY * 2}px)`;
                } else {
                    // Reset eye position if mouse is far from this input
                    eyeBall.style.transition = 'transform 0.5s ease';
                    eyePupil.style.transition = 'transform 0.5s ease';
                    eyeBall.style.transform = 'translate(0, 0)';
                    eyePupil.style.transform = 'translate(0, 0)';
                }
            } else {
                // Reset eye position if mouse is outside input area
                eyeBall.style.transform = 'translate(0, 0)';
                eyePupil.style.transform = 'translate(0, 0)';
            }
        });
        
        // Random blink for each eye (independent)
        let blinkTimeout;
        
        function randomBlink() {
            // Only blink when password is hidden (eye is open)
            if (input.type !== 'password') return;
            
            // Random delay between 2 and 6 seconds
            const delay = Math.random() * 4000 + 2000;
            
            blinkTimeout = setTimeout(() => {
                // Quick blink animation - hide the eyeball briefly
                if (eyeBall && eyePupil && input.type === 'password') {
                    eyeBall.style.opacity = '0';
                    eyePupil.style.opacity = '0';
                    
                    // Open eye after blinking
                    setTimeout(() => {
                        if (input.type === 'password') { // Check again in case it changed
                            eyeBall.style.opacity = '1';
                            eyePupil.style.opacity = '1';
                        }
                    }, 150);
                }
                
                // Schedule next blink
                randomBlink();
            }, delay);
        }
        
        // Start random blinking for each eye
        randomBlink();
        
        // Handle click event
        button.addEventListener('click', () => {
            // Prevent multiple clicks during animation
            if (busy) return;
            busy = true;
            
            const isPassword = input.type === 'password';
            
            // Update ARIA state immediately
            button.setAttribute('aria-pressed', isPassword);
            
            // Force hide pupil immediately when closing eye
            if (isPassword && eyePupil) {
                eyePupil.style.opacity = '0';
                eyePupil.style.visibility = 'hidden';
            }
            
            // The CSS transitions will handle the animations automatically
            
            // Change input type with a short delay
            setTimeout(() => {
                input.type = isPassword ? 'text' : 'password';
                
                // When opening eye, make pupil visible again
                if (!isPassword && eyePupil) {
                    eyePupil.style.opacity = '1';
                    eyePupil.style.visibility = 'visible';
                }
                
                if (isPassword) {
                    // Add ripple animation class
                    input.classList.add('reveal');
                    
                    // Remove class after animation completes
                    setTimeout(() => {
                        input.classList.remove('reveal');
                    }, 600);
                }
                
                busy = false;
                
                // If going back to password, restart blinking
                if (!isPassword) {
                    clearTimeout(blinkTimeout);
                    randomBlink();
                }
            }, 100);
        });
        
        // Clean up event listeners when needed
        return () => {
            clearTimeout(blinkTimeout);
        };
    });
}); 