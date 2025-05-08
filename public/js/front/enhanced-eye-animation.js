/**
 * Enhanced Eye Animation with Automatic Blinking & Improved Text Animation
 * Implements 5-second blink interval and enhanced text animation effects
 */
document.addEventListener('DOMContentLoaded', () => {
  console.log("Enhanced eye animation with text effect initialized");
  
  // Initialize all password toggle buttons
  document.querySelectorAll('.toggle-password').forEach(button => {
    // Find password input
    const input = button.previousElementSibling;
    
    // Ensure it's a password input
    if (!input || (input.type !== 'password' && input.type !== 'text')) return;
    
    // Animation state
    let busy = false;
    
    // Eye elements
    const eye = button.querySelector('.eye');
    const lidUpper = button.querySelector('.lid--upper');
    
    // Setup automatic blinking
    let blinkInterval;
    
    // Function to trigger a blink
    const triggerBlink = () => {
      // Don't blink if we're already busy with another animation
      if (busy || input.type !== 'password') return;
      
      // Quick blink animation
      if (lidUpper) {
        // Start blink - close eye
        lidUpper.style.transform = 'translateY(3.5px)';
        
        // End blink - open eye after short delay
        setTimeout(() => {
          lidUpper.style.transform = '';
        }, 200);
      }
    };
    
    // Start the blink interval (every 5 seconds)
    const startBlinking = () => {
      // Clear any existing interval
      clearInterval(blinkInterval);
      
      // Set new interval for blinking every 5 seconds
      blinkInterval = setInterval(triggerBlink, 5000);
      
      // Initial blink after a random delay (1-3 seconds)
      setTimeout(triggerBlink, Math.random() * 2000 + 1000);
    };
    
    // Start blinking immediately
    startBlinking();
    
    // Eye tracking for cursor following with enhanced sensitivity
    document.addEventListener('mousemove', (e) => {
      if (busy || input.type !== 'password') return;
      
      // Get the input bounds
      const inputRect = input.getBoundingClientRect();
      const buttonRect = button.getBoundingClientRect();
      
      // Define the area where the eye should track
      const buffer = 70; // Increased tracking area
      if (
        e.clientX >= inputRect.left - buffer && 
        e.clientX <= inputRect.right + buffer && 
        e.clientY >= inputRect.top - buffer && 
        e.clientY <= inputRect.bottom + buffer
      ) {
        // Calculate center of button
        const buttonCenterX = buttonRect.left + buttonRect.width / 2;
        const buttonCenterY = buttonRect.top + buttonRect.height / 2;
        
        // Calculate distance from button center
        const distX = e.clientX - buttonCenterX;
        const distY = e.clientY - buttonCenterY;
        
        // Calculate movement percentage (max 35% - slightly increased)
        const moveX = Math.min(35, Math.max(-35, (distX / 100) * 35));
        const moveY = Math.min(35, Math.max(-35, (distY / 100) * 35));
        
        // Apply movement - smoother transition
        if (eye) {
          eye.style.transition = 'transform 0.1s ease'; // Faster, smoother tracking
          eye.style.transform = `translate(${moveX}%, ${moveY}%)`;
        }
      } else {
        // Return to center position
        if (eye) {
          eye.style.transition = 'transform 0.3s ease';
          eye.style.transform = 'translate(0, 0)';
        }
      }
    });
    
    // Handle click event
    button.addEventListener('click', () => {
      // Prevent clicks during animation
      if (busy) return;
      busy = true;
      
      const isPassword = input.type === 'password';
      
      // Update button state
      button.setAttribute('aria-pressed', isPassword);
      
      // Stop blinking during animation
      clearInterval(blinkInterval);
      
      if (isPassword) {
        // Show password (close eye)
        if (lidUpper) {
          lidUpper.style.transform = 'translateY(3.5px)';
        }
        
        // Hide eye completely
        if (eye) {
          eye.style.opacity = '0';
        }
        
        // Change input type after animation
        setTimeout(() => {
          // Store original value
          const value = input.value;
          
          // Change type to text
          input.type = 'text';
          
          // Re-set value to trigger the CSS animation
          input.value = value;
          
          // Add enhanced reveal animation
          input.classList.add('password-reveal-animation');
          
          // Add character-by-character animation class
          input.classList.add('text-character-animation');
          
          // Remove animation classes after they complete
          setTimeout(() => {
            input.classList.remove('password-reveal-animation');
            input.classList.remove('text-character-animation');
            busy = false;
          }, 800); // Extended duration for longer animation
        }, 200);
      } else {
        // Hide password (open eye)
        if (lidUpper) {
          lidUpper.style.transform = '';
        }
        
        // Make eye visible again
        if (eye) {
          eye.style.opacity = '1';
        }
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'password';
          busy = false;
          
          // Resume blinking
          startBlinking();
        }, 200);
      }
    });
  });
});
