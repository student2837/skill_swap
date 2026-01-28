<!-- Admin Sidebar - Only for admin users -->
<aside class="sidebar glass" id="adminSidebarComponent" style="display: none;">
  <div class="sidebar-logo-wrap">
    <img src="{{ asset('assets/logo.png') }}" class="sidebar-logo-img" />
  </div>

  <nav class="sidebar-nav">
    <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">⚙️ Admin Panel</a>
    <a href="{{ route('admin.wallet') }}" class="nav-item {{ request()->routeIs('admin.wallet') ? 'active' : '' }}">🏦 Platform Wallet</a>
    <a href="{{ route('admin.payouts') }}" class="nav-item {{ request()->routeIs('admin.payouts') || request()->routeIs('admin.payouts.show') ? 'active' : '' }}">💸 Payouts</a>
    <a href="{{ route('admin.cashout') }}" class="nav-item {{ request()->routeIs('admin.cashout') ? 'active' : '' }}">💳 Admin Cashout</a>
    <a href="{{ route('browse') }}" class="nav-item {{ request()->routeIs('browse') ? 'active' : '' }}">🔍 Browse Skills</a>
    <a href="{{ route('messages') }}" class="nav-item {{ request()->routeIs('messages') ? 'active' : '' }}">💬 Messages</a>
    <a href="{{ route('profile') }}" class="nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">👤 Profile</a>
  </nav>

  <div class="sidebar-footer">
    <a href="{{ route('login') }}" class="logout-btn" onclick="event.preventDefault(); adminSidebarLogout();">🚪 Logout</a>
  </div>
</aside>

<script>
  async function adminSidebarLogout() {
    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    try {
      await apiClient.logout();
    } catch (err) {
      console.error('Logout error:', err);
    }
    window.location.href = "{{ route('login') }}";
  }
</script>
