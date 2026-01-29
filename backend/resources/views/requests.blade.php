@extends('layouts.app')

@section('title', 'Requests – SkillSwap')

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
      <h2>Requests</h2>
      <div class="topbar-actions">
      </div>
    </header>

    <section class="requests-section">
      <!-- Teaching requests -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Teaching requests</h3>
          <button id="refreshTeachingBtn" class="btn-small" type="button">Refresh</button>
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
          <button id="refreshLearningBtn" class="btn-small" type="button">Refresh</button>
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
                <th>Actions</th>
              </tr>
            </thead>

            <tbody id="learningRequestsBody">
              <tr><td colspan="6">Loading...</td></tr>
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

    // If quiz is in progress, force return to the exam page.
    try {
      if (sessionStorage.getItem('quiz_in_progress') === '1') {
        alert("You cannot go back until you finish the quiz.");
        window.location.href = "{{ route('quiz.exam') }}";
      }
    } catch (e) {}

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

    async function loadTeachingRequests(hideCancelled = false) {
      try {
        const requests = await apiClient.getTeachingRequests();
        const tbody = document.getElementById("teachingRequestsBody");
        tbody.innerHTML = "";

        const displayRequests = hideCancelled
          ? (requests || []).filter(r => (r.status || '').toLowerCase() !== 'cancelled')
          : (requests || []);

        if (!displayRequests.length) {
          tbody.innerHTML = "<tr><td colspan='6'>No teaching requests</td></tr>";
          return;
        }

        displayRequests.forEach(request => {
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

    async function loadLearningRequests(hideCancelled = false) {
      try {
        const requests = await apiClient.getLearningRequests();
        const tbody = document.getElementById("learningRequestsBody");
        tbody.innerHTML = "";

        const displayRequests = hideCancelled
          ? (requests || []).filter(r => (r.status || '').toLowerCase() !== 'cancelled')
          : (requests || []);

        if (!displayRequests.length) {
          tbody.innerHTML = "<tr><td colspan='6'>No learning requests</td></tr>";
          return;
        }

        displayRequests.forEach(request => {
          const row = document.createElement('tr');
          const isCompleted = (request.status || '').toLowerCase() === 'completed';
          const quizCompleted = !!request.quiz_completed_at;
          const quizStarted = !!request.quiz_started_at && !quizCompleted;
          row.innerHTML = `
            <td>${request.skill?.user?.name || 'Unknown'}</td>
            <td>${request.skill?.title || 'Untitled'}</td>
            <td>${formatDate(request.created_at)}</td>
            <td>${request.skill?.price || 0}</td>
            <td>${statusBadge(request.status)}</td>
            <td class="action-cell">
              ${request.status === 'pending' ? `
                <button class="btn-small btn-danger" onclick="cancelLearningRequest(${request.id}, this)">Cancel</button>
              ` : isCompleted ? `
                ${quizCompleted ? `
                  <span style="color: var(--text-muted);">Quiz completed</span>
                ` : quizStarted ? `
                  <span style="color: var(--text-muted);">Quiz in progress</span>
                ` : `
                  <button class="btn-small btn-primary" onclick="takeQuiz(${request.id})">Take Quiz</button>
                `}
              ` : '<span style="color: var(--text-muted);">—</span>'}
            </td>
          `;
          tbody.appendChild(row);
        });
      } catch (err) {
        console.error("Error loading learning requests:", err);
        document.getElementById("learningRequestsBody").innerHTML = "<tr><td colspan='6'>Error loading requests</td></tr>";
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
        const response = await apiClient.completeRequest(requestId);
        // Request is completed - quiz will be generated when student clicks "Take Quiz"
        alert("Request marked as completed! The student can now take the quiz.");
        loadTeachingRequests();
      } catch (err) {
        alert(err.message || "Failed to complete request");
      }
    }

    async function takeQuiz(requestId) {
      window.location.href = "{{ url('/quiz/start') }}/" + requestId;
    }

    async function cancelLearningRequest(requestId, btnEl = null) {
      const confirmCancel = confirm("Cancel this request?");
      if (!confirmCancel) return;

      try {
        await apiClient.cancelRequest(requestId);
        alert("Request cancelled");

        // Keep the row visible until user clicks Refresh
        if (btnEl) {
          const row = btnEl.closest('tr');
          if (row) {
            const statusCell = row.querySelector('td:nth-child(5)');
            if (statusCell) statusCell.innerHTML = statusBadge('cancelled');

            const actionCell = row.querySelector('td:nth-child(6)');
            if (actionCell) {
              actionCell.innerHTML = '<span style="color: var(--text-muted);">—</span>';
            }
          }
          return;
        }
      } catch (err) {
        alert(err.message || "Failed to cancel request");
      }
    }

    // Ensure refresh buttons work even if inline handlers are blocked
    document.getElementById('refreshTeachingBtn')?.addEventListener('click', (e) => {
      e.preventDefault();
      (async () => {
        try {
          await apiClient.purgeCancelledTeachingRequests();
        } catch (err) {
          console.error("Error deleting cancelled teaching requests:", err);
        }
        loadTeachingRequests(true);
      })();
    });
    document.getElementById('refreshLearningBtn')?.addEventListener('click', (e) => {
      e.preventDefault();
      (async () => {
        try {
          await apiClient.purgeCancelledLearningRequests();
        } catch (err) {
          console.error("Error deleting cancelled learning requests:", err);
        }
        loadLearningRequests(true);
      })();
    });

    // Initial load: keep cancelled visible. Refresh buttons hide them.
    loadTeachingRequests(false);
    loadLearningRequests(false);
  </script>
@endpush
