/**
 * Modal Focus Fix Script
 * 
 * This script fixes issues with Bootstrap modal focus and accessibility by:
 * 1. Ensuring proper ARIA attributes are set
 * 2. Managing focus correctly when opening/closing modals
 * 3. Preventing backdrop issues with multiple modals
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fix for modals with focus and ARIA issues
    const fixModalFocusIssues = function() {
        // Get all modal elements
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            // Fix for modal setup issue - ensure events are properly set
            modal.addEventListener('show.bs.modal', function() {
                // Make sure ARIA attributes are correctly set
                this.setAttribute('aria-modal', 'true');
                this.setAttribute('role', 'dialog');
                this.removeAttribute('aria-hidden');
                
                // Fix backdrop issues
                const backdropClass = this.getAttribute('data-bs-backdrop');
                if (!backdropClass) {
                    this.setAttribute('data-bs-backdrop', 'true');
                }
            });
            
            // Reset ARIA attributes on hidden
            modal.addEventListener('hidden.bs.modal', function() {
                this.setAttribute('aria-hidden', 'true');
                this.removeAttribute('aria-modal');
                
                // Return focus to the element that triggered the modal, if available
                const lastActiveElement = this.dataset.lastActiveElement;
                if (lastActiveElement) {
                    const element = document.querySelector(lastActiveElement);
                    if (element) {
                        element.focus();
                    }
                }
            });
            
            // Store the element that was focused before opening the modal
            modal.addEventListener('show.bs.modal', function() {
                this.dataset.lastActiveElement = document.activeElement ? '#' + document.activeElement.id : null;
            });
        });
    };
    
    // Run the fix
    fixModalFocusIssues();
    
    // Also fix any dynamically added modals
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                for (let i = 0; i < mutation.addedNodes.length; i++) {
                    const node = mutation.addedNodes[i];
                    if (node.nodeType === Node.ELEMENT_NODE && 
                        (node.classList.contains('modal') || node.querySelector('.modal'))) {
                        fixModalFocusIssues();
                        break;
                    }
                }
            }
        });
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
    
    console.log('Modal focus and accessibility fixes applied');
});
