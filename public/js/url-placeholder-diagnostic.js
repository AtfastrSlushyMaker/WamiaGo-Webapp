/**
 * URL Placeholder Diagnostic Tool
 * This script helps identify and fix issues with API URL placeholders
 * by checking for literal 'USER_ID' strings in URLs or other placeholders
 * that aren't being properly replaced.
 */

(function() {
    // Run when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('URL Placeholder Diagnostic Tool running...');
        
        // Check global window variables for API URLs
        const urlVariables = [
            'apiRouteUrl',
            'userGetUrl',
            'userEditUrl',
            'userUpdateUrl',
            'userDeleteUrl',
            'userDeleteApiUrl',
            'userCreateUrl'
        ];
        
        // List of valid placeholders to ignore (these are expected in URL templates)
        const validPlaceholders = ['{id}'];
        
        const issues = [];
        
        // Check each variable
        urlVariables.forEach(varName => {
            if (window[varName]) {
                const url = window[varName];
                
                // Check for 'USER_ID' literal
                if (url.includes('USER_ID')) {
                    issues.push({
                        variable: varName,
                        url: url,
                        issue: "Contains literal 'USER_ID' string"
                    });
                }
                
                // Check for potential unresolved placeholders
                const placeholderRegex = /__[a-z0-9_]+__|{[a-z0-9_]+}|\[\w+\]/gi;
                const placeholders = url.match(placeholderRegex);
                
                if (placeholders) {
                    // Filter out valid placeholders
                    const invalidPlaceholders = placeholders.filter(
                        placeholder => !validPlaceholders.includes(placeholder)
                    );
                    
                    // Only report as an issue if we found invalid placeholders
                    if (invalidPlaceholders.length > 0) {
                        issues.push({
                            variable: varName,
                            url: url,
                            issue: `Contains potential unresolved placeholders: ${invalidPlaceholders.join(', ')}`
                        });
                    }
                }
            }
        });
        
        // If issues were found, log them to the console
        if (issues.length > 0) {
            console.warn('URL Placeholder issues detected:');
            console.table(issues);
            
            // Also check if API_ROUTES is defined in the page
            if (window.API_ROUTES) {
                console.log('Current API_ROUTES values:');
                console.table(window.API_ROUTES);
            }
            
            // Add a visual notification for developers
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.bottom = '10px';
            notification.style.right = '10px';
            notification.style.backgroundColor = '#ffebee';
            notification.style.color = '#c62828';
            notification.style.padding = '10px 15px';
            notification.style.borderRadius = '4px';
            notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            notification.style.zIndex = '9999';
            notification.style.fontSize = '14px';
            notification.style.maxWidth = '300px';
            
            notification.innerHTML = `
                <strong>URL Placeholder Issues Detected</strong>
                <p style="margin: 5px 0;">Found ${issues.length} URL issues that may cause API requests to fail.</p>
                <p style="margin: 5px 0;">Check the browser console for details.</p>
                <button style="background: #c62828; color: white; border: none; padding: 5px 10px; margin-top: 5px; cursor: pointer;">
                    Dismiss
                </button>
            `;
            
            document.body.appendChild(notification);
            
            // Add a click handler for the dismiss button
            notification.querySelector('button').addEventListener('click', function() {
                notification.remove();
            });
            
            // Auto-remove after 30 seconds
            setTimeout(() => {
                notification.remove();
            }, 30000);
        } else {
            console.log('No URL placeholder issues detected.');
        }
    });
})();
