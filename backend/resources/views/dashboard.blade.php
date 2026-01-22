@extends('layouts.app')

@section('title', 'Dashboard – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2 id="welcomeText">Welcome back 👋</h2>

      <div class="topbar-actions">
        <a href="{{ route('add-skill') }}" class="btn-primary">Teach a Skill</a>
        <a href="{{ route('credits') }}" class="btn-secondary">Buy Credits</a>
      </div>
    </header>

    <!-- Stats -->
    <section class="stats-grid">
      <div class="stat-card glass">
        <h3>Credits</h3>
        <p class="stat-value" id="creditsValue">—</p>
        <p class="stat-label">Available balance</p>
      </div>

      <div class="stat-card glass">
        <h3>Teaching requests</h3>
        <p class="stat-value" id="teachReqCount">—</p>
        <p class="stat-label">Pending approvals</p>
      </div>

      <div class="stat-card glass">
        <h3>Learning requests</h3>
        <p class="stat-value" id="learnReqCount">—</p>
        <p class="stat-label">Upcoming sessions</p>
      </div>

      <div class="stat-card glass">
        <h3>Rating</h3>
        <p class="stat-value" id="teacherRating">—</p>
        <p class="stat-label">From reviews</p>
      </div>
    </section>

    <!-- Teaching Section -->
    <section class="teaching-section">
      <!-- Teaching Skills -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Your teaching skills</h3>
          <a href="{{ route('add-skill') }}" class="btn-small">Add Skill</a>
        </div>

        <div class="skill-list" id="teachingSkills">
          <p class="muted">No skills yet.</p>
        </div>
      </div>

      <!-- Teaching Requests (for teachers) -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Teaching requests</h3>
          <a href="{{ route('requests') }}" class="btn-small">View all</a>
        </div>

        <table class="request-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Skill</th>
              <th>Status</th>
              <th>Credits</th>
            </tr>
          </thead>
          <tbody id="teachingRequests">
            <tr><td colspan="4">No requests</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Learning Requests (for students) -->
    <section class="learning-section">
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Learning requests</h3>
          <a href="{{ route('requests') }}" class="btn-small">View all</a>
        </div>

        <table class="request-table">
          <thead>
            <tr>
              <th>Skill</th>
              <th>Teacher</th>
              <th>Status</th>
              <th>Credits</th>
            </tr>
          </thead>
          <tbody id="learningRequests">
            <tr><td colspan="4">No requests</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    
    // Check authentication asynchronously to avoid race conditions
    async function checkAuthentication() {
      // First check if token exists in localStorage
      const token = localStorage.getItem('token');
      if (!token) {
        window.location.href = "{{ route('login') }}";
        return false;
      }

      // Verify token is valid by trying to get user
      try {
        const user = await apiClient.getUser();
        
        // Redirect admins to admin dashboard
        if (user.is_admin) {
          window.location.href = "{{ route('admin.dashboard') }}";
          return false;
        }
        
        return true; // User is authenticated and not admin
      } catch (err) {
        console.error("Authentication error:", err);
        // Token is invalid, clear it and redirect to login
        localStorage.removeItem('token');
        window.location.href = "{{ route('login') }}";
        return false;
      }
    }

    // Run authentication check
    checkAuthentication().then(isAuthenticated => {
      if (isAuthenticated) {
        // User is authenticated, continue loading dashboard data
        loadAllData();
      }
    });

    // Helper function for status badges
    function statusBadge(status) {
      const statusMap = {
        'pending': '<span class="tag tag-yellow">Pending</span>',
        'accepted': '<span class="tag tag-blue">Accepted</span>',
        'confirmed': '<span class="tag tag-blue">Confirmed</span>',
        'completed': '<span class="tag tag-green">Completed</span>',
        'rejected': '<span class="tag tag-red">Rejected</span>',
        'cancelled': '<span class="tag tag-red">Cancelled</span>',
        'active': '<span class="tag tag-blue">Active</span>',
        'inactive': '<span class="tag tag-gray">Inactive</span>'
      };
      return statusMap[status?.toLowerCase()] || `<span class="tag">${status || 'Unknown'}</span>`;
    }

    async function loadUser() {
      try {
        const u = await apiClient.getUser();
        if (u.name) {
          document.getElementById("welcomeText").textContent =
            `Welcome back, ${u.name} 👋`;
        } else if (u.first_name) {
          document.getElementById("welcomeText").textContent =
            `Welcome back, ${u.first_name} 👋`;
        }
        
        // Load teacher rating
        if (u.rating_avg !== null && u.rating_avg !== undefined) {
          document.getElementById("teacherRating").textContent = u.rating_avg.toFixed(1);
        } else {
          document.getElementById("teacherRating").textContent = "N/A";
        }
      } catch (err) {
        console.error("Error loading user:", err);
      }
    }

    async function loadCredits() {
      try {
        // Get credits directly from user object
        const user = await apiClient.getUser();
        const balance = user.credits || 0;
        document.getElementById("creditsValue").textContent = balance;
      } catch (err) {
        console.error("Error loading credits:", err);
        // Fallback: try to get from transactions
        try {
          const tx = await apiClient.getUserTransactions();
          if (tx && tx.balance !== undefined) {
            document.getElementById("creditsValue").textContent = tx.balance;
          }
        } catch (err2) {
          console.error("Error loading credits from transactions:", err2);
        }
      }
    }

    async function loadTeachingSkills() {
      try {
        const skills = await apiClient.getTeachingSkills();
        const box = document.getElementById("teachingSkills");
        box.innerHTML = "";

        if (!skills || !skills.length) {
          box.innerHTML = "<p class='muted'>No skills yet.</p>";
          return;
        }

        skills.forEach(s => {
          // Get skill-specific rating (average of all reviews for this specific skill)
          const skillRating = s.rating_avg || null;
          const ratingDisplay = skillRating ? `⭐ ${skillRating.toFixed(1)}` : '';
          
          box.innerHTML += `
            <div class="skill-item">
              <div>
                <p class="skill-title">${s.title || s.skill_name || 'Untitled'}</p>
                <p class="skill-sub">${s.credits || s.price || 0} credits ${ratingDisplay ? '• ' + ratingDisplay : ''}</p>
              </div>
              ${statusBadge(s.status)}
            </div>
          `;
        });
      } catch (err) {
        console.error("Error loading teaching skills:", err);
      }
    }

    async function loadRequests() {
      try {
        const requests = await apiClient.getLearningRequests();

        const tbody = document.getElementById("learningRequests");
        tbody.innerHTML = "";

        if (!requests || !Array.isArray(requests)) {
          tbody.innerHTML = "<tr><td colspan='4'>No requests</td></tr>";
          document.getElementById("learnReqCount").textContent = 0;
          return;
        }

        document.getElementById("learnReqCount").textContent = requests.length;

        requests.forEach(r => {
          tbody.innerHTML += `
            <tr>
              <td>${r.skill?.title || 'N/A'}</td>
              <td>${r.skill?.user?.name || 'N/A'}</td>
              <td>${statusBadge(r.status)}</td>
              <td>${r.skill?.price || 0}</td>
            </tr>
          `;
        });
      } catch (err) {
        console.error("Error loading requests:", err);
        const tbody = document.getElementById("learningRequests");
        tbody.innerHTML = "<tr><td colspan='4'>Error loading requests</td></tr>";
      }
    }

    async function loadTeachingRequestsCount() {
      try {
        const requests = await apiClient.getTeachingRequests();
        const pendingCount = requests ? requests.filter(r => r.status === 'pending').length : 0;
        document.getElementById("teachReqCount").textContent = pendingCount;
      } catch (err) {
        console.error("Error loading teaching requests count:", err);
        document.getElementById("teachReqCount").textContent = 0;
      }
    }

    async function loadTeachingRequests() {
      try {
        const requests = await apiClient.getTeachingRequests();
        const tbody = document.getElementById("teachingRequests");
        tbody.innerHTML = "";

        if (!requests || !Array.isArray(requests) || requests.length === 0) {
          tbody.innerHTML = "<tr><td colspan='4'>No teaching requests</td></tr>";
          return;
        }

        // Show only pending requests on dashboard (or limit to 5 most recent)
        const recentRequests = requests.slice(0, 5);
        
        recentRequests.forEach(r => {
          tbody.innerHTML += `
            <tr>
              <td>${r.student?.name || 'Unknown'}</td>
              <td>${r.skill?.title || 'Untitled'}</td>
              <td>${statusBadge(r.status)}</td>
              <td>${r.skill?.price || 0}</td>
            </tr>
          `;
        });
      } catch (err) {
        console.error("Error loading teaching requests:", err);
        const tbody = document.getElementById("teachingRequests");
        tbody.innerHTML = "<tr><td colspan='4'>Error loading requests</td></tr>";
      }
    }

    // Load all data
    async function loadAllData() {
      await loadUser();
      await loadCredits();
      await loadTeachingSkills();
      await loadRequests();
      await loadTeachingRequestsCount();
      await loadTeachingRequests();
    }

    // Listen for storage events (when credits are updated from credits page in another tab)
    window.addEventListener('storage', (e) => {
      if (e.key === 'creditsUpdated') {
        loadCredits();
        loadUser(); // Refresh user data
      }
    });

    // Listen for focus events (when user returns to dashboard from credits page)
    window.addEventListener('focus', () => {
      loadCredits();
      loadUser();
    });

    // Listen for page visibility changes (when user switches back to this tab)
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        loadCredits();
        loadUser();
      }
    });

    // Check for credits update on page load (in case user just came from credits page)
    const creditsUpdated = localStorage.getItem('creditsUpdated');
    if (creditsUpdated) {
      loadCredits();
      loadUser();
      localStorage.removeItem('creditsUpdated');
    }

    // Initial load will be called after authentication check
  </script>
@endpush
