@extends('layouts.app')

@section('title', 'Register – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}" />
@endpush

@section('body-class', 'auth-body')

@section('content')
  <div class="auth-bg"></div>

  <div class="auth-card glass">
    <a href="{{ route('index') }}" class="auth-back" aria-label="Back to home">←</a>
    <img
      src="{{ asset('assets/logo.png') }}"
      alt="SkillSwap Logo"
      class="auth-logo"
    />

    <h2>Create your account</h2>
    <p class="auth-sub">
      Join SkillSwap and start teaching or learning today.
    </p>

    <!-- Registration form -->
    <form class="auth-form" id="registerForm">
      <label class="input-group">
        <span>Full name</span>
        <input
          type="text"
          id="regName"
          autocomplete="off"
          placeholder="John Doe"
          required
        />
      </label>

      <label class="input-group">
        <span>Email</span>
        <input
          type="email"
          id="regEmail"
          autocomplete="off"
          placeholder="you@example.com"
          required
        />
      </label>

      <label class="input-group">
        <span>Password</span>
        <input
          type="password"
          id="regPassword"
          autocomplete="off"
          placeholder="••••••••"
          required
          minlength="8"
        />
      </label>

      <label class="input-group">
        <span>Confirm password</span>
        <input
          type="password"
          id="regConfirmPassword"
          autocomplete="off"
          placeholder="••••••••"
          required
        />
      </label>

      <button type="button" class="auth-btn" id="createAccountBtn">
        Create account
      </button>

      <p class="auth-switch">
        Already have an account?
        <a href="{{ route('login') }}">Log in</a>
      </p>
    </form>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const createAccountBtn = document.getElementById("createAccountBtn");
      const apiClient = new ApiClient("{{ url('/api') }}");

      createAccountBtn.addEventListener("click", async () => {
        const name = document.getElementById("regName").value.trim();
        const email = document.getElementById("regEmail").value.trim();
        const password = document.getElementById("regPassword").value.trim();
        const confirmPassword = document.getElementById("regConfirmPassword").value.trim();

        if (password !== confirmPassword) {
          alert("Passwords do not match");
          return;
        }

        try {
          await apiClient.register(name, email, password);
          alert("Registration successful! Please log in.");
          window.location.href = "{{ route('login') }}";
        } catch (err) {
          // Handle duplicate email error specifically
          if (err.status === 422 && err.data?.errors?.email) {
            alert("This email is already registered. Please use a different email or log in.");
          } else if (err.message) {
            alert(err.message);
          } else {
            alert("Registration failed. Please try again.");
          }
        }
      });
    });
  </script>
@endpush
