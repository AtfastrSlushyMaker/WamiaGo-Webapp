/**
 * Test functions for the confetti animation
 * This script provides utility functions to test animations without form submission
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add a hidden test button
    const testContainer = document.createElement('div');
    testContainer.style.position = 'fixed';
    testContainer.style.bottom = '10px';
    testContainer.style.right = '10px';
    testContainer.style.zIndex = '9999';
    testContainer.style.opacity = '0.3';
    testContainer.style.transition = 'opacity 0.3s ease';
    
    testContainer.onmouseover = function() {
        this.style.opacity = '1';
    };
    
    testContainer.onmouseout = function() {
        this.style.opacity = '0.3';
    };
    
    const testButton = document.createElement('button');
    testButton.textContent = '🎉 Test Confetti';
    testButton.style.padding = '5px 10px';
    testButton.style.background = 'var(--primary-color, #3a86ff)';
    testButton.style.color = 'white';
    testButton.style.border = 'none';
    testButton.style.borderRadius = '5px';
    testButton.style.cursor = 'pointer';
    testButton.style.fontSize = '12px';
    
    testButton.onclick = function() {
        if (window.showSuccessWithConfetti) {
            window.showSuccessWithConfetti('Test confetti animation!');
        } else {
            console.error('Confetti animation function not available');
        }
    };
    
    testContainer.appendChild(testButton);
    document.body.appendChild(testContainer);
    
    // Test keyboard shortcut (Ctrl+Shift+C)
    document.addEventListener('keydown', function(event) {
        if (event.ctrlKey && event.shiftKey && event.key === 'C') {
            if (window.showSuccessWithConfetti) {
                window.showSuccessWithConfetti('Confetti triggered with keyboard shortcut!');
            }
        }
    });
    
    console.log('Confetti test tools loaded. Press Ctrl+Shift+C to test confetti or use the test button.');
});
