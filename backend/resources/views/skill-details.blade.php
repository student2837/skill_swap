@extends('layouts.app')

@section('title', 'Skill Details – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
@endpush

@section('body-class', 'page')

@section('content')
  <div class="page-bg"></div>

  <!-- Header -->
  <header class="site-header glass">
    <div class="container nav-container">
      <a href="{{ route('index') }}" class="logo-wrap">
        <img
          src="{{ asset('assets/logo.png') }}"
          alt="SkillSwap Logo"
          class="logo-img"
        />
      </a>

      <nav class="nav-links">
        <a href="{{ route('browse') }}">Browse Skills</a>
        <a href="{{ route('index') }}#how-it-works">How it Works</a>
        <a href="{{ route('favorites') }}">Favorites</a>
      </nav>

      <div class="nav-actions" id="navActions">
        <a href="{{ route('login') }}" class="nav-link-small">Log in</a>
        <a href="{{ route('register') }}" class="btn btn-sm btn-primary">Join Now</a>
      </div>
    </div>
  </header>

  <!-- Main -->
  <main>
    <section class="section">
      <div class="container">
        <button class="back-button" onclick="window.history.back()">
          ← Back
        </button>
      </div>
      <div class="container skill-details-layout glass">
        <!-- Main content -->
        <div class="skill-main">
          <p class="skill-label" id="skillCategory">Loading...</p>

          <h1 class="skill-title-lg" id="skillTitle">
            Loading...
          </h1>

          <p class="skill-teacher-lg" id="skillTeacher">
            Loading...
          </p>

          <div class="skill-highlight-row" id="skillHighlights" style="margin-bottom: 1.5rem;">
            <span class="chip chip-soft">Loading...</span>
          </div>

          <p class="skill-sub" id="skillShortDesc">
            Loading...
          </p>

          <p class="skill-desc" id="skillDescription">
            Loading...
          </p>

          <div class="skill-actions">
            <a
              href="#"
              id="learnMoreBtn"
              class="btn btn-primary"
            >
              Ask the teacher
            </a>

            <button
              class="btn btn-ghost js-show-toast"
              data-toast-message="Added to favorites."
              id="addFavoriteBtn"
            >
              Add to Favorites
            </button>
          </div>

          <h2 class="section-subtitle-h2">
            What you'll learn
          </h2>

          <ul class="bullet-list" id="whatYoullLearn">
            <li>Loading...</li>
          </ul>
        </div>

        <!-- Sidebar -->
        <aside class="skill-side">
          <div class="side-card" id="bookingCard" style="display: none;">
            <h3>Book this skill</h3>
            <p class="side-text" style="margin-bottom: 1rem;">
              Skill rate: <strong id="bookingSkillPrice">Loading...</strong>
            </p>
            <p class="side-text" style="margin-bottom: 1rem;">
              Your balance: <strong id="bookingUserCredits">Loading...</strong>
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
              <button
                class="btn btn-primary"
                id="bookNowBtn"
                style="width: 100%;"
              >
                Book Now
              </button>
              <button
                class="btn btn-secondary"
                id="buyCreditsBtn"
                style="width: 100%;"
              >
                Buy Credits
              </button>
            </div>
          </div>

          <div class="side-card" id="teacherInfo">
            <h3>About the teacher</h3>
            <p><strong>Loading...</strong></p>
            <p class="side-text">
              Loading...
            </p>
          </div>

          <div class="side-card" id="reviewsInfo">
            <h3>Reviews</h3>
            <p class="side-text">
              Loading...
            </p>
          </div>
        </aside>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    const token = localStorage.getItem("token");
    if (token) {
      apiClient.setToken(token);
    }

    // Update navigation based on authentication status
    function updateNavigation() {
      const navActions = document.getElementById("navActions");
      const isAuthenticated = apiClient.isAuthenticated();
      
      if (isAuthenticated) {
        navActions.innerHTML = `
          <a href="{{ route('dashboard') }}" class="btn btn-sm btn-primary">Dashboard</a>
        `;
      } else {
        navActions.innerHTML = `
          <a href="{{ route('login') }}" class="nav-link-small">Log in</a>
          <a href="{{ route('register') }}" class="btn btn-sm btn-primary">Join Now</a>
        `;
      }
    }

    // Update navigation on page load
    updateNavigation();

    // Get skill ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const skillId = urlParams.get('id');

    if (!skillId) {
      alert("Skill ID not found");
      window.location.href = "{{ route('browse') }}";
    }

    // Load skill data
    async function loadSkillDetails() {
      try {
        const skill = await apiClient.getSkill(skillId);
        
        if (!skill) {
          alert("Skill not found");
          window.location.href = "{{ route('browse') }}";
          return;
        }

        // Helper function to capitalize first letter
        function capitalize(str) {
          return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
        }

        // Populate skill details
        const categoryName = skill.category ? capitalize(skill.category) : 'Other';
        document.getElementById('skillCategory').textContent = categoryName;
        document.getElementById('skillTitle').textContent = skill.title || 'Untitled';
        document.getElementById('skillTeacher').textContent = `Taught by ${skill.user?.name || 'Unknown teacher'}`;
        
        // Update highlight row
        const studentsCount = skill.students_count || 0;
        const rating = skill.rating_avg || null;
        const ratingDisplay = rating ? rating.toFixed(1) : 'N/A';
        
        // Format lesson type
        let lessonTypeDisplay = '';
        if (skill.lesson_type) {
          if (skill.lesson_type === 'online') {
            lessonTypeDisplay = '<span class="chip chip-soft">🌐 Online</span>';
          } else if (skill.lesson_type === 'inperson') {
            lessonTypeDisplay = '<span class="chip chip-soft">👥 In-Person</span>';
          }
        }
        
        const highlightRow = document.getElementById('skillHighlights');
        highlightRow.innerHTML = `
          <span class="chip chip-soft">${skill.price || 0} credits</span>
          ${lessonTypeDisplay}
          <span class="rating">★ ${ratingDisplay}</span>
          <span class="skill-meta-small">${studentsCount} ${studentsCount === 1 ? 'student' : 'students'}</span>
        `;

        // Update descriptions
        document.getElementById('skillShortDesc').textContent = skill.shortDesc || skill.description?.substring(0, 100) || 'No description available.';
        document.getElementById('skillDescription').textContent = skill.description || 'No full description available.';

        // Update "What you'll learn" section
        const learnList = document.getElementById('whatYoullLearn');
        if (skill.what_youll_learn && skill.what_youll_learn.trim()) {
          const learnItems = skill.what_youll_learn.split('\n').filter(item => item.trim());
          if (learnItems.length > 0) {
            learnList.innerHTML = learnItems.map(item => `<li>${item.trim()}</li>`).join('');
          } else {
            learnList.innerHTML = '<li>No learning outcomes specified</li>';
          }
        } else {
          learnList.innerHTML = '<li>No learning outcomes specified</li>';
        }

        // Update teacher info
        const teacherCard = document.getElementById('teacherInfo');
        teacherCard.innerHTML = `
          <h3 style="margin-bottom: 1rem;">About the teacher</h3>
          <p style="margin-bottom: 0.75rem;"><strong>${skill.user?.name || 'Unknown'}</strong></p>
          <p class="side-text">${skill.user?.bio || 'No bio available.'}</p>
        `;

        // Load and display reviews
        async function loadReviews() {
          try {
            const reviews = await apiClient.getReviewsForSkill(skill.id);
            const reviewsCard = document.getElementById('reviewsInfo');
            const studentsCountForReviews = skill.students_count || 0;
            const ratingForReviews = skill.rating_avg || null;
            
            let reviewsHTML = `<h3 style="margin-bottom: 1rem;">Reviews</h3>`;
            
            if (!reviews || reviews.length === 0) {
              reviewsHTML += `<p class="side-text">No reviews yet. Be the first to take this course!</p>`;
            } else {
              reviewsHTML += `<p class="side-text" style="margin-bottom: 1rem;">
                ⭐ ${ratingForReviews ? ratingForReviews.toFixed(1) : 'N/A'} average rating from ${reviews.length} ${reviews.length === 1 ? 'review' : 'reviews'}
              </p>`;
              
              reviewsHTML += `<div style="max-height: 300px; overflow-y: auto;">`;
              reviews.forEach(review => {
                const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                const reviewerName = review.fromUser?.name || review.from_user?.name || review.fromUser?.name || 'Unknown';
                reviewsHTML += `
                  <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                      <strong style="font-size: 0.9rem;">${reviewerName}</strong>
                      <span style="color: #fbbf24; font-size: 0.875rem;">${stars}</span>
                    </div>
                    ${review.comment ? `<p class="side-text" style="font-size: 0.875rem; margin: 0;">${review.comment}</p>` : ''}
                  </div>
                `;
              });
              reviewsHTML += `</div>`;
            }
            
            reviewsCard.innerHTML = reviewsHTML;
          } catch (err) {
            console.error('Error loading reviews:', err);
            const reviewsCard = document.getElementById('reviewsInfo');
            reviewsCard.innerHTML = `
              <h3 style="margin-bottom: 1rem;">Reviews</h3>
              <p class="side-text">Error loading reviews.</p>
            `;
          }
        }
        
        loadReviews();

        // Update "Learn More" button - hide if user is the teacher
        const learnMoreBtn = document.getElementById('learnMoreBtn');
        if (learnMoreBtn) {
          let isAuthenticated = false;
          let currentUser = null;
          
          if (apiClient.isAuthenticated()) {
            try {
              currentUser = await apiClient.getUser();
              isAuthenticated = true;
            } catch (err) {
              console.error('Token invalid or expired:', err);
              localStorage.removeItem('token');
              apiClient.setToken(null);
              isAuthenticated = false;
            }
          }
          
          if (isAuthenticated && currentUser) {
            const teacherId = skill.user_id || skill.user?.id;
            if (currentUser.id === teacherId) {
              learnMoreBtn.style.display = 'none';
            } else {
              learnMoreBtn.href = `{{ route('request-session') }}?skillId=${skill.id}`;
              learnMoreBtn.style.display = '';
            }
          } else {
            learnMoreBtn.href = `{{ route('request-session') }}?skillId=${skill.id}`;
            learnMoreBtn.onclick = (e) => {
              e.preventDefault();
              window.location.href = '{{ route('register') }}';
            };
          }
        }

        // Setup booking section
        let currentUser = null;
        async function setupBookingSection() {
          let isAuthenticated = false;
          
          if (apiClient.isAuthenticated()) {
            try {
              currentUser = await apiClient.getUser();
              isAuthenticated = true;
            } catch (err) {
              console.error('Token invalid or expired:', err);
              localStorage.removeItem('token');
              apiClient.setToken(null);
              isAuthenticated = false;
            }
          }

          const bookingCard = document.getElementById('bookingCard');
          const bookNowBtn = document.getElementById('bookNowBtn');
          const buyCreditsBtn = document.getElementById('buyCreditsBtn');

          if (isAuthenticated && currentUser) {
            const teacherId = skill.user_id || skill.user?.id;
            if (currentUser.id === teacherId) {
              bookingCard.style.display = 'none';
            } else {
              bookingCard.style.display = 'block';
              
              const skillPrice = skill.price || 0;
              document.getElementById('bookingSkillPrice').textContent = `${skillPrice} credits`;
              document.getElementById('bookingUserCredits').textContent = `${currentUser.credits || 0} credits`;

              bookNowBtn.onclick = async (e) => {
                e.preventDefault();
                
                try {
                  currentUser = await apiClient.getUser();
                  document.getElementById('bookingUserCredits').textContent = `${currentUser.credits || 0} credits`;
                } catch (err) {
                  console.error('Error refreshing user data:', err);
                }
                
                if (currentUser.credits < skillPrice) {
                  const needed = skillPrice - currentUser.credits;
                  alert(`Not enough credits!\n\nYou need ${skillPrice} credits but only have ${currentUser.credits} credits.\nYou need to buy ${needed} more credits to book this session.\n\nRedirecting to buy credits...`);
                  window.location.href = `{{ route('credits') }}?buy=true&amount=${skillPrice}`;
                  return;
                }

                const confirmBooking = confirm(`Book this session for ${skillPrice} credits?`);
                if (!confirmBooking) return;

                bookNowBtn.disabled = true;
                bookNowBtn.textContent = 'Booking...';

                try {
                  await apiClient.createRequest(skill.id);
                  alert('Session booked successfully!');
                  currentUser = await apiClient.getUser();
                  document.getElementById('bookingUserCredits').textContent = `${currentUser.credits || 0} credits`;
                  window.location.href = '{{ route('requests') }}';
                } catch (err) {
                  console.error('Error booking session:', err);
                  
                  const errorMsg = err.message || err.error || '';
                  if (errorMsg.toLowerCase().includes('credit') || errorMsg.toLowerCase().includes('insufficient')) {
                    alert(`Not enough credits!\n\nYou need ${skillPrice} credits to book this session.\nRedirecting to buy credits...`);
                    window.location.href = `{{ route('credits') }}?buy=true&amount=${skillPrice}`;
                  } else {
                    alert('Error: ' + errorMsg + '\n\nFailed to book session');
                  }
                } finally {
                  bookNowBtn.disabled = false;
                  bookNowBtn.textContent = 'Book Now';
                }
              };

              buyCreditsBtn.onclick = (e) => {
                e.preventDefault();
                window.location.href = `{{ route('credits') }}?buy=true`;
              };
            }
          } else {
            bookingCard.style.display = 'none';
          }
        }

        setupBookingSection();

        // Check if skill is favorited and update button
        let isFavorited = false;
        async function updateFavoriteButton() {
          const addFavoriteBtn = document.getElementById('addFavoriteBtn');
          if (!addFavoriteBtn) return;

          let isAuthenticated = false;
          let currentUser = null;
          
          if (apiClient.isAuthenticated()) {
            try {
              currentUser = await apiClient.getUser();
              isAuthenticated = true;
            } catch (err) {
              console.error('Token invalid or expired:', err);
              localStorage.removeItem('token');
              apiClient.setToken(null);
              isAuthenticated = false;
            }
          }

          if (!isAuthenticated) {
            addFavoriteBtn.onclick = (e) => {
              e.preventDefault();
              window.location.href = '{{ route('register') }}';
            };
            return;
          }

          try {
            const teacherId = skill.user_id || skill.user?.id;
            if (currentUser && currentUser.id === teacherId) {
              addFavoriteBtn.style.display = 'none';
              return;
            }

            const favorites = await apiClient.listFavorites();
            isFavorited = favorites.some(fav => fav.skill && fav.skill.id === skill.id);
            
            if (isFavorited) {
              addFavoriteBtn.textContent = 'Remove from Favorites';
              addFavoriteBtn.classList.remove('btn-ghost');
              addFavoriteBtn.classList.add('btn-danger');
            } else {
              addFavoriteBtn.textContent = 'Add to Favorites';
              addFavoriteBtn.classList.remove('btn-danger');
              addFavoriteBtn.classList.add('btn-ghost');
            }

            addFavoriteBtn.onclick = async (e) => {
              e.preventDefault();
              
              try {
                if (isFavorited) {
                  await apiClient.removeFavorite({ skill_id: skill.id });
                  alert('Removed from favorites');
                  isFavorited = false;
                  addFavoriteBtn.textContent = 'Add to Favorites';
                  addFavoriteBtn.classList.remove('btn-danger');
                  addFavoriteBtn.classList.add('btn-ghost');
                } else {
                  await apiClient.addFavorite({ skill_id: skill.id });
                  alert('Added to favorites successfully!');
                  isFavorited = true;
                  addFavoriteBtn.textContent = 'Remove from Favorites';
                  addFavoriteBtn.classList.remove('btn-ghost');
                  addFavoriteBtn.classList.add('btn-danger');
                }
              } catch (err) {
                console.error('Error toggling favorite:', err);
                alert('Error: ' + (err.message || err.error || 'Failed to update favorite'));
              }
            };
          } catch (err) {
            console.error('Error checking favorites:', err);
            addFavoriteBtn.textContent = 'Add to Favorites';
          }
        }

        updateFavoriteButton();

        // Listen for favorite removed events from favorites page
        window.addEventListener('favoriteRemoved', (e) => {
          if (e.detail.skillId === skill.id) {
            isFavorited = false;
            const addFavoriteBtn = document.getElementById('addFavoriteBtn');
            if (addFavoriteBtn) {
              addFavoriteBtn.textContent = 'Add to Favorites';
              addFavoriteBtn.classList.remove('btn-danger');
              addFavoriteBtn.classList.add('btn-ghost');
            }
          }
        });

      } catch (err) {
        console.error("Error loading skill details:", err);
        let errorMsg = "Unknown error";
        
        if (err.message) {
          errorMsg = err.message;
        } else if (err.data) {
          if (err.data.error) {
            errorMsg = err.data.error;
          } else if (err.data.message) {
            errorMsg = err.data.message;
          }
        } else if (err.error) {
          errorMsg = err.error;
        } else if (typeof err === 'string') {
          errorMsg = err;
        }
        
        alert("Failed to load skill details: " + errorMsg + "\n\nPlease check the browser console for more details.");
      }
    }

    loadSkillDetails();
  </script>
@endpush
