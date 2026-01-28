@extends('layouts.app')

@section('title', 'My Skills – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Your Skills</h2>
    </header>

    <section class="two-col">
      <!-- Teaching skills -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Teaching skills</h3>
          <a href="{{ route('add-skill') }}" class="btn-small">
            Add Skill
          </a>
        </div>

        <!-- Teaching skills will be loaded here -->
        <div class="skill-list" id="teachingSkillsList">
          <p class="skill-sub">
            You haven't added any skills yet.
          </p>
        </div>
      </div>

      <!-- Learning skills -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Skills you're learning</h3>
          <a href="{{ route('browse') }}" class="btn-small">
            Browse Skills
          </a>
        </div>

        <div class="skill-list" id="learningSkillsList">
          <p class="skill-sub">
            You haven't enrolled in any skills yet.
            <br />
            Browse skills to start learning.
          </p>
        </div>
      </div>
    </section>

    <!-- Performance -->
    <section class="dash-card-full glass">
      <div class="dash-card-header">
        <h3>Skill performance</h3>
        <a class="btn-small" href="{{ route('review') }}">
          View all reviews
        </a>
      </div>

      <table class="request-table" id="skillPerformanceTable">
        <thead>
          <tr>
            <th>Skill</th>
            <th>Status</th>
            <th>Sessions</th>
            <th>Avg rating</th>
            <th>Credits earned</th>
          </tr>
        </thead>
        <tbody id="skillPerformanceBody">
          <tr><td colspan="5">Loading...</td></tr>
        </tbody>
      </table>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    const token = localStorage.getItem("token");
    if (!token) window.location.href = "{{ route('login') }}";

    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    if (token) {
      apiClient.setToken(token);
    }

    // Block admin access - redirect to admin dashboard
    (async function() {
      try {
        const user = await apiClient.getUser();
        if (user.is_admin) {
          window.location.href = "{{ route('admin.dashboard') }}";
        }
      } catch (err) {
        console.error("Error checking admin status:", err);
      }
    })();

    function statusBadge(status) {
      const statusMap = {
        'draft': '<span class="tag tag-red">Draft</span>',
        'active': '<span class="tag tag-green">Active</span>',
        'paused': '<span class="tag tag-red">Paused</span>',
        'pending': '<span class="tag tag-yellow">Pending</span>',
        'accepted': '<span class="tag tag-blue">Accepted</span>',
        'confirmed': '<span class="tag tag-blue">Confirmed</span>',
        'completed': '<span class="tag tag-green">Completed</span>',
        'rejected': '<span class="tag tag-red">Rejected</span>',
        'cancelled': '<span class="tag tag-red">Cancelled</span>',
        'inactive': '<span class="tag tag-gray">Inactive</span>'
      };
      return statusMap[status?.toLowerCase()] || `<span class="tag">${status || 'Unknown'}</span>`;
    }

    function getCategoryName(category) {
      const categoryMap = {
        'music': 'Music',
        'programming': 'Programming',
        'design': 'Design',
        'languages': 'Languages',
        'other': 'Other'
      };
      return categoryMap[category] || category || 'Other';
    }

    async function loadTeachingSkills() {
      try {
        const skills = await apiClient.getTeachingSkills();
        const box = document.getElementById('teachingSkillsList');
        
        if (!box) {
          console.error("Teaching skills container not found");
          return;
        }
        
        if (!skills || !skills.length) {
          box.innerHTML = '<p class="skill-sub">You haven\'t added any skills yet.</p>';
          return;
        }

        box.innerHTML = skills.map(s => `
          <div class="skill-item" data-skill-id="${s.id}">
            <div>
              <p class="skill-title">${s.title || s.skill_name || 'Untitled'}</p>
              <p class="skill-sub">${s.price || s.credits || 0} credits • ${getCategoryName(s.category)}</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
              ${statusBadge(s.status || 'draft')}
              <button class="btn-small" onclick="changeSkillStatus(${s.id}, '${s.status || 'draft'}')">Change Status</button>
              <a href="{{ route('edit-skill') }}?id=${s.id}" class="btn-small">Edit</a>
              <button class="btn-small" onclick="deleteSkill(${s.id})" style="background: #dc2626; color: white;">Delete</button>
            </div>
          </div>
        `).join('');
      } catch (err) {
        console.error("Error loading teaching skills:", err);
        const box = document.getElementById('teachingSkillsList');
        if (box) {
          box.innerHTML = '<p class="skill-sub">Error loading skills. Please try again.</p>';
        }
      }
    }

    async function loadLearningSkills() {
      try {
        const skills = await apiClient.getLearningSkills();
        const learningBox = document.getElementById('learningSkillsList');
        
        if (!learningBox) {
          console.error("Learning skills container not found");
          return;
        }
        
        if (!skills || !skills.length) {
          learningBox.innerHTML = '<p class="skill-sub">You haven\'t enrolled in any skills yet.<br />Browse skills to start learning.</p>';
          return;
        }

        learningBox.innerHTML = skills.map(s => `
          <div class="skill-item">
            <div>
              <p class="skill-title">${s.title || s.skill_name || 'Untitled'}</p>
              <p class="skill-sub">${s.price || s.credits || 0} credits</p>
            </div>
            ${statusBadge(s.status || 'accepted')}
          </div>
        `).join('');
      } catch (err) {
        console.error("Error loading learning skills:", err);
        const learningBox = document.getElementById('learningSkillsList');
        if (learningBox) {
          learningBox.innerHTML = '<p class="skill-sub">Error loading skills. Please try again.</p>';
        }
      }
    }

    async function changeSkillStatus(skillId, currentStatus) {
      // Determine next status: draft -> active -> paused -> active (cycle)
      let newStatus;
      if (currentStatus === 'draft') {
        newStatus = 'active';
      } else if (currentStatus === 'active') {
        newStatus = 'paused';
      } else {
        newStatus = 'active';
      }

      try {
        await apiClient.changeSkillStatus(skillId, newStatus);
        loadTeachingSkills(); // Reload to show updated status
      } catch (err) {
        alert(err.message || 'Failed to update status');
        console.error("Error changing skill status:", err);
      }
    }

    async function deleteSkill(skillId) {
      if (!confirm('Are you sure you want to delete this skill? This action cannot be undone.')) {
        return;
      }

      try {
        await apiClient.deleteSkill(skillId);
        alert('Skill deleted successfully!');
        loadTeachingSkills(); // Reload to remove deleted skill
      } catch (err) {
        alert(err.message || 'Failed to delete skill');
        console.error("Error deleting skill:", err);
      }
    }

    async function loadSkillPerformance() {
      const tbody = document.getElementById('skillPerformanceBody');
      if (!tbody) return;

      try {
        const rows = await apiClient.getSkillPerformance();
        if (!rows || !rows.length) {
          tbody.innerHTML = "<tr><td colspan='5'>No performance data yet</td></tr>";
          return;
        }

        tbody.innerHTML = rows.map(r => `
          <tr>
            <td>${r.skill_title || 'Untitled'}</td>
            <td>${statusBadge(r.status || 'draft')}</td>
            <td style="text-align:center;">${r.sessions_count ?? 0}</td>
            <td style="text-align:center;">${(r.avg_rating ?? 0)} (${r.ratings_count ?? 0})</td>
            <td style="text-align:center;">${r.credits_earned ?? 0}</td>
          </tr>
        `).join('');
      } catch (err) {
        console.error("Error loading skill performance:", err);
        tbody.innerHTML = "<tr><td colspan='5'>Error loading performance</td></tr>";
      }
    }

    loadTeachingSkills();
    loadLearningSkills();
    loadSkillPerformance();
  </script>
@endpush
