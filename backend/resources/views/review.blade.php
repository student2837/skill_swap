@extends('layouts.app')

@section('title', 'Leave a Review – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Leave a review</h2>
    </header>

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
      loadReviewableSkills();
    });
  </script>
@endpush
