@extends('layouts.app')

@section('title', 'Profile – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Profile & Settings</h2>
    </header>

    <section class="two-col">
      <!-- Profile -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Your profile</h3>
        </div>

        <form class="profile-form" id="profileForm">
          <label>
            <span>Full name</span>
            <input type="text" id="profileName" required />
          </label>

          <label>
            <span>Email</span>
            <input type="email" id="profileEmail" disabled />
          </label>

          <label>
            <span>Short bio</span>
            <textarea
              id="profileBio"
              rows="4"
              placeholder="Tell students a bit about you…"
            ></textarea>
          </label>

          <div class="profile-roles">
            <span class="profile-roles-title">Roles</span>
            <p class="skill-sub">
              You can be a teacher, learner, or both.
              <br />
            </p>
          </div>

          <button
            type="submit"
            class="btn-primary profile-save-btn"
            id="saveProfileBtn"
          >
            Save profile
          </button>
        </form>
      </div>

      <!-- Account -->
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Account</h3>
        </div>

        <div class="profile-section">
          <p class="profile-section-title">Security</p>
          <button
            class="btn-small js-show-toast"
            data-toast-message="Password change will be available soon."
          >
            Change password
          </button>
        </div>

        <div class="profile-section">
          <p class="profile-section-title">Teaching preferences</p>
          <p class="profile-section-text">
            Set your availability, time zone and languages so students can
            book easier.
          </p>
          <button
            class="btn-small js-show-toast"
            data-toast-message="Availability settings coming soon."
          >
            Edit availability
          </button>
        </div>

        <div class="profile-section">
          <p class="profile-section-title">Danger zone</p>
          <button
            class="btn-small btn-danger-small js-show-toast"
            data-toast-message="Account deactivation requires confirmation."
          >
            Deactivate account
          </button>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/dashboard.js') }}"></script>
  <script>
    const token = localStorage.getItem("token");
    if (!token) window.location.href = "{{ route('login') }}";

    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    if (token) {
      apiClient.setToken(token);
    }

    // Filter sidebar navigation for admins
    filterSidebarForAdmin(apiClient);

    // Load user profile
    async function loadProfile() {
      try {
        const user = await apiClient.getUser();
        
        // The getUser method returns the user object directly
        document.getElementById("profileName").value = user.name || "";
        document.getElementById("profileEmail").value = user.email || "";
        document.getElementById("profileBio").value = user.bio || "";
      } catch (err) {
        console.error("Error loading profile:", err);
        alert("Failed to load profile: " + (err.message || "Unknown error"));
      }
    }

    // Save profile
    document.getElementById("profileForm").addEventListener("submit", async (e) => {
      e.preventDefault();

      const name = document.getElementById("profileName").value.trim();
      const bio = document.getElementById("profileBio").value.trim();

      if (!name) {
        alert("Name is required");
        return;
      }

      try {
        const updatedUser = await apiClient.updateProfile({ name, bio });
        alert("Profile updated successfully!");
        // Update the form with the returned user data
        document.getElementById("profileName").value = updatedUser.name || name;
        document.getElementById("profileBio").value = updatedUser.bio || bio;
      } catch (err) {
        console.error("Error updating profile:", err);
        alert("Failed to update profile: " + (err.message || "Unknown error"));
      }
    });

    // Load profile on page load
    loadProfile();
  </script>
@endpush
