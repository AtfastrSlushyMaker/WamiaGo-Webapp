/**
 * Exact Eye Animation with Text Animation
 * Matches the password-demo.html implementation
 */
document.addEventListener('DOMContentLoaded', () => {
  console.log("Eye animation with text effect initialized");
  
  // Initialize all password toggle buttons
  document.querySelectorAll('.toggle-password').forEach(button => {
    // Find password input
    const input = button.previousElementSibling;
    
    // Ensure it's a password input
    if (!input || (input.type !== 'password' && input.type !== 'text')) return;
    
    // Animation state
    let busy = false;

    // Eye tracking for cursor following
    const eye = button.querySelector('.eye');
    
    // Only follow cursor when password is hidden
    document.addEventListener('mousemove', (e) => {
      if (busy || input.type !== 'password') return;
      
      // Get the input bounds
      const inputRect = input.getBoundingClientRect();
      const buttonRect = button.getBoundingClientRect();
      
      // Define the area where the eye should track
      const buffer = 50;
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
        
        // Calculate movement percentage (max 30%)
        const moveX = Math.min(30, Math.max(-30, (distX / 100) * 30));
        const moveY = Math.min(30, Math.max(-30, (distY / 100) * 30));
        
        // Apply movement
        if (eye) {
          eye.style.transition = 'none';
          eye.style.transform = `translate(${moveX}%, ${moveY}%)`;
          
          // Re-enable transition after movement for smooth return
          setTimeout(() => {
            eye.style.transition = 'transform 0.2s ease';
          }, 5);
        }
      } else {
        // Return to center position
        if (eye) {
          eye.style.transition = 'transform 0.2s ease';
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
      
      // Get upper eyelid
      const lidUpper = button.querySelector('.lid--upper');
      
      if (isPassword) {
        // Show password (close eye)
        if (lidUpper) {
          lidUpper.style.transform = 'translateY(3.5px)';
        }
        
        // Hide eye completely (will override transform)
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
          
          // Add ripple effect
          input.classList.add('password-reveal-animation');
          
          // Remove ripple class after animation
          setTimeout(() => {
            input.classList.remove('password-reveal-animation');
          }, 600);
          
          busy = false;
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
        }, 200);
      }
    });
  });
});
