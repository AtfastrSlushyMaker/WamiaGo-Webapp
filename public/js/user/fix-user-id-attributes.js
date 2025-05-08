/**
 * User Data Attribute Fixer
 * 
 * This script ensures consistency in user ID data attributes
 * across the DOM by ensuring both data-id and data-user-id
 * attributes are present and have the correct value.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Run immediately on page load
    fixUserIdAttributes();
    
    // Also set up a mutation observer to catch any dynamically added elements
    const observer = new MutationObserver(function(mutations) {
        let shouldFix = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                for (let i = 0; i < mutation.addedNodes.length; i++) {
                    const node = mutation.addedNodes[i];
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Check if this node or any of its children might need fixing
                        if (node.querySelector('.edit-user, .view-user, .delete-user') || 
                            node.classList && (
                                node.classList.contains('edit-user') || 
                                node.classList.contains('view-user') || 
                                node.classList.contains('delete-user')
                            )) {
                            shouldFix = true;
                            break;
                        }
                    }
                }
            }
        });
        
        if (shouldFix) {
            fixUserIdAttributes();
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

/**
 * Fix user ID attributes in the DOM
 * Ensures both data-id and data-user-id are present and have the same numeric value
 */
function fixUserIdAttributes() {
    console.log('Fixing user ID data attributes');
    
    // Selectors for elements that should have user ID attributes
    const selectors = [
        '.edit-user',
        '.view-user',
        '.delete-user',
        '[data-user-id]',
        '[data-id]'
    ];
    
    // Process each element
    document.querySelectorAll(selectors.join(', ')).forEach(element => {
        // Get ID from either attribute
        let userId = element.dataset.userId || element.dataset.id;
        
        // Only proceed if we have a user ID
        if (userId) {
            // Parse to ensure it's numeric
            userId = parseInt(userId);
            
            if (!isNaN(userId)) {
                // Set both attributes with the numeric value
                element.dataset.id = userId;
                element.dataset.userId = userId;
            }
        }
    });
}
