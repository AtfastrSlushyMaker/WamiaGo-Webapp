/**
 * Password Toggle with Eye Animation and Cursor Tracking
 * Complete implementation
 */
document.addEventListener('DOMContentLoaded', () => {
  console.log("Eye animation script initialized");
  
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
    
    // Mouse tracking for eye movement
    document.addEventListener('mousemove', (e) => {
      if (busy) return;
      if (input.type !== 'password') return; // Only follow when password is hidden
      
      const buttonRect = button.getBoundingClientRect();
      const buttonCenterX = buttonRect.left + buttonRect.width / 2;
      const buttonCenterY = buttonRect.top + buttonRect.height / 2;
      
      // Calculate distance from button center
      const distX = e.clientX - buttonCenterX;
      const distY = e.clientY - buttonCenterY;
      
      // Only move if mouse is somewhat close to the button (within 300px)
      const distance = Math.sqrt(distX * distX + distY * distY);
      if (distance < 300) {
        // Map distance to movement (max 30% movement)
        const moveX = (distX / 100) * 30;
        const moveY = (distY / 100) * 30;
        
        // Limit movement
        const limitedX = Math.min(30, Math.max(-30, moveX));
        const limitedY = Math.min(30, Math.max(-30, moveY));
        
        // Apply movement
        if (eye) {
          eye.style.transform = `translate(${limitedX}%, ${limitedY}%)`;
        }
      } else {
        // Reset position when mouse is far away
        if (eye) {
          eye.style.transform = 'translate(0, 0)';
        }
      }
    });
    
    // Handle click event
    button.addEventListener('click', () => {
      // Prevent multiple clicks during animation
      if (busy) return;
      busy = true;
      
      const isPassword = input.type === 'password';
      
      // Update ARIA state
      button.setAttribute('aria-pressed', isPassword);
      
      // Get the upper eyelid
      const lidUpper = button.querySelector('.lid--upper');
      
      if (isPassword) {
        // Show password (close eye)
        if (lidUpper) {
          lidUpper.style.transform = 'translateY(3.5px)';
        }
        
        // Reset eye position when showing password
        if (eye) {
          eye.style.transform = 'translate(0, 0)';
        }
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'text';
          busy = false;
          console.log("Password now visible");
        }, 200);
      } else {
        // Hide password (open eye)
        if (lidUpper) {
          lidUpper.style.transform = '';
        }
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'password';
          busy = false;
          console.log("Password now hidden");
        }, 200);
      }
    });
  });
});
