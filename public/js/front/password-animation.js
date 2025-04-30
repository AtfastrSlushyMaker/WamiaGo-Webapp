/**
 * Animated Password Toggle with Eye Animation
 * Direct implementation from the original show-password-animation example
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log("Password animation initialized");

    // Get all password toggle buttons
    const toggleButtons = document.querySelectorAll('.password-toggle-animated');
    console.log(`Found ${toggleButtons.length} password toggle buttons`);

    // Set up each password field
    toggleButtons.forEach((button, index) => {
        // Find the associated input field
        const input = button.previousElementSibling;
        if (!input || !(input.type === 'password' || input.type === 'text')) {
            console.log(`Button ${index}: No valid input found`);
            return;
        }

        console.log(`Button ${index}: Found input field of type ${input.type}`);
        
        // Animation states
        let busy = false;

        // Track eye elements
        const eye = button.querySelector('.eye');
        const lidUpper = button.querySelector('.lid--upper');
        
        // Set initial state
        button.setAttribute('aria-pressed', 'false');
        
        // Eye tracking for mouse movement
        document.addEventListener('mousemove', (e) => {
            if (busy) return;
            
            // Only follow if the password is hidden (eye is open)
            if (input.type !== 'password') return;
            
            const buttonRect = button.getBoundingClientRect();
            const buttonX = buttonRect.left + buttonRect.width/2;
            const buttonY = buttonRect.top + buttonRect.height/2;
            
            // Calculate distance from button center
            const distX = e.clientX - buttonX;
            const distY = e.clientY - buttonY;
            
            // Only move if mouse is somewhat close to the button
            const distance = Math.sqrt(distX * distX + distY * distY);
            if (distance < 300) {
                // Map distance to movement percentage (30% max movement)
                const moveX = (distX / 100) * 30;
                const moveY = (distY / 100) * 30;
                
                // Limit movement 
                const limitedMoveX = Math.min(30, Math.max(-30, moveX));
                const limitedMoveY = Math.min(30, Math.max(-30, moveY));
                
                if (eye) {
                    eye.style.transform = `translate(${limitedMoveX}%, ${limitedMoveY}%)`;
                }
            } else {
                // Reset position if mouse is far away
                if (eye) {
                    eye.style.transform = 'translate(0, 0)';
                }
            }
        });
        
        // Click handler to toggle password visibility
        button.addEventListener('click', () => {
            if (busy) return;
            busy = true;
            
            const isPassword = input.type === 'password';
            console.log(`Button ${index} clicked: Password is ${isPassword ? 'hidden' : 'visible'}`);
            
            button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            
            if (isPassword) {
                // Show the password (close the eye)
                if (lidUpper) {
                    lidUpper.style.transform = 'translateY(3.5px)';
                }
                
                setTimeout(() => {
                    input.type = 'text';
                    busy = false;
                    console.log(`Button ${index}: Changed to text`);
                }, 200);
            } else {
                // Hide the password (open the eye)
                if (lidUpper) {
                    lidUpper.style.transform = '';
                }
                
                setTimeout(() => {
                    input.type = 'password';
                    busy = false;
                    console.log(`Button ${index}: Changed to password`);
                }, 200);
            }
        });
    });
});
