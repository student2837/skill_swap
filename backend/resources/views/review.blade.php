@extends('layouts.app')

@section('title', 'Reviews – SkillSwap')

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
      <h2>Reviews</h2>
    </header>

    <section class="dash-card-full glass" style="margin-bottom: 1.5rem;">
      <div class="dash-card-header">
        <h3>Skill performance</h3>
      </div>

      <table class="request-table" id="skillPerformanceTable">
        <thead>
          <tr>
            <th>Skill</th>
            <th>Status</th>
            <th>Sessions</th>
            <th>Avg rating</th>
            <th>Credits earned</th>
          </tr>
        </thead>
        <tbody id="skillPerformanceBody">
          <tr><td colspan="5">Loading...</td></tr>
        </tbody>
      </table>
    </section>

    <section class="dash-card-full glass" style="margin-bottom: 1.5rem;">
      <div class="dash-card-header">
        <h3>All reviews</h3>
      </div>

      <div id="allReviewsList">
        <p class="muted">Loading reviews...</p>
      </div>
    </section>

    <section class="dash-card-full glass">
      <div class="dash-card-header">
        <h3>Completed Skills</h3>
      </div>

      <div id="reviewableSkillsList">
        <p class="muted">Loading completed skills...</p>
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

    let selectedRating = 0;
    let currentRequestId = null;
    let currentTeacherId = null;

    // Load reviewable skills
    async function loadReviewableSkills() {
      try {
        const requests = await apiClient.getReviewableRequests();
        const container = document.getElementById("reviewableSkillsList");
        container.innerHTML = "";

        if (!requests || !requests.length) {
          container.innerHTML = "<p class='muted'>No completed skills to review yet.</p>";
          return;
        }

        requests.forEach(request => {
          const skill = request.skill;
          const teacher = skill.user;
          const isReviewed = request.already_reviewed;

          const skillCard = document.createElement('div');
          skillCard.className = 'dash-card glass';
          skillCard.style.marginBottom = '1.5rem';
          
          skillCard.innerHTML = `
            <div class="dash-card-header">
              <div>
                <h3>${skill.title || 'Untitled Skill'}</h3>
                <p style="color: #a0a0a0; font-size: 0.875rem; margin-top: 0.25rem;">
                  Taught by ${teacher?.name || 'Unknown teacher'}
                </p>
              </div>
              <span class="tag tag-green">Completed</span>
            </div>
            ${isReviewed ? `
              <p style="color: #94a3b8; padding: 1rem 0;">You have already reviewed this skill.</p>
            ` : `
              <form class="profile-form review-form" data-request-id="${request.id}" data-teacher-id="${teacher?.id}">
                <div class="profile-section">
                  <p class="profile-section-title">Rating</p>
                  <div class="rating-stars" data-selected="0">
                    <button type="button" class="star-btn" data-value="1">★</button>
                    <button type="button" class="star-btn" data-value="2">★</button>
                    <button type="button" class="star-btn" data-value="3">★</button>
                    <button type="button" class="star-btn" data-value="4">★</button>
                    <button type="button" class="star-btn" data-value="5">★</button>
                  </div>
                </div>

                <label>
                  <span>Your feedback</span>
                  <textarea
                    name="comment"
                    rows="4"
                    placeholder="How was the session? What was helpful?"
                  ></textarea>
                </label>

                <button type="submit" class="btn-primary profile-save-btn">
                  Submit review
                </button>
              </form>
            `}
          `;
          
          container.appendChild(skillCard);

          // Add star rating functionality
          if (!isReviewed) {
            const form = skillCard.querySelector('.review-form');
            const starButtons = form.querySelectorAll('.star-btn');
            
            starButtons.forEach((btn, index) => {
              btn.addEventListener('click', (e) => {
                e.preventDefault();
                const value = parseInt(btn.getAttribute('data-value'));
                selectedRating = value;
                
                // Update star display
                starButtons.forEach((star, idx) => {
                  if (idx < value) {
                    star.style.color = '#fbbf24';
                    star.textContent = '★';
                  } else {
                    star.style.color = '#94a3b8';
                    star.textContent = '☆';
                  }
                });
              });
            });

            // Handle form submission
            form.addEventListener('submit', async (e) => {
              e.preventDefault();
              
              if (selectedRating === 0) {
                alert('Please select a rating');
                return;
              }

              const comment = form.querySelector('textarea[name="comment"]').value.trim();
              const requestId = parseInt(form.getAttribute('data-request-id'));
              const teacherId = parseInt(form.getAttribute('data-teacher-id'));

              const submitBtn = form.querySelector('button[type="submit"]');
              submitBtn.disabled = true;
              submitBtn.textContent = 'Submitting...';

              try {
                await apiClient.createReview({
                  request_id: requestId,
                  to_user_id: teacherId,
                  rating: selectedRating,
                  comment: comment || null
                });
                
                alert('Review submitted successfully!');
                loadReviewableSkills(); // Reload to show updated status
              } catch (err) {
                console.error('Error submitting review:', err);
                alert('Error: ' + (err.message || 'Failed to submit review'));
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit review';
              }
            });
          }
        });
      } catch (err) {
        console.error("Error loading reviewable skills:", err);
        document.getElementById("reviewableSkillsList").innerHTML = 
          "<p class='muted'>Error loading completed skills. Please try again.</p>";
      }
    }

    // Load on page load
    window.addEventListener('DOMContentLoaded', () => {
      loadSkillPerformance();
      loadAllReviews();
      loadReviewableSkills();
    });

    async function loadSkillPerformance() {
      const tbody = document.getElementById('skillPerformanceBody');
      if (!tbody) return;

      try {
        const rows = await apiClient.getSkillPerformance();
        if (!rows || !rows.length) {
          tbody.innerHTML = "<tr><td colspan='5'>No performance data yet</td></tr>";
          return;
        }

        tbody.innerHTML = rows.map(r => `
          <tr>
            <td>${r.skill_title || 'Untitled'}</td>
            <td>${statusBadge(r.status || 'draft')}</td>
            <td style="text-align:center;">${r.sessions_count ?? 0}</td>
            <td style="text-align:center;">${(r.avg_rating ?? 0)} (${r.ratings_count ?? 0})</td>
            <td style="text-align:center;">${r.credits_earned ?? 0}</td>
          </tr>
        `).join('');
      } catch (err) {
        console.error("Error loading skill performance:", err);
        tbody.innerHTML = "<tr><td colspan='5'>Error loading performance</td></tr>";
      }
    }

    async function loadAllReviews() {
      const container = document.getElementById('allReviewsList');
      if (!container) return;

      try {
        const me = await apiClient.getUser();
        const reviews = await apiClient.getReviewsForUser(me.id);

        if (!reviews || !reviews.length) {
          container.innerHTML = "<p class='muted'>No reviews yet.</p>";
          return;
        }

        container.innerHTML = reviews.map(r => {
          const skillTitle = r.request?.skill?.title || 'Skill';
          const reviewerName = r.from_user?.name || r.fromUser?.name || 'User';
          const rating = r.rating ?? 0;
          const comment = r.comment ? String(r.comment) : '';
          const createdAt = r.created_at ? new Date(r.created_at).toLocaleDateString() : '';

          return `
            <div class="dash-card glass" style="margin-bottom: 1rem;">
              <div class="dash-card-header">
                <div>
                  <h3 style="margin:0;">${skillTitle}</h3>
                  <p style="color: #94a3b8; font-size: 0.875rem; margin-top: 0.25rem;">
                    By ${reviewerName}${createdAt ? ` • ${createdAt}` : ''}
                  </p>
                </div>
                <span class="tag tag-blue">${rating} / 5</span>
              </div>
              ${comment ? `<p class="side-text" style="padding: 0.9rem 0;">${comment}</p>` : `<p class="side-text" style="padding: 0.9rem 0;">No comment.</p>`}
            </div>
          `;
        }).join('');
      } catch (err) {
        console.error("Error loading reviews:", err);
        container.innerHTML = "<p class='muted'>Error loading reviews. Please try again.</p>";
      }
    }
  </script>
@endpush
