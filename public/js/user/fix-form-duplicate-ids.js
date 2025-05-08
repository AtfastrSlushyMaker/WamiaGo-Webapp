
document.addEventListener('DOMContentLoaded', function() {
    const DEBUG = true;
    
    // Find and fix duplicate IDs
    fixDuplicateFormIds();
    
    // Set up a mutation observer to handle dynamically added elements
    const observer = new MutationObserver(function(mutations) {
        let shouldFix = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                for (let i = 0; i < mutation.addedNodes.length; i++) {
                    const node = mutation.addedNodes[i];
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        if (node.tagName === 'FORM' || node.querySelector('form')) {
                            shouldFix = true;
                            break;
                        }
                    }
                }
            }
        });
        
        if (shouldFix) {
            fixDuplicateFormIds();
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    /**
     * Fix duplicate form element IDs by appending a unique suffix to each duplicate
     */
    function fixDuplicateFormIds() {
        if (DEBUG) console.log('Checking for duplicate form IDs...');
        
        // Map all forms to their parent modal or container for context
        const forms = document.querySelectorAll('form');
        const formContainers = [];
        
        forms.forEach((form, formIndex) => {
            // Find parent container (modal or direct parent with ID)
            let container = form.closest('.modal') || form.closest('[id]');
            let containerName = container ? container.id : `form-${formIndex}`;
            
            // If the form itself has an ID, use that instead
            if (form.id) {
                formContainers.push({
                    form: form,
                    containerId: form.id
                });
            } else {
                formContainers.push({
                    form: form,
                    containerId: containerName
                });
            }
        });
        
        // Collect all element IDs
        const idMap = {};
        
        // First pass: collect all IDs
        formContainers.forEach(({ form, containerId }) => {
            const elements = form.querySelectorAll('[id]');
            
            elements.forEach(el => {
                const id = el.id;
                
                if (!idMap[id]) {
                    idMap[id] = [];
                }
                
                idMap[id].push({
                    element: el,
                    form: form,
                    containerId: containerId
                });
            });
        });
        
        // Second pass: fix duplicate IDs
        let fixedCount = 0;
        
        for (const [id, instances] of Object.entries(idMap)) {
            // Skip if there's only one instance of this ID
            if (instances.length <= 1) continue;
            
            if (DEBUG) console.log(`Found duplicate ID: "${id}" (${instances.length} instances)`);
            
            // Keep the first instance unchanged, rename the others
            for (let i = 1; i < instances.length; i++) {
                const { element, containerId } = instances[i];
                const newId = `${id}_${containerId}_${i}`;
                
                // Change ID
                element.id = newId;
                
                // If there are labels pointing to this element, update their 'for' attribute
                const labels = document.querySelectorAll(`label[for="${id}"]`);
                labels.forEach(label => {
                    // Only update labels that are in the same form
                    if (label.closest('form') === element.closest('form')) {
                        label.setAttribute('for', newId);
                    }
                });
                
                fixedCount++;
                
                if (DEBUG) console.log(`  - Renamed to: "${newId}"`);
            }
        }
        
        if (DEBUG && fixedCount > 0) {
            console.log(`Fixed ${fixedCount} duplicate element IDs across ${forms.length} forms`);
        } else if (DEBUG) {
            console.log('No duplicate IDs found after previous fixes');
        }
    }
});
