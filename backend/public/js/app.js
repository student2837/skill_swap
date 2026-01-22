/**
 * Common app utilities
 * This file provides common utility functions used across the application
 */

// Global token expiration handler
// This listens for token expiration events from the API client and redirects to login
window.addEventListener('tokenExpired', function(event) {
    console.warn('Token expired:', event.detail.message);
    
    // Clear all auth-related data
    localStorage.removeItem('auth_token');
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // Show a message to the user
    alert('Your session has expired. Please log in again.');
    
    // Redirect to login page (or register for new users)
    if (window.location.pathname !== '/login.html' && !window.location.pathname.includes('login.html') &&
        window.location.pathname !== '/register.html' && !window.location.pathname.includes('register.html')) {
        window.location.href = 'register.html';
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
            
            // Clear all auth data
            localStorage.removeItem('auth_token');
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.clear();
            
            // Try to call logout API if API client is available
            if (typeof ApiClient !== 'undefined' && window.apiClient) {
                try {
                    await window.apiClient.logout();
                } catch (err) {
                    console.error('Logout API error:', err);
                }
            }
            
            // Redirect to login/register
            window.location.href = 'register.html';
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

