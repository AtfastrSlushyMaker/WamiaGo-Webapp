/**
 * User API Debug Script
 * 
 * This script helps diagnose issues with the User API endpoints.
 * It attempts to fetch user data from the API and displays detailed debugging information.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create debugging UI
    const debugContainer = document.createElement('div');
    debugContainer.className = 'debug-container';
    debugContainer.style.cssText = 'padding: 20px; background-color: #f8f9fa; border-radius: 8px; margin: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);';
    
    debugContainer.innerHTML = `
        <h3>User API Debugging</h3>
        <div class="form-group mb-3">
            <label for="userId">User ID to test:</label>
            <input type="number" id="userId" class="form-control" value="1">
        </div>
        <div class="form-group mb-3">
            <label for="apiEndpoint">API endpoint:</label>
            <input type="text" id="apiEndpoint" class="form-control" value="/admin/users/api/{id}">
        </div>
        <button id="testFetch" class="btn btn-primary mb-3">Test Fetch</button>
        <div id="results" class="mt-3 p-3 bg-light border rounded">
            <p>Click "Test Fetch" to fetch user data...</p>
        </div>
    `;
    
    document.body.appendChild(debugContainer);
    
    // Add event listener to the test button
    document.getElementById('testFetch').addEventListener('click', function() {
        const userId = document.getElementById('userId').value;
        const apiEndpointTemplate = document.getElementById('apiEndpoint').value;
        const apiEndpoint = apiEndpointTemplate.replace('{id}', userId);
        
        const resultsDiv = document.getElementById('results');
        resultsDiv.innerHTML = `<p>Fetching from: ${apiEndpoint}...</p>`;
        
        // Attempt to fetch user data
        fetch(apiEndpoint)
            .then(response => {
                const statusLine = `<p>Status: ${response.status} ${response.statusText}</p>`;
                const headersArray = Array.from(response.headers.entries());
                const headersText = headersArray.map(header => `${header[0]}: ${header[1]}`).join('<br>');
                
                resultsDiv.innerHTML = statusLine + `<p>Headers:<br>${headersText}</p>`;
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        resultsDiv.innerHTML += `<p>Response Body:</p><pre>${JSON.stringify(data, null, 2)}</pre>`;
                        return data;
                    });
                } else {
                    return response.text().then(text => {
                        resultsDiv.innerHTML += `<p>Response Body (not JSON):</p><pre>${text}</pre>`;
                    });
                }
            })
            .catch(error => {
                resultsDiv.innerHTML += `<p class="text-danger">Error: ${error.message}</p>`;
                console.error('Fetch error:', error);
            });
    });
});
