/**
 * Common app utilities
 * This file provides common utility functions used across the application
 */

// Global token expiration handler
// This listens for token expiration events from the API client and redirects to login
// Only handle if not already on login/register pages
let isLoggingOut = false;
window.addEventListener('tokenExpired', function(event) {
    // Don't show alert if user is intentionally logging out
    if (isLoggingOut) {
        return;
    }
    
    console.warn('Token expired:', event.detail.message);
    
    // Clear all auth-related data
    localStorage.removeItem('auth_token');
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // Only show alert and redirect if not already on auth pages
    const currentPath = window.location.pathname;
    const isAuthPage = currentPath.includes('/login') || currentPath.includes('/register') || 
                       currentPath === '/' || currentPath === '';
    
    if (!isAuthPage) {
        // Show a message to the user
        alert('Your session has expired. Please log in again.');
        
        // Redirect to login page using current origin
        const loginUrl = window.location.origin + '/login';
        window.location.href = loginUrl;
    }
});

/**
 * Filter sidebar navigation for admin users
 * Hides restricted links and adds Admin Panel link
 * @param {ApiClient} apiClient - Initialized API client instance
 */
async function filterSidebarForAdmin(apiClient) {
    try {
        const user = await apiClient.getUser();
        if (user.is_admin) {
            const sidebarNav = document.querySelector('.sidebar-nav');
            if (!sidebarNav) return;

            // Links to hide for admins
            const restrictedLinks = [
                'dashboard.html',
                'my-skills.html',
                'requests.html',
                'favorites.html',
                'review.html',
                'credits.html'
            ];

            // Hide restricted links
            restrictedLinks.forEach(href => {
                const link = sidebarNav.querySelector(`a[href="${href}"]`);
                if (link) {
                    link.style.display = 'none';
                }
            });

            // Add Admin Panel link if not present
            if (!sidebarNav.querySelector('a[href="admin-dashboard.html"]')) {
                const browseLink = sidebarNav.querySelector('a[href="browse.html"]');
                if (browseLink) {
                    const adminLink = document.createElement('a');
                    adminLink.href = 'admin-dashboard.html';
                    adminLink.className = 'nav-item';
                    adminLink.textContent = '⚙️ Admin Panel';
                    browseLink.parentNode.insertBefore(adminLink, browseLink);
                }
            }
        }
    } catch (err) {
        console.error("Error filtering sidebar:", err);
    }
}

// Handle logout button clicks globally
document.addEventListener('DOMContentLoaded', function() {
    // Find all logout buttons/links
    const logoutElements = document.querySelectorAll('.logout-btn, [href*="logout"], [onclick*="logout"]');
    logoutElements.forEach(element => {
        element.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Set flag to prevent tokenExpired event from showing alert
            isLoggingOut = true;
            
            // Clear all auth data first
            localStorage.removeItem('auth_token');
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.clear();
            
            // Try to call logout API if API client is available
            // Suppress errors since we're logging out anyway
            if (typeof ApiClient !== 'undefined' && window.apiClient) {
                try {
                    // Temporarily remove token to prevent 401 errors
                    const tempToken = window.apiClient.token;
                    window.apiClient.token = null;
                    await window.apiClient.post('/logout');
                } catch (err) {
                    // Ignore errors during logout - token might already be expired
                    console.log('Logout API call completed (errors ignored during logout)');
                }
            }
            
            // Redirect to login page using current origin
            const loginUrl = window.location.origin + '/login';
            window.location.href = loginUrl;
        });
    });
});

// Toast notification function (used by js-show-toast class)
document.addEventListener('DOMContentLoaded', function() {
    // Handle toast notifications
    const toastElements = document.querySelectorAll('.js-show-toast');
    toastElements.forEach(element => {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-toast-message') || 'Notification';
            alert(message); // Simple alert - can be replaced with a toast library
        });
    });

    // Handle toast on submit
    const toastSubmitElements = document.querySelectorAll('.js-show-toast-on-submit');
    toastSubmitElements.forEach(element => {
        element.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = this.getAttribute('data-toast-message') || 'Form submitted';
            alert(message); // Simple alert - can be replaced with a toast library
        });
    });
});

