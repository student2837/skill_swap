<!-- Sidebar -->
<aside class="sidebar glass">
  <div class="sidebar-logo-wrap">
    <img src="{{ asset('assets/logo.png') }}" class="sidebar-logo-img" />
  </div>

  <nav class="sidebar-nav">
    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">🏠 Dashboard</a>
    <a href="{{ route('my-skills') }}" class="nav-item {{ request()->routeIs('my-skills') ? 'active' : '' }}">📚 My Skills</a>
    <a href="{{ route('browse') }}" class="nav-item {{ request()->routeIs('browse') ? 'active' : '' }}">🔍 Browse Skills</a>
    <a href="{{ route('requests') }}" class="nav-item {{ request()->routeIs('requests') ? 'active' : '' }}">📥 Requests</a>
    <a href="{{ route('messages') }}" class="nav-item {{ request()->routeIs('messages') ? 'active' : '' }}">💬 Messages</a>
    <a href="{{ route('favorites') }}" class="nav-item {{ request()->routeIs('favorites') ? 'active' : '' }}">❤️ Favorites</a>
    <a href="{{ route('review') }}" class="nav-item {{ request()->routeIs('review') ? 'active' : '' }}">⭐ Reviews</a>
    <a href="{{ route('credits') }}" class="nav-item {{ request()->routeIs('credits') ? 'active' : '' }}">💳 Credits</a>
    <a href="{{ route('profile') }}" class="nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">👤 Profile</a>
  </nav>

  <div class="sidebar-footer">
    <a href="{{ route('login') }}" class="logout-btn" onclick="event.preventDefault(); handleLogout();">🚪 Logout</a>
  </div>
</aside>

<script>
  async function handleLogout() {
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
