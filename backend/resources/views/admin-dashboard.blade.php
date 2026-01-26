@extends('layouts.app')

@section('title', 'Admin Dashboard – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    .admin-tabs {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid var(--border-soft);
      padding-bottom: 0.5rem;
    }
    .admin-tab {
      padding: 0.75rem 1.5rem;
      background: transparent;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      border-radius: var(--radius);
      font-weight: 500;
      transition: all 0.2s;
    }
    .admin-tab:hover {
      background: rgba(79, 70, 229, 0.2);
      color: #fff;
    }
    .admin-tab.active {
      background: rgba(79, 70, 229, 0.4);
      color: #fff;
    }
    .admin-tab-content {
      display: none;
    }
    .admin-tab-content.active {
      display: block;
    }
    .admin-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      background: rgba(34, 211, 238, 0.2);
      color: #22d3ee;
      margin-left: 0.5rem;
    }
    .admin-badge.admin {
      background: rgba(239, 68, 68, 0.2);
      color: #fecaca;
    }
    .admin-badge.user {
      background: rgba(34, 211, 238, 0.2);
      color: #22d3ee;
    }
    .action-buttons {
      display: flex;
      gap: 0.5rem;
    }
    /* Users table: Actions column */
    .user-actions {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 0.45rem;
      width: 120px;
      margin: 0 auto;
    }
    .user-actions .btn-sm {
      width: 100%;
      justify-content: center;
      padding: 0.42rem 0.9rem;
      line-height: 1.1;
    }
    /* Users table: vertical scroll only (no horizontal) */
    .users-table-wrapper {
      overflow-y: auto;
      overflow-x: hidden;
      max-height: 65vh;
    }
    /* Users table: make ID column smaller */
    #tab-users .request-table th:nth-child(1),
    #tab-users .request-table td:nth-child(1) {
      width: 52px;
      min-width: 52px;
      max-width: 52px;
      padding-left: 0.4rem;
      padding-right: 0.4rem;
      font-variant-numeric: tabular-nums;
    }
    .btn-sm {
      padding: 0.4rem 0.8rem;
      font-size: 0.875rem;
    }
    .btn-success {
      background: rgba(34, 197, 94, 0.2);
      color: #86efac;
      border: 1px solid rgba(34, 197, 94, 0.3);
    }
    .btn-success:hover {
      background: rgba(34, 197, 94, 0.3);
    }
    .btn-danger {
      background: rgba(239, 68, 68, 0.2);
      color: #fecaca;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .btn-danger:hover {
      background: rgba(239, 68, 68, 0.3);
    }
    .btn-warning {
      background: rgba(251, 191, 36, 0.2);
      color: #fde047;
      border: 1px solid rgba(251, 191, 36, 0.3);
    }
    .btn-warning:hover {
      background: rgba(251, 191, 36, 0.3);
    }
    .form-inline {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }
    .form-inline input {
      flex: 1;
      padding: 0.6rem;
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--border-soft);
      border-radius: var(--radius);
      color: var(--text-main);
    }
    .category-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      background: rgba(15, 23, 42, 0.4);
      border-radius: var(--radius);
      margin-bottom: 0.5rem;
    }
    .sidebar-footer .logout-btn {
      transition: all 0.3s ease;
    }
    .sidebar-footer .logout-btn:hover {
      background: rgba(239, 68, 68, 0.35);
      color: #fff;
      box-shadow: 0 0 20px rgba(239, 68, 68, 0.6),
                  0 0 40px rgba(239, 68, 68, 0.4),
                  0 0 60px rgba(239, 68, 68, 0.2);
      transform: translateY(-1px);
    }
  </style>
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Admin Dashboard <span class="admin-badge">ADMIN</span></h2>
    </header>

    <!-- Tabs -->
    <div class="admin-tabs">
      <button class="admin-tab active" onclick="switchTab('users')">👥 Users</button>
      <button class="admin-tab" onclick="switchTab('transactions')">💰 Transactions</button>
      <button class="admin-tab" onclick="switchTab('payouts')">💸 Payouts</button>
      <button class="admin-tab" onclick="switchTab('categories')">📁 Categories</button>
    </div>

    <!-- Users Tab -->
    <div id="tab-users" class="admin-tab-content active">
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>All Users</h3>
          <p class="muted" id="usersCount">Loading...</p>
        </div>
        <div class="users-table-wrapper">
          <table class="request-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Skills</th>
                <th>Students</th>
                <th>Credits</th>
                <th>Rating</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="usersTableBody">
              <tr><td colspan="10">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Transactions Tab -->
    <div id="tab-transactions" class="admin-tab-content">
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>All Transactions</h3>
          <p class="muted" id="transactionsCount">Loading...</p>
        </div>
        <div style="overflow-x: auto;">
          <table class="request-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody id="transactionsTableBody">
              <tr><td colspan="6">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Payouts Tab -->
    <div id="tab-payouts" class="admin-tab-content">
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>All Payouts</h3>
          <p class="muted" id="payoutsCount">Loading...</p>
        </div>
        <div style="overflow-x: auto;">
          <table class="request-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Processed</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="payoutsTableBody">
              <tr><td colspan="7">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Categories Tab -->
    <div id="tab-categories" class="admin-tab-content">
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Categories</h3>
        </div>
        <div class="form-inline">
          <input type="text" id="newCategoryName" placeholder="Category name" onkeydown="if(event.key==='Enter') createCategory()">
          <button class="btn-primary btn-sm" onclick="createCategory()">Add Category</button>
        </div>
        <div id="categoriesList">
          <p class="muted">Loading...</p>
        </div>
      </div>
    </div>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    
    // Check if user is authenticated and is admin
    async function checkAdmin() {
      if (!apiClient.isAuthenticated()) {
        window.location.href = "{{ route('login') }}";
        return;
      }
      
      try {
        const user = await apiClient.getUser();
        if (!user.is_admin) {
          alert('Access denied. Admin privileges required.');
          window.location.href = "{{ route('dashboard') }}";
          return;
        }
        // Store current admin ID for user deletion checks
        window.currentAdminId = user.id;
      } catch (err) {
        console.error("Error checking admin status:", err);
        window.location.href = "{{ route('login') }}";
      }
    }

    checkAdmin();

    let currentTab = 'users';

    function switchTab(tabName) {
      currentTab = tabName;
      document.querySelectorAll('.admin-tab').forEach(tab => tab.classList.remove('active'));
      document.querySelectorAll('.admin-tab-content').forEach(content => content.classList.remove('active'));
      event.target.classList.add('active');
      document.getElementById(`tab-${tabName}`).classList.add('active');
      loadTabData(tabName);
    }

    async function loadTabData(tabName) {
      switch(tabName) {
        case 'users':
          await loadUsers();
          break;
        case 'transactions':
          await loadTransactions();
          break;
        case 'payouts':
          await loadPayouts();
          break;
        case 'categories':
          await loadCategories();
          break;
      }
    }

    async function loadUsers() {
      try {
        const users = await apiClient.getAllUsers();
        const tbody = document.getElementById('usersTableBody');
        document.getElementById('usersCount').textContent = `${users.length} users`;
        
        tbody.innerHTML = users.map(user => {
          const isCurrentAdmin = user.id === window.currentAdminId;
          const createdAt = user.created_at ? new Date(user.created_at) : null;
          const isNewUser = createdAt ? ((Date.now() - createdAt.getTime()) <= (3 * 24 * 60 * 60 * 1000)) : false;
          return `
            <tr>
              <td>${user.id}</td>
              <td>${user.name || 'N/A'}${user.is_verified ? ' <span title="Verified" style="color:#3b82f6;font-weight:700;">✓</span>' : ''}${isNewUser ? ' <span class="tag tag-green" title="New user" style="margin-left:8px; font-size:0.7rem; padding:0.2rem 0.55rem;">NEW</span>' : ''}</td>
              <td>${user.email}</td>
              <td><span class="admin-badge ${user.is_admin ? 'admin' : 'user'}">${user.is_admin ? 'Admin' : 'User'}</span></td>
              <td>${user.skills_count ?? 0}</td>
              <td>${user.students_taught_count ?? 0}</td>
              <td>${user.credits || 0}</td>
              <td>${user.rating_avg ? user.rating_avg.toFixed(1) : 'N/A'}</td>
              <td>${new Date(user.created_at).toLocaleDateString()}</td>
              <td>
                ${isCurrentAdmin 
                  ? '<span style="color: var(--text-muted);">—</span>' 
                  : `
                    <div class="user-actions">
                      <button class="btn-primary btn-sm" style="background: rgba(99,102,241,0.22); border: 1px solid rgba(99,102,241,0.9); color: #e0e7ff; box-shadow: 0 10px 25px rgba(99,102,241,0.22);" onclick="messageUser(${user.id}, '${(user.name || user.email).replace(/'/g, "\\'")}')">Message</button>
                      <button
                        class="btn-primary btn-sm"
                        style="background: rgba(59,130,246,0.22); border: 1px solid rgba(59,130,246,0.9); color: #bfdbfe; box-shadow: 0 10px 25px rgba(59,130,246,0.25);"
                        title="${user.is_verified ? 'Remove verification' : 'Verify user'}"
                        onclick="setUserVerified(${user.id}, ${user.is_verified ? 'false' : 'true'})"
                      >✓</button>
                      <button class="btn-primary btn-sm" style="background: linear-gradient(120deg, #dc2626, #ef4444); box-shadow: 0 10px 25px rgba(220, 38, 38, 0.55);" onclick="deleteUserById(${user.id}, '${(user.name || user.email).replace(/'/g, "\\'")}')">Delete</button>
                    </div>
                  `
                }
              </td>
            </tr>
          `;
        }).join('');
      } catch (err) {
        console.error("Error loading users:", err);
        document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="10">Error loading users</td></tr>';
      }
    }

    async function messageUser(userId, userName) {
      try {
        // Create/get conversation then redirect to Messages and open it
        const conversation = await apiClient.createConversation(userId);
        const conversationId = conversation?.id;
        if (!conversationId) {
          throw new Error('Failed to start conversation');
        }
        window.location.href = `{{ route('messages') }}?conversation_id=${conversationId}&user=${encodeURIComponent(userName || '')}`;
      } catch (err) {
        console.error("Error creating conversation:", err);
        alert('Error starting conversation: ' + (err.message || 'Unknown error'));
      }
    }

    async function deleteUserById(userId, userName) {
      if (!confirm(`Are you sure you want to delete user "${userName}"?\n\nThis action cannot be undone.`)) {
        return;
      }

      try {
        await apiClient.deleteUser(userId);
        alert('User deleted successfully!');
        loadUsers(); // Reload the list
      } catch (err) {
        alert('Error deleting user: ' + (err.message || err.error || 'Unknown error'));
        console.error("Error deleting user:", err);
      }
    }

    async function setUserVerified(userId, isVerified) {
      try {
        await apiClient.setUserVerified(userId, isVerified);
        loadUsers();
      } catch (err) {
        alert('Error updating verification: ' + (err.message || err.error || 'Unknown error'));
        console.error("Error updating verification:", err);
      }
    }

    async function loadTransactions() {
      try {
        const transactions = await apiClient.getAllTransactions();
        const tbody = document.getElementById('transactionsTableBody');
        document.getElementById('transactionsCount').textContent = `${transactions.length} transactions`;
        
        tbody.innerHTML = transactions.map(tx => `
          <tr>
            <td>${tx.id}</td>
            <td>${tx.user?.name || tx.user_id || 'N/A'}</td>
            <td><span class="tag">${tx.type || 'N/A'}</span></td>
            <td>${tx.amount || 0}</td>
            <td>${statusBadge(tx.status)}</td>
            <td>${new Date(tx.created_at).toLocaleString()}</td>
          </tr>
        `).join('');
      } catch (err) {
        console.error("Error loading transactions:", err);
        document.getElementById('transactionsTableBody').innerHTML = '<tr><td colspan="6">Error loading transactions</td></tr>';
      }
    }

    async function loadPayouts() {
      try {
        const payouts = await apiClient.getAllPayouts();
        const tbody = document.getElementById('payoutsTableBody');
        document.getElementById('payoutsCount').textContent = `${payouts.length} payouts`;
        
        tbody.innerHTML = payouts.map(payout => {
          const canApprove = payout.status === 'pending';
          const canMarkPaid = payout.status === 'approved';
          const canReject = payout.status === 'pending';
          
          return `
            <tr>
              <td>${payout.id}</td>
              <td>${payout.user?.name || payout.user_id || 'N/A'}</td>
              <td>${payout.amount || 0} credits</td>
              <td>${statusBadge(payout.status)}</td>
              <td>${new Date(payout.created_at).toLocaleDateString()}</td>
              <td>${payout.processed_at ? new Date(payout.processed_at).toLocaleDateString() : '—'}</td>
              <td>
                <div class="action-buttons">
                  ${canApprove ? `<button class="btn-success btn-sm" onclick="approvePayout(${payout.id})">Approve</button>` : ''}
                  ${canMarkPaid ? `<button class="btn-warning btn-sm" onclick="markPayoutPaid(${payout.id})">Mark Paid</button>` : ''}
                  ${canReject ? `<button class="btn-danger btn-sm" onclick="rejectPayout(${payout.id})">Reject</button>` : ''}
                </div>
              </td>
            </tr>
          `;
        }).join('');
      } catch (err) {
        console.error("Error loading payouts:", err);
        document.getElementById('payoutsTableBody').innerHTML = '<tr><td colspan="7">Error loading payouts</td></tr>';
      }
    }

    async function image.pngmake loadCategories() {
      try {
        const categories = await apiClient.listCategories();
        const container = document.getElementById('categoriesList');
        
        if (categories.length === 0) {
          container.innerHTML = '<p class="muted">No categories yet.</p>';
          return;
        }
        
        container.innerHTML = categories.map(cat => {
          const categoryName = cat.name || 'Unnamed';
          return `
          <div class="category-item">
            <span>${categoryName}</span>
            <button class="btn-primary btn-sm" style="background: linear-gradient(120deg, #dc2626, #ef4444); box-shadow: 0 10px 25px rgba(220, 38, 38, 0.55);" data-category-id="${cat.id}" data-category-name="${categoryName.replace(/"/g, '&quot;')}">Delete</button>
          </div>
        `;
        }).join('');
        
        // Add event listeners to delete buttons
        container.querySelectorAll('button[data-category-id]').forEach(btn => {
          btn.addEventListener('click', function() {
            const categoryId = parseInt(this.getAttribute('data-category-id'));
            const categoryName = this.getAttribute('data-category-name');
            deleteCategoryById(categoryId, categoryName);
          });
        });
      } catch (err) {
        console.error("Error loading categories:", err);
        document.getElementById('categoriesList').innerHTML = '<p class="muted">Error loading categories</p>';
      }
    }

    function statusBadge(status) {
      const statusMap = {
        'pending': '<span class="tag tag-yellow">Pending</span>',
        'approved': '<span class="tag tag-blue">Approved</span>',
        'rejected': '<span class="tag tag-red">Rejected</span>',
        'paid': '<span class="tag tag-green">Paid</span>',
        'completed': '<span class="tag tag-green">Completed</span>',
      };
      return statusMap[status?.toLowerCase()] || `<span class="tag">${status || 'Unknown'}</span>`;
    }

    async function approvePayout(id) {
      if (!confirm('Approve this payout?')) return;
      try {
        await apiClient.approvePayout(id);
        alert('Payout approved successfully!');
        loadPayouts();
      } catch (err) {
        alert('Error approving payout: ' + (err.message || 'Unknown error'));
      }
    }

    async function rejectPayout(id) {
      const note = prompt('Enter rejection reason:');
      if (!note) return;
      try {
        await apiClient.rejectPayout(id, note);
        alert('Payout rejected successfully!');
        loadPayouts();
      } catch (err) {
        alert('Error rejecting payout: ' + (err.message || 'Unknown error'));
      }
    }

    async function markPayoutPaid(id) {
      if (!confirm('Mark this payout as paid?')) return;
      try {
        await apiClient.markPayoutAsPaid(id);
        alert('Payout marked as paid successfully!');
        loadPayouts();
      } catch (err) {
        alert('Error marking payout as paid: ' + (err.message || 'Unknown error'));
      }
    }

    async function createCategory() {
      const nameInput = document.getElementById('newCategoryName');
      const name = nameInput.value.trim();
      if (!name) {
        alert('Please enter a category name');
        return;
      }
      
      const btn = document.querySelector('button[onclick*="createCategory"]');
      if (btn) {
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Adding...';
        
        try {
          await apiClient.createCategory(name);
          nameInput.value = '';
          alert('Category created successfully!');
          await loadCategories(); // Reload categories after creation
        } catch (err) {
          const errorMsg = err.message || err.error || 'Unknown error';
          alert('Error creating category: ' + errorMsg);
          console.error("Error creating category:", err);
        } finally {
          btn.disabled = false;
          btn.textContent = originalText;
        }
      } else {
        // Fallback
        try {
          await apiClient.createCategory(name);
          nameInput.value = '';
          alert('Category created successfully!');
          await loadCategories();
        } catch (err) {
          const errorMsg = err.message || err.error || 'Unknown error';
          alert('Error creating category: ' + errorMsg);
          console.error("Error creating category:", err);
        }
      }
    }

    async function deleteCategoryById(id, name) {
      if (!confirm(`Delete category "${name}"?`)) return;
      try {
        await apiClient.deleteCategory(id);
        alert('Category deleted successfully!');
        loadCategories();
      } catch (err) {
        alert('Error deleting category: ' + (err.message || err.error || 'Unknown error'));
        console.error("Error deleting category:", err);
      }
    }

    // Load initial data
    checkAdmin().then(() => {
      loadTabData(currentTab);
    });

  </script>
@endpush
