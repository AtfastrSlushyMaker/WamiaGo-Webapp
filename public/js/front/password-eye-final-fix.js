/**
 * Final Password Eye Animation Fix
 * Complete solution to hide the eye and upper lid when password is visible
 */
document.addEventListener('DOMContentLoaded', () => {
  console.log("Final eye animation fix initialized");
  
  // Initialize all password toggle buttons
  document.querySelectorAll('.toggle-password').forEach(button => {
    // Find password input
    const input = button.previousElementSibling;
    
    // Ensure it's a password input
    if (!input || (input.type !== 'password' && input.type !== 'text')) return;
    
    // Animation state
    let busy = false;
    
    // Get eye element
    const eye = button.querySelector('.eye');
    const lidUpper = button.querySelector('.lid--upper');
    const lidLower = button.querySelector('.lid--lower');
    
    // Mouse tracking for eye movement
    document.addEventListener('mousemove', (e) => {
      if (busy || input.type !== 'password') return;
      
      // Get the button's position
      const buttonRect = button.getBoundingClientRect();
      const inputRect = input.getBoundingClientRect();
      
      // Check if mouse is near this specific input field
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
        
        // Map distance to movement
        const moveX = (distX / 100) * 30;
        const moveY = (distY / 100) * 30;
        
        // Limit movement
        const limitedX = Math.min(30, Math.max(-30, moveX));
        const limitedY = Math.min(30, Math.max(-30, moveY));
        
        // Apply movement - instant without transition
        if (eye) {
          eye.style.transition = 'none';
          eye.style.transform = `translate(${limitedX}%, ${limitedY}%)`;
          
          // Re-enable transition after movement
          setTimeout(() => {
            eye.style.transition = 'transform 0.2s ease';
          }, 5);
        }
      } else {
        // Return eye to center position
        if (eye) {
          eye.style.transition = 'transform 0.2s ease';
          eye.style.transform = 'translate(0, 0)';
        }
      }
    });
    
    // Create proxy element for text scrambling
    const PROXY = document.createElement('div');
    PROXY.style.display = 'none';
    document.body.appendChild(PROXY);
    
    // Characters for scrambling effect
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`~,.<>?/;":][}{+_)(*&^%$#@!±=-§';
    
    // Random blink for each eye
    let blinkTimeout;
    
    function randomBlink() {
      // Only blink when password is hidden
      if (input.type !== 'password') return;
      
      // Random delay between 2 and 6 seconds
      const delay = Math.random() * 4000 + 2000;
      
      blinkTimeout = setTimeout(() => {
        // Quick blink animation
        if (lidUpper && input.type === 'password') {
          lidUpper.style.transform = 'translateY(3.5px)';
          
          // Open eye after blinking
          setTimeout(() => {
            if (input.type === 'password') { // Check again in case it changed
              lidUpper.style.transform = '';
            }
          }, 150);
        }
        
        // Schedule next blink
        randomBlink();
      }, delay);
    }
    
    // Start random blinking
    randomBlink();
    
    // Handle click event with text animation
    button.addEventListener('click', () => {
      // Prevent multiple clicks during animation
      if (busy) return;
      busy = true;
      
      const isPassword = input.type === 'password';
      const val = input.value; // Store original value
      
      // Update ARIA state
      button.setAttribute('aria-pressed', isPassword);
      
      if (isPassword) {
        // Show password (close eye)
        if (lidUpper) {
          // Completely hide upper lid when showing password
          lidUpper.style.opacity = '0';
          lidUpper.style.visibility = 'hidden'; 
        }
        
        // Completely hide the eye
        if (eye) {
          eye.style.opacity = '0';
          eye.style.visibility = 'hidden';
        }
        
        // Reset eye position
        if (eye) {
          eye.style.transform = 'translate(0, 0)';
        }
        
        // Text reveal animation with character by character reveal
        // Start by changing to text type
        setTimeout(() => {
          input.type = 'text';
          input.classList.add('password-revealed');
          input.classList.add('password-ripple');
          
          // Text reveal animation using scrambling (just visual effect)
          let currentText = '';
          const revealSpeed = 30; // ms per character
          const totalTime = val.length * revealSpeed;
          const startTime = Date.now();
          
          const animateReveal = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(1, elapsed / totalTime);
            const charsToReveal = Math.floor(val.length * progress);
            
            // Build revealed text with scrambling effect at the end
            let newText = val.substring(0, charsToReveal);
            if (charsToReveal < val.length) {
              // Add scrambled char at the end
              newText += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            
            // Update if text changed
            if (newText !== currentText) {
              currentText = newText;
              input.value = newText + '•'.repeat(val.length - newText.length);
            }
            
            // Continue animation if not complete
            if (progress < 1) {
              requestAnimationFrame(animateReveal);
            } else {
              // Animation complete, restore actual value
              input.value = val;
              busy = false;
            }
          };
          
          // Start animation if there's text to reveal
          if (val.length > 0) {
            animateReveal();
          } else {
            busy = false;
          }
          
          // Remove ripple effect after animation
          setTimeout(() => {
            input.classList.remove('password-ripple');
          }, 600);
        }, 200);
      } else {
        // Hide password (open eye)
        if (lidUpper) {
          // Restore upper lid visibility when hiding password
          lidUpper.style.opacity = '1';
          lidUpper.style.visibility = 'visible';
          lidUpper.style.transform = '';
        }
        
        // Restore eye visibility
        if (eye) {
          eye.style.opacity = '1';
          eye.style.visibility = 'visible';
        }
        
        // Text scrambling animation when hiding password
        setTimeout(() => {
          let currentText = '';
          const hideSpeed = 30; // ms per character
          const totalTime = val.length * hideSpeed;
          const startTime = Date.now();
          
          const animateHide = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(1, elapsed / totalTime);
            const charsToHide = Math.floor(val.length * progress);
            
            // Build text with increasing number of dots
            let newText = val.substring(0, val.length - charsToHide);
            
            // Update if text changed
            if (newText !== currentText) {
              currentText = newText;
              input.value = newText + '•'.repeat(charsToHide);
            }
            
            // Continue animation if not complete
            if (progress < 1) {
              requestAnimationFrame(animateHide);
            } else {
              // Animation complete, change type and restore value
              input.type = 'password';
              input.value = val;
              input.classList.remove('password-revealed');
              busy = false;
              
              // Restart random blinking
              clearTimeout(blinkTimeout);
              randomBlink();
            }
          };
          
          // Start animation if there's text to hide
          if (val.length > 0) {
            animateHide();
          } else {
            input.type = 'password';
            busy = false;
          }
        }, 200);
      }
    });
    
    // Clean up event listeners when needed
    return () => {
      clearTimeout(blinkTimeout);
    };
  });
});
