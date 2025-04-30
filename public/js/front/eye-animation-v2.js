/**
 * Enhanced Password Toggle with Eye Animation and Cursor Tracking - v2
 * Improved performance and smoother animation
 */
document.addEventListener('DOMContentLoaded', () => {
  console.log("Enhanced eye animation initialized");
  
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
    
    // More responsive movement - throttled for performance
    let lastMoveTime = 0;
    const moveThrottle = 10; // ms
    
    // Update mask and improve eye visibility
    const svg = button.querySelector('svg');
    if (svg) {
      // Fix masks for better animation
      const masks = svg.querySelectorAll('mask');
      masks.forEach(mask => {
        if (mask.id && mask.id.includes('eye-closed')) {
          // Make sure closed eye mask is properly set up
          const path = mask.querySelector('path');
          if (path) {
            path.setAttribute('d', 'M1 12C1 12 5 20 12 20C19 20 23 12 23 12V20H12H1V12Z');
            path.setAttribute('fill', '#D9D9D9');
          }
        }
      });
    }
    
    // Mouse tracking for eye movement with improved performance
    document.addEventListener('mousemove', (e) => {
      const now = Date.now();
      if (now - lastMoveTime < moveThrottle) return; // throttle
      lastMoveTime = now;
      
      // Skip if busy with animation or eye is closed (password visible)
      if (busy || input.type !== 'password') return;
      
      const buttonRect = button.getBoundingClientRect();
      const buttonCenterX = buttonRect.left + buttonRect.width / 2;
      const buttonCenterY = buttonRect.top + buttonRect.height / 2;
      
      // Calculate distance from button center
      const distX = e.clientX - buttonCenterX;
      const distY = e.clientY - buttonCenterY;
      
      // Only move if mouse is somewhat close to the button
      const distance = Math.sqrt(distX * distX + distY * distY);
      if (distance < 250) {
        // Map distance to movement with more responsive curve
        const moveX = (distX / 80) * 25;
        const moveY = (distY / 80) * 25;
        
        // Limit movement
        const limitedX = Math.min(25, Math.max(-25, moveX));
        const limitedY = Math.min(25, Math.max(-25, moveY));
        
        // Apply movement without animation delay for smoothness
        if (eye) {
          eye.style.transition = 'none';
          eye.style.transform = `translate(${limitedX}%, ${limitedY}%)`;
          
          // Re-enable transition after a short delay
          setTimeout(() => {
            eye.style.transition = 'transform 0.1s cubic-bezier(0.4, 0, 0.2, 1)';
          }, 5);
        }
      } else {
        // Reset position with smooth transition when mouse is far away
        if (eye) {
          eye.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
          eye.style.transform = 'translate(0, 0)';
        }
      }
    });
    
    // Handle click event with improved animation
    button.addEventListener('click', () => {
      // Prevent multiple clicks during animation
      if (busy) return;
      busy = true;
      
      const isPassword = input.type === 'password';
      
      // Update ARIA state immediately
      button.setAttribute('aria-pressed', isPassword);
      
      if (isPassword) {
        // Show password (close eye)
        if (lidUpper) lidUpper.style.transform = 'translateY(5px)';
        if (lidLower) lidLower.style.transform = 'translateY(-2px)';
        
        // Reset eye position instantly and adjust opacity
        if (eye) {
          eye.style.transition = 'transform 0.15s ease, opacity 0.15s ease';
          eye.style.transform = 'translate(0, 0)';
          eye.style.opacity = '0.4';
        }
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'text';
          busy = false;
        }, 150); // reduced from 200ms for responsiveness
      } else {
        // Hide password (open eye)
        if (lidUpper) lidUpper.style.transform = '';
        if (lidLower) lidLower.style.transform = '';
        
        // Restore eye opacity
        if (eye) {
          eye.style.opacity = '1';
        }
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'password';
          busy = false;
        }, 150); // reduced from 200ms for responsiveness
      }
    });
  });
});
