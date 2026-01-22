@extends('layouts.app')

@section('title', 'Login – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}" />
@endpush

@section('body-class', 'auth-body')

@section('content')
  <div class="auth-bg"></div>

  <div class="auth-card glass">
    <img
      src="{{ asset('assets/logo.png') }}"
      alt="SkillSwap Logo"
      class="auth-logo"
    />

    <h2>Welcome back</h2>
    <p class="auth-sub">
      Log in to continue learning & teaching.
    </p>

    <form class="auth-form" id="loginForm">
      <label class="input-group">
        <span>Email</span>
        <input
          type="email"
          id="email"
          placeholder="you@example.com"
          required
        />
      </label>

      <label class="input-group">
        <span>Password</span>
        <input
          type="password"
          id="password"
          placeholder="••••••••"
          required
        />
      </label>

      <button type="submit" class="auth-btn">
        Log in
      </button>
    </form>

    <p class="auth-switch">
      Don't have an account?
      <a href="{{ route('register') }}">Register now</a>
    </p>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/dashboard.js') }}"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const form = document.getElementById("loginForm");
      const apiClient = new ApiClient("{{ url('/api') }}");

      form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (!email || !password) {
          alert("Please enter both email and password");
          return;
        }
        
        // Disable button during login
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logging in...';

        try {
          const result = await apiClient.login(email, password);

          // Token is already saved by apiClient.login()
          console.log("Login successful", result);

          // Verify token was saved
          const savedToken = localStorage.getItem('token');
          if (!savedToken) {
            console.error("Token was not saved to localStorage");
            throw new Error("Failed to save authentication token");
          }

          // Get user data to check admin status
          let user = result.user;
          if (!user) {
            // If user not in response, fetch it
            try {
              user = await apiClient.getUser();
            } catch (err) {
              console.error("Error fetching user:", err);
              // Still proceed with redirect even if user fetch fails
            }
          }

          // Ensure token is set in the apiClient instance
          apiClient.setToken(savedToken);

          // Redirect admins to admin dashboard, regular users to dashboard
          // Use window.location.replace to prevent back button issues
          if (user && user.is_admin) {
            window.location.replace("{{ route('admin.dashboard') }}");
          } else {
            window.location.replace("{{ route('dashboard') }}");
          }

        } catch (err) {
          console.error("Login error:", err);
          const errorMessage = err.message || err.error || "Login failed. Please check your credentials.";
          alert(errorMessage);
          submitBtn.disabled = false;
          submitBtn.textContent = 'Log in';
        }
      });
    });
  </script>
@endpush
