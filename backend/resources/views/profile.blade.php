@extends('layouts.app')

@section('title', 'Profile – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  <script>
    // Hide sidebar on profile page
    (function() {
      const userSidebar = document.getElementById('userSidebarComponent');
      const adminSidebar = document.getElementById('adminSidebarComponent');
      if (userSidebar) userSidebar.style.display = 'none';
      if (adminSidebar) adminSidebar.style.display = 'none';
      
      // Make main content full width
      const mainContent = document.querySelector('.main-content');
      if (mainContent) {
        mainContent.style.marginLeft = '0';
        mainContent.style.width = '100%';
      }
    })();
  </script>

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <a href="#" class="back-button" id="backButton" style="margin: 0;" onclick="event.preventDefault(); goBack();">
          ←
        </a>
        <h2 style="margin: 0;">Profile & Settings</h2>
      </div>
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

        <div class="profile-section" style="margin-bottom: 2.5rem;">
          <p class="profile-section-title">Security</p>
          <button
            class="btn-primary"
            id="changePasswordBtn"
            style="padding: 0.7rem 1.5rem; font-size: 0.95rem; margin-top: 0.5rem;"
          >
            Change password
          </button>
        </div>

        <div class="profile-section">
          <p class="profile-section-title">Danger zone</p>
          <button
            class="btn-primary"
            id="deleteAccountBtn"
            style="padding: 0.7rem 1.5rem; font-size: 0.95rem; margin-top: 0.5rem; background: linear-gradient(120deg, #dc2626, #ef4444); box-shadow: 0 10px 25px rgba(220, 38, 38, 0.55);"
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

    // Set back button destination based on user role
    async function goBack() {
      try {
        if (apiClient.isAuthenticated()) {
          const user = await apiClient.getUser();
          if (user.is_admin) {
            window.location.href = "{{ route('admin.dashboard') }}";
          } else {
            window.location.href = "{{ route('dashboard') }}";
          }
        } else {
          window.location.href = "{{ route('dashboard') }}";
        }
      } catch (err) {
        console.error("Error checking user for back button:", err);
        window.location.href = "{{ route('dashboard') }}";
      }
    }

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

    // Change password functionality
    document.getElementById('changePasswordBtn').addEventListener('click', async function() {
      const currentPassword = prompt('Enter your current password:');
      if (!currentPassword) return;

      const newPassword = prompt('Enter your new password (min 8 characters):');
      if (!newPassword) return;

      if (newPassword.length < 8) {
        alert('Password must be at least 8 characters long');
        return;
      }

      const confirmPassword = prompt('Confirm your new password:');
      if (!confirmPassword) return;

      if (newPassword !== confirmPassword) {
        alert('Passwords do not match');
        return;
      }

      try {
        await apiClient.changePassword(currentPassword, newPassword);
        alert('Password changed successfully!');
      } catch (err) {
        alert('Error: ' + (err.message || 'Failed to change password'));
        console.error('Error changing password:', err);
      }
    });

    // Delete account functionality
    document.getElementById('deleteAccountBtn').addEventListener('click', async function() {
      const confirmText = prompt('Type "DELETE" to confirm account deletion. This action cannot be undone:');
      if (confirmText !== 'DELETE') {
        if (confirmText !== null) {
          alert('Account deletion cancelled');
        }
        return;
      }

      if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone and all your data will be permanently deleted.')) {
        return;
      }

      try {
        await apiClient.deleteOwnAccount();
        alert('Your account has been deleted. You will be redirected to the login page.');
        // Clear all local data
        apiClient.clearToken();
        localStorage.clear();
        sessionStorage.clear();
        // Redirect to login
        window.location.href = "{{ route('login') }}";
      } catch (err) {
        alert('Error: ' + (err.message || 'Failed to delete account'));
        console.error('Error deleting account:', err);
      }
    });
  </script>
@endpush
