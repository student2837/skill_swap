<script>
  // Sidebar initialization - instant display, no refresh
  (function initSidebar() {
    // Get sidebars immediately
    const userSidebar = document.getElementById('userSidebarComponent');
    const adminSidebar = document.getElementById('adminSidebarComponent');
    
    if (!userSidebar && !adminSidebar) {
      // Retry if not ready
      setTimeout(initSidebar, 10);
      return;
    }
    
    // Check cache for instant display (synchronous)
    const cachedUser = localStorage.getItem('user');
    let isAdmin = false;
    
    if (cachedUser) {
      try {
        const user = JSON.parse(cachedUser);
        isAdmin = user.is_admin === true;
      } catch (e) {
        // Invalid cache
      }
    }
    
    // Show appropriate sidebar immediately (no delay)
    if (isAdmin && adminSidebar) {
      if (userSidebar) userSidebar.style.display = 'none';
      adminSidebar.style.display = 'block';
    } else if (userSidebar) {
      userSidebar.style.display = 'block';
      if (adminSidebar) adminSidebar.style.display = 'none';
    }
    
    // Verify with API in background (doesn't block display)
    (async function verifySidebar() {
      try {
        if (typeof ApiClient === 'undefined') {
          // Wait for ApiClient
          setTimeout(verifySidebar, 50);
          return;
        }
        
        const API = "{{ url('/api') }}";
        const apiClient = new ApiClient(API);
        
        // Set token if available
        const token = localStorage.getItem('token');
        if (token) {
          apiClient.setToken(token);
        }
        
        if (apiClient.isAuthenticated()) {
          const user = await apiClient.getUser();
          if (user) {
            localStorage.setItem('user', JSON.stringify(user));
            
            // Only update if different from cache
            const shouldShowAdmin = user.is_admin === true;
            const currentlyShowingAdmin = adminSidebar && adminSidebar.style.display !== 'none';
            
            if (shouldShowAdmin !== currentlyShowingAdmin) {
              if (user.is_admin && adminSidebar) {
                if (userSidebar) userSidebar.style.display = 'none';
                adminSidebar.style.display = 'block';
              } else if (userSidebar) {
                userSidebar.style.display = 'block';
                if (adminSidebar) adminSidebar.style.display = 'none';
              }
            }
          }
        }
      } catch (err) {
        console.error("Error verifying sidebar:", err);
      }
    })();
  })();
</script>
