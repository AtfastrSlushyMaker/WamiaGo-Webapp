/**
 * User Account Status Manager
 * This script helps with handling banned and suspended users
 */

document.addEventListener('DOMContentLoaded', function() {
    // Constants
    const BANNED = 'BANNED';
    const SUSPENDED = 'SUSPENDED';
    const ACTIVE = 'ACTIVE';
    
    // Global user status check
    function checkUserStatus() {
        // Parse status from session storage if available
        const userStatus = sessionStorage.getItem('user_account_status');
        const userRole = sessionStorage.getItem('user_role');
        
        if (userStatus === BANNED) {
            showBannedUserAlert();
            return false;
        }
        
        if (userStatus === SUSPENDED) {
            showSuspendedUserAlert();
            return false;
        }
        
        return true;
    }
    
    // Show alert for banned users
    function showBannedUserAlert() {
        const alertHtml = `
            <div class="banned-user-alert" style="display: none;">
                <div class="banned-user-content">
                    <div class="banned-icon">
                        <i class="fas fa-ban"></i>
                    </div>
                    <h3>Account Banned</h3>
                    <p>Your account has been banned. Please contact the administrator for more information.</p>
                    <button type="button" id="banned-logout-btn">Logout</button>
                </div>
            </div>
        `;
        
        // Insert the alert into the page
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Add styles for the alert
        const style = document.createElement('style');
        style.textContent = `
            .banned-user-alert {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.8);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.3s ease;
            }
            
            .banned-user-content {
                background: white;
                border-radius: 15px;
                padding: 30px;
                text-align: center;
                max-width: 400px;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
                animation: slideIn 0.4s ease;
            }
            
            .banned-icon {
                font-size: 5rem;
                color: #ef4444;
                margin-bottom: 20px;
            }
            
            .banned-user-content h3 {
                color: #ef4444;
                font-size: 1.8rem;
                margin-bottom: 15px;
            }
            
            .banned-user-content p {
                color: #4b5563;
                margin-bottom: 25px;
                font-size: 1.1rem;
                line-height: 1.5;
            }
            
            #banned-logout-btn {
                background: #ef4444;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 50px;
                font-weight: bold;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            #banned-logout-btn:hover {
                background: #dc2626;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideIn {
                from { transform: translateY(-30px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        // Show the alert with animation
        setTimeout(() => {
            document.querySelector('.banned-user-alert').style.display = 'flex';
        }, 100);
        
        // Handle logout button click
        document.getElementById('banned-logout-btn').addEventListener('click', function() {
            sessionStorage.removeItem('user_account_status');
            sessionStorage.removeItem('user_role');
            window.location.href = '/logout';
        });
    }
    
    // Show alert for suspended users
    function showSuspendedUserAlert() {
        const alertHtml = `
            <div class="suspended-user-alert" style="display: none;">
                <div class="suspended-user-content">
                    <div class="suspended-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <h3>Account Suspended</h3>
                    <p>Your account has been temporarily suspended. Please contact the administrator for more information.</p>
                    <button type="button" id="suspended-logout-btn">Logout</button>
                </div>
            </div>
        `;
        
        // Insert the alert into the page
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Add styles for the alert
        const style = document.createElement('style');
        style.textContent = `
            .suspended-user-alert {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.8);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.3s ease;
            }
            
            .suspended-user-content {
                background: white;
                border-radius: 15px;
                padding: 30px;
                text-align: center;
                max-width: 400px;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
                animation: slideIn 0.4s ease;
            }
            
            .suspended-icon {
                font-size: 5rem;
                color: #f59e0b;
                margin-bottom: 20px;
            }
            
            .suspended-user-content h3 {
                color: #f59e0b;
                font-size: 1.8rem;
                margin-bottom: 15px;
            }
            
            .suspended-user-content p {
                color: #4b5563;
                margin-bottom: 25px;
                font-size: 1.1rem;
                line-height: 1.5;
            }
            
            #suspended-logout-btn {
                background: #f59e0b;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 50px;
                font-weight: bold;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            #suspended-logout-btn:hover {
                background: #d97706;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideIn {
                from { transform: translateY(-30px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        // Show the alert with animation
        setTimeout(() => {
            document.querySelector('.suspended-user-alert').style.display = 'flex';
        }, 100);
        
        // Handle logout button click
        document.getElementById('suspended-logout-btn').addEventListener('click', function() {
            sessionStorage.removeItem('user_account_status');
            sessionStorage.removeItem('user_role');
            window.location.href = '/logout';
        });
    }
    
    // Check user status on page load
    checkUserStatus();
});
