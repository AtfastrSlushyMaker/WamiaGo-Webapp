/**
 * Perfect Match Password Eye Animation Script
 * Based on the original show-password-animation.html example
 */
document.addEventListener('DOMContentLoaded', () => {
  console.log("Precise eye animation initialized");
  
  // Initialize all password toggle buttons
  document.querySelectorAll('.toggle-password').forEach(button => {
    // Find password input
    const input = button.previousElementSibling;
    
    // Ensure it's a password input
    if (!input || (input.type !== 'password' && input.type !== 'text')) return;
    
    // Animation state
    let busy = false;
    
    // Get eye elements
    const eye = button.querySelector('.eye');
    const lidUpper = button.querySelector('.lid--upper');
    const lidLower = button.querySelector('.lid--lower');
    
    // Mouse tracking for eye movement
    document.addEventListener('mousemove', (e) => {
      // Skip if busy with animation or password is visible (eye closed)
      if (busy || input.type !== 'password') return;
      
      // Get the button's position
      const buttonRect = button.getBoundingClientRect();
      const inputRect = input.getBoundingClientRect();
      
      // Check if mouse is near this specific input field (not others)
      // Using expanded area around the input for better UX
      const buffer = 50; // px buffer around the input
      if (
        e.clientX >= inputRect.left - buffer && 
        e.clientX <= inputRect.right + buffer && 
        e.clientY >= inputRect.top - buffer && 
        e.clientY <= inputRect.bottom + buffer
      ) {
        const buttonCenterX = buttonRect.left + buttonRect.width / 2;
        const buttonCenterY = buttonRect.top + buttonRect.height / 2;
        
        // Calculate distance from button center
        const distX = e.clientX - buttonCenterX;
        const distY = e.clientY - buttonCenterY;
        
        // Map distance to movement (30% maximum movement)
        const moveX = (distX / 100) * 30;
        const moveY = (distY / 100) * 30;
        
        // Limit movement
        const limitedX = Math.min(30, Math.max(-30, moveX));
        const limitedY = Math.min(30, Math.max(-30, moveY));
        
        // Apply movement without transition for instant response
        if (eye) {
          eye.style.transition = 'none';
          eye.style.transform = `translate(${limitedX}%, ${limitedY}%)`;
          
          // Re-enable transition after a small delay
          setTimeout(() => {
            eye.style.transition = 'transform 0.15s ease';
          }, 5);
        }
      } else {
        // Reset eye position if mouse is far from this input
        if (eye) {
          eye.style.transition = 'transform 0.3s ease';
          eye.style.transform = 'translate(0, 0)';
        }
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
        // Quick blink animation
        if (lidUpper && input.type === 'password') {
          lidUpper.style.opacity = '0';
          
          // Open eye after blinking
          setTimeout(() => {
            if (input.type === 'password') { // Check again in case it changed
              lidUpper.style.opacity = '1';
            }
          }, 150);
        }
        
        // Schedule next blink
        randomBlink();
      }, delay);
    }
    
    // Start random blinking
    randomBlink();
    
    // Handle click event
    button.addEventListener('click', () => {
      // Prevent multiple clicks during animation
      if (busy) return;
      busy = true;
      
      const isPassword = input.type === 'password';
      
      // Update ARIA state
      button.setAttribute('aria-pressed', isPassword);
      
      if (isPassword) {
        // Show password (close eye)
        if (lidUpper) {
          // Hide upper lid completely
          lidUpper.style.opacity = '0';
        }
        
        // Reset eye position when showing password
        if (eye) {
          eye.style.transition = 'transform 0.15s ease';
          eye.style.transform = 'translate(0, 0)';
        }
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'text';
          busy = false;
        }, 150);
      } else {
        // Hide password (open eye)
        if (lidUpper) {
          // Show upper lid again
          lidUpper.style.opacity = '1';
        }
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'password';
          busy = false;
          
          // Restart random blinking
          clearTimeout(blinkTimeout);
          randomBlink();
        }, 150);
      }
    });
    
    // Clean up event listeners when needed
    return () => {
      clearTimeout(blinkTimeout);
    };
  });
});
