@extends('layouts.app')

@section('title', 'Favorites – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  <style>
    .remove-favorite-btn:hover {
      background: linear-gradient(120deg, #b91c1c, #dc2626) !important;
      box-shadow: 0 6px 16px rgba(220, 38, 38, 0.6) !important;
      transform: translateY(-1px);
      filter: brightness(1.1);
    }
  </style>
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

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

    <section class="dash-card-full glass" style="padding: 0; overflow: visible; width: 100%;">
      <div class="dash-card-header" style="padding: 1.5rem 2rem;">
        <h3>Your favorite skills</h3>
      </div>

      <div style="width: 100%; padding: 2rem; box-sizing: border-box;">
        <div class="skills-grid" id="favoritesGrid" style="width: 100%; margin: 0;">
          <!-- Favorite cards will be rendered by backend later -->
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
      const favoritesGrid = document.getElementById('favoritesGrid');
      if (!favoritesGrid) {
        console.error('Favorites grid element not found');
        return;
      }
      favoritesGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 3rem 1rem;">Loading favorites...</p>';

      try {
        const favorites = await apiClient.listFavorites();
        
        if (!favorites || favorites.length === 0) {
          favoritesGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 3rem 1rem;">No favorites yet. Start browsing skills to add some!</p>';
          return;
        }

        favoritesGrid.innerHTML = favorites.map(favorite => {
          const skill = favorite.skill;
          if (!skill) {
            return ''; // Skip if no skill associated
          }

          // Get rating
          const rating = skill.rating_avg ? skill.rating_avg.toFixed(1) : 'N/A';
          const title = skill.title || 'Untitled';
          const teacherName = skill.user?.name || 'Unknown teacher';
          const price = skill.price || 0;
          const shortDesc = skill.shortDesc || skill.description || 'No description available.';

          return `
            <div class="skill-card glass">
              <div class="skill-card-header">
                <h3>${title}</h3>
                <span class="chip chip-soft">${price} credits</span>
              </div>
              <p class="skill-card-teacher">${teacherName}</p>
              <p class="skill-card-desc">${shortDesc}</p>
              <div class="skill-card-footer">
                <span class="rating">★ ${rating}</span>
                <div style="display: flex; gap: 0.5rem; align-items: center; flex-shrink: 0;">
                  <a href="{{ route('skill-details') }}?id=${skill.id}" class="btn btn-sm btn-primary">View Details</a>
                  <button class="btn btn-sm btn-danger remove-favorite-btn" data-favorite-id="${favorite.id}" data-skill-id="${skill.id}" style="background: linear-gradient(120deg, #dc2626, #ef4444); color: #fff; border: none; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4); border-radius: 999px; cursor: pointer; transition: all 0.2s ease;">
                    Remove
                  </button>
                </div>
              </div>
            </div>
          `;
        }).join('');

        // Add event listeners for remove buttons
        document.querySelectorAll('.remove-favorite-btn').forEach(btn => {
          btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const skillId = btn.getAttribute('data-skill-id');
            
            if (!confirm('Are you sure you want to remove this skill from your favorites?')) {
              return;
            }
            
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
        const favoritesGrid = document.getElementById('favoritesGrid');
        if (favoritesGrid) {
          favoritesGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 3rem 1rem;">Error loading favorites. Please try again.</p>';
        }
      }
    }

    // Load favorites on page load
    window.addEventListener('DOMContentLoaded', () => {
      loadFavorites();
    });
  </script>
@endpush
