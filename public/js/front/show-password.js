/**
 * Original show-password-animation.html implementation
 */
document.addEventListener('DOMContentLoaded', () => {
  // Initialize all password toggle buttons
  document.querySelectorAll('.toggle').forEach(button => {
    // Find password input
    const input = button.previousElementSibling;
    
    // Ensure it's a password input
    if (!input || (input.type !== 'password' && input.type !== 'text')) return;
    
    // Animation state
    let busy = false;
    
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
        lidUpper.style.transform = 'translateY(3.5px)';
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'text';
          busy = false;
        }, 200);
      } else {
        // Hide password (open eye)
        lidUpper.style.transform = '';
        
        // Change input type after animation
        setTimeout(() => {
          input.type = 'password';
          busy = false;
        }, 200);
      }
    });
  });
});
