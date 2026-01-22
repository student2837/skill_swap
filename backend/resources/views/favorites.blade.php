@extends('layouts.app')

@section('title', 'Favorites – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Favorites</h2>
      <div class="topbar-actions">
        <a href="{{ route('browse') }}" class="btn-secondary">
          Browse more skills
        </a>
      </div>
    </header>

    <section class="dash-card-full glass">
      <div class="dash-card-header">
        <h3>Your favorite skills</h3>
      </div>

      <div class="favorites-grid">
        <!-- Favorite cards will be rendered by backend later -->
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

    // Load favorites
    async function loadFavorites() {
      const favoritesGrid = document.querySelector('.favorites-grid');
      favoritesGrid.innerHTML = '<p>Loading favorites...</p>';

      try {
        const favorites = await apiClient.listFavorites();
        
        if (!favorites || favorites.length === 0) {
          favoritesGrid.innerHTML = '<p>No favorites yet. Start browsing skills to add some!</p>';
          return;
        }

        favoritesGrid.innerHTML = favorites.map(favorite => {
          const skill = favorite.skill;
          if (!skill) {
            return ''; // Skip if no skill associated
          }

          // Format category
          const category = skill.category ? skill.category.charAt(0).toUpperCase() + skill.category.slice(1) : 'Other';
          
          // Get rating
          const rating = skill.rating_avg ? skill.rating_avg.toFixed(1) : (skill.user?.rating_avg ? skill.user.rating_avg.toFixed(1) : 'N/A');
          
          // Truncate long titles
          const title = skill.title || 'Untitled';
          const truncatedTitle = title.length > 40 ? title.substring(0, 40) + '...' : title;
          
          // Truncate teacher name if needed
          const teacherName = skill.user?.name || 'Unknown teacher';
          const truncatedTeacher = teacherName.length > 25 ? teacherName.substring(0, 25) + '...' : teacherName;

          return `
            <div class="favorite-card">
              <div class="fav-card-content">
                <div class="fav-category">${category}</div>
                <h3 class="fav-title" title="${title}">${truncatedTitle}</h3>
                <p class="fav-meta">${truncatedTeacher} • ${skill.price || 0} credits</p>
                <div class="fav-rating">
                  <span class="rating-star">★</span>
                  <span class="rating-value">${rating}</span>
                </div>
              </div>
              <div class="fav-card-footer">
                <a href="{{ route('skill-details') }}?id=${skill.id}" class="fav-btn-view">View</a>
                <button class="fav-btn-remove remove-favorite-btn" data-favorite-id="${favorite.id}" data-skill-id="${skill.id}">
                  Remove
                </button>
              </div>
            </div>
          `;
        }).join('');

        // Add event listeners for remove buttons
        document.querySelectorAll('.remove-favorite-btn').forEach(btn => {
          btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const skillId = e.target.getAttribute('data-skill-id');
            
            try {
              await apiClient.removeFavorite({ skill_id: parseInt(skillId) });
              // Reload the list
              loadFavorites();
              // If we're on the skill details page, it will update when the page is refreshed
              // We can dispatch a custom event to notify other pages
              window.dispatchEvent(new CustomEvent('favoriteRemoved', { detail: { skillId: parseInt(skillId) } }));
            } catch (err) {
              alert('Error removing favorite: ' + (err.message || 'Unknown error'));
            }
          });
        });

      } catch (err) {
        console.error('Error loading favorites:', err);
        favoritesGrid.innerHTML = '<p>Error loading favorites. Please try again.</p>';
      }
    }

    // Load favorites on page load
    window.addEventListener('DOMContentLoaded', () => {
      loadFavorites();
    });
  </script>
@endpush
