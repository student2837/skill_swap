@extends('layouts.app')

@section('title', 'Requests – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Requests</h2>
      <div class="topbar-actions">
        <button
          class="btn-secondary js-show-toast"
          data-toast-message="Create request flow coming soon."
        >
          New learning request
        </button>
      </div>
    </header>

    <section class="requests-section">
      <!-- Teaching requests -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Teaching requests</h3>
          <button class="btn-small" onclick="loadTeachingRequests()">Refresh</button>
        </div>

        <div class="table-wrapper">
          <table class="request-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Skill</th>
                <th>Date</th>
                <th>Credits</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody id="teachingRequestsBody">
              <tr><td colspan="6">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Learning requests -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Learning requests</h3>
          <button class="btn-small" onclick="loadLearningRequests()">Refresh</button>
        </div>

        <div class="table-wrapper">
          <table class="request-table">
            <thead>
              <tr>
                <th>Teacher</th>
                <th>Skill</th>
                <th>Time</th>
                <th>Credits</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody id="learningRequestsBody">
              <tr><td colspan="5">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
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
        'pending': '<span class="tag tag-yellow">Pending</span>',
        'accepted': '<span class="tag tag-blue">Accepted</span>',
        'confirmed': '<span class="tag tag-blue">Confirmed</span>',
        'completed': '<span class="tag tag-green">Completed</span>',
        'rejected': '<span class="tag tag-red">Rejected</span>',
        'cancelled': '<span class="tag tag-red">Cancelled</span>'
      };
      return statusMap[status?.toLowerCase()] || `<span class="tag">${status || 'Unknown'}</span>`;
    }

    function formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      const month = date.toLocaleDateString('en-US', { month: 'short' });
      const day = date.getDate();
      const time = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
      return `${month} ${day}, ${time}`;
    }

    async function loadTeachingRequests() {
      try {
        const requests = await apiClient.getTeachingRequests();
        const tbody = document.getElementById("teachingRequestsBody");
        tbody.innerHTML = "";

        if (!requests || !requests.length) {
          tbody.innerHTML = "<tr><td colspan='6'>No teaching requests</td></tr>";
          return;
        }

        requests.forEach(request => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${request.student?.name || 'Unknown'}</td>
            <td>${request.skill?.title || 'Untitled'}</td>
            <td>${formatDate(request.created_at)}</td>
            <td>${request.skill?.price || 0}</td>
            <td>${statusBadge(request.status)}</td>
            <td class="action-cell">
              ${request.status === 'pending' ? `
                <button class="btn-small btn-success" onclick="acceptRequest(${request.id})">Accept</button>
                <button class="btn-small btn-danger" onclick="rejectRequest(${request.id})">Decline</button>
              ` : request.status === 'accepted' ? `
                <button class="btn-small" onclick="completeRequest(${request.id})">Complete</button>
              ` : '<span style="color: var(--text-muted);">—</span>'}
            </td>
          `;
          tbody.appendChild(row);
        });
      } catch (err) {
        console.error("Error loading teaching requests:", err);
        document.getElementById("teachingRequestsBody").innerHTML = "<tr><td colspan='6'>Error loading requests</td></tr>";
      }
    }

    async function loadLearningRequests() {
      try {
        const requests = await apiClient.getLearningRequests();
        const tbody = document.getElementById("learningRequestsBody");
        tbody.innerHTML = "";

        if (!requests || !requests.length) {
          tbody.innerHTML = "<tr><td colspan='5'>No learning requests</td></tr>";
          return;
        }

        requests.forEach(request => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${request.skill?.user?.name || 'Unknown'}</td>
            <td>${request.skill?.title || 'Untitled'}</td>
            <td>${formatDate(request.created_at)}</td>
            <td>${request.skill?.price || 0}</td>
            <td>${statusBadge(request.status)}</td>
          `;
          tbody.appendChild(row);
        });
      } catch (err) {
        console.error("Error loading learning requests:", err);
        document.getElementById("learningRequestsBody").innerHTML = "<tr><td colspan='5'>Error loading requests</td></tr>";
      }
    }

    // Request action handlers
    async function acceptRequest(requestId) {
      try {
        await apiClient.acceptRequest(requestId);
        alert("Request accepted");
        loadTeachingRequests();
      } catch (err) {
        alert(err.message || "Failed to accept request");
      }
    }

    async function rejectRequest(requestId) {
      try {
        await apiClient.rejectRequest(requestId);
        alert("Request rejected");
        loadTeachingRequests();
      } catch (err) {
        alert(err.message || "Failed to reject request");
      }
    }

    async function completeRequest(requestId) {
      try {
        await apiClient.completeRequest(requestId);
        alert("Request marked as completed");
        loadTeachingRequests();
      } catch (err) {
        alert(err.message || "Failed to complete request");
      }
    }

    loadTeachingRequests();
    loadLearningRequests();
  </script>
@endpush
