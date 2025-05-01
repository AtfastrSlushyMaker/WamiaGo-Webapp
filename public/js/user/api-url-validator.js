/**
 * API URL Validator and Fixer
 * 
 * This script runs on page load and checks all API route URLs to ensure:
 * 1. They have the correct prefix (/admin)
 * 2. All placeholders are properly formatted for JavaScript replacement ({id})
 * 3. URLs are properly structured and don't contain unresolved placeholders
 */

(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const DEBUG = true;
        if (DEBUG) console.log('API URL validator running...');
        
        // URLs to check
        const urlVars = [
            'apiRouteUrl',
            'userGetUrl',
            'userEditUrl',
            'userUpdateUrl',
            'userDeleteUrl', 
            'userDeleteApiUrl',
            'userCreateUrl'
        ];
        
        let fixedUrls = false;
        
        // Check and fix URLs
        urlVars.forEach(varName => {
            if (window[varName]) {
                let url = window[varName];
                let originalUrl = url;
                  // Fix 1: Ensure URLs have the correct /admin prefix if they should
                if ((url.startsWith('/users/') || url.includes('/users/api/')) && !url.includes('/admin/')) {
                    url = '/admin' + url;
                    fixedUrls = true;
                    if (DEBUG) console.log(`Fixed URL prefix for ${varName}: ${originalUrl} -> ${url}`);
                    
                    // Force update for critical paths
                    if (varName === 'userGetUrl') {
                        console.warn(`Critical path fixed: ${varName} was missing /admin prefix. This is required for user API requests to work.`);
                    }
                }
                
                // Fix 2: Ensure all placeholders are properly formatted 
                // Look for USER_ID, __id__, or other placeholders
                if (url.includes('USER_ID')) {
                    url = url.replace('USER_ID', '{id}');
                    fixedUrls = true;
                    if (DEBUG) console.log(`Standardized placeholder for ${varName}: USER_ID -> {id}`);
                }
                
                if (url.includes('__id__')) {
                    url = url.replace('__id__', '{id}');
                    fixedUrls = true;
                    if (DEBUG) console.log(`Standardized placeholder for ${varName}: __id__ -> {id}`);
                }
                
                // Fix 3: Check for misconfigured routes
                // If a URL for a user-specific endpoint doesn't have an ID placeholder, that's a problem
                if ((varName.includes('Get') || 
                     varName.includes('Edit') || 
                     varName.includes('Update') || 
                     varName.includes('Delete')) && !url.includes('{id}')) {
                    console.warn(`Warning: URL ${varName} = "${url}" appears to be missing an ID placeholder`);
                }
                
                // If the URL has been fixed, update it
                if (originalUrl !== url) {
                    window[varName] = url;
                    if (DEBUG) console.log(`- Updated ${varName} = "${url}"`);
                }
            } else {
                console.warn(`Warning: ${varName} is not defined`);
            }
        });
        
        // If we've fixed any URLs, add a visual indicator
        if (fixedUrls) {
            console.info("Fixed API URLs. Check the console logs for details.");
            
            // Add visual notification
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.bottom = '70px'; // Position above the URL placeholder warning
            notification.style.right = '10px';
            notification.style.backgroundColor = '#dfffdf';
            notification.style.color = '#257225';
            notification.style.padding = '10px 15px';
            notification.style.borderRadius = '4px';
            notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            notification.style.zIndex = '9999';
            notification.style.fontSize = '14px';
            notification.style.maxWidth = '300px';
            
            notification.innerHTML = `
                <strong>API URLs Fixed</strong>
                <p style="margin: 5px 0;">URL paths have been corrected to ensure proper API communication.</p>
                <button style="background: #257225; color: white; border: none; padding: 5px 10px; margin-top: 5px; cursor: pointer;">
                    Dismiss
                </button>
            `;
            
            document.body.appendChild(notification);
            
            notification.querySelector('button').addEventListener('click', function() {
                notification.remove();
            });
            
            setTimeout(() => notification.remove(), 10000);
        }
        
        // Check if API_ROUTES is accessible (only relevant after admin-user-management.js is loaded)
        setTimeout(() => {
            if (typeof API_ROUTES !== 'undefined') {
                if (DEBUG) console.log('API_ROUTES object is available:', API_ROUTES);
                
                // Check if the values in API_ROUTES match the window variables
                for (const key in API_ROUTES) {
                    const windowVarName = key === 'get' ? 'apiRouteUrl' : 
                                         `user${key.charAt(0).toUpperCase() + key.slice(1)}Url`;
                    
                    if (window[windowVarName] && API_ROUTES[key] !== window[windowVarName]) {
                        console.warn(`API_ROUTES.${key} (${API_ROUTES[key]}) doesn't match window.${windowVarName} (${window[windowVarName]})`);
                    }
                }
            }
        }, 1000);
    });
})();
