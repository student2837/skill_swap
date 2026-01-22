@extends('layouts.app')

@section('title', 'Session Details – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <main class="main-content">
    <header class="topbar glass">
      <h2>Session details</h2>
    </header>

    <section class="dash-card-full glass">
      <div class="dash-card-header">
        <h3>Python Programming with Dr. Mubarak</h3>
        <span class="tag tag-blue">Confirmed</span>
      </div>

      <div class="two-col">
        <div>
          <p class="profile-section-title">Session info</p>
          <p class="profile-section-text">Role: <strong>Learner</strong></p>
          <p class="profile-section-text">
            Date: <strong>Mar 21, 2025</strong>
          </p>
          <p class="profile-section-text">
            Time: <strong>6:00 PM – 7:00 PM</strong>
          </p>
          <p class="profile-section-text">Credits: <strong>5</strong></p>

          <p class="profile-section-title" style="margin-top: 1rem">Status</p>
          <p class="profile-section-text">
            Your session is confirmed. You'll receive a reminder 1 hour
            before.
          </p>
        </div>

        <div>
          <p class="profile-section-title">Actions</p>
          <div class="profile-section">
            <button
              class="btn-small js-show-toast"
              data-toast-message="Join link opened (demo)."
            >
              Join session
            </button>
          </div>
          <div class="profile-section">
            <button
              class="btn-small btn-secondary js-show-toast"
              data-toast-message="Reschedule flow (demo)."
            >
              Reschedule
            </button>
          </div>
          <div class="profile-section">
            <button
              class="btn-small btn-danger-small js-show-toast"
              data-toast-message="Cancel request (demo)."
            >
              Cancel request
            </button>
          </div>
        </div>
      </div> 
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
