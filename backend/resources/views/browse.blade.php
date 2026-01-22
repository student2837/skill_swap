@extends('layouts.app')

@section('title', 'Browse Skills – SkillSwap')

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
        <img src="{{ asset('assets/logo.png') }}" alt="SkillSwap Logo" class="logo-img" />
      </a>

      <nav class="nav-links">
        <a href="{{ route('index') }}">Home</a>
        <a href="{{ route('index') }}#how-it-works">How it Works</a>
      </nav>

      <div class="nav-actions" id="navActions">
        <a href="{{ route('login') }}" class="nav-link-small">Log in</a>
        <a href="{{ route('register') }}" class="btn btn-sm btn-primary">Register</a>
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
        <div class="section-header">
          <h2>Browse Skills</h2>
          <p>Discover courses and learn directly from skilled teachers.</p>
        </div>

        <div class="browse-layout">
          <!-- Filters Sidebar -->
          <aside class="filters glass">
            <h3>Filters</h3>

            <label class="filter-label">
              <span>Search</span>
              <input
                type="text"
                id="browse-search"
                placeholder="Search courses or teachers"
                onkeydown="if(event.key==='Enter') applyBrowseFilters()"
              />
            </label>

            <label class="filter-label">
              <span>Category</span>
              <select id="browse-category">
                <option value="">All</option>
                <option value="programming">Programming</option>
                <option value="design">Design</option>
                <option value="music">Music</option>
                <option value="languages">Languages</option>
                <option value="other">Other</option>
              </select>
            </label>

            <label class="filter-label">
              <span>Min rating</span>
              <select id="browse-rating">
                <option value="">Any</option>
                <option value="3">+3</option>
                <option value="4">+4</option>
                <option value="4.5">+4.5</option>
                <option value="4.8">+4.8</option>
              </select>
            </label>

            <button
              class="btn btn-primary btn-sm"
              onclick="applyBrowseFilters()"
            >
              Search
            </button>
          </aside>

          <!-- Skills Grid -->
          <div class="skills-grid-container">
            <div class="skills-grid" id="browseSkillsGrid"></div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="container footer-grid">
      <div>
        <p class="footer-brand">SkillSwap</p>
        <p class="footer-text">Find skills to learn and share your own.</p>
      </div>
      <p class="footer-copy">© 2025 SkillSwap.</p>
    </div>
  </footer>
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
          <a href="{{ route('register') }}" class="btn btn-sm btn-primary">Register</a>
        `;
      }
    }

    // Update navigation on page load
    window.addEventListener('DOMContentLoaded', () => {
      updateNavigation();
    });

    async function applyBrowseFilters() {
      const searchQuery = document.getElementById("browse-search").value.trim();
      const categoryValue = document.getElementById("browse-category").value;
      const minRatingValue = document.getElementById("browse-rating").value;
      const grid = document.getElementById("browseSkillsGrid");
      grid.innerHTML = "<p>Loading...</p>";

      try {
        let skills = [];

        if (searchQuery) {
          // When searching, also apply category filter if selected
          const categoryFilter = categoryValue && categoryValue !== "" ? categoryValue : null;
          skills = await apiClient.searchSkills(searchQuery, minRatingValue, categoryFilter);
        } else if (categoryValue && categoryValue !== "") {
          // Filter by enum category
          skills = await apiClient.getSkillsByCategory(categoryValue, minRatingValue);
        } else {
          // Load all active skills
          skills = await apiClient.listAllSkills(minRatingValue);
        }

        if (!skills || !skills.length) {
          grid.innerHTML = "<p>No skills found</p>";
          return;
        }

        grid.innerHTML = skills.map(skill => `
          <div class="skill-card glass">
            <div class="skill-card-header">
              <h3>${skill.title || skill.skill_name || 'Untitled'}</h3>
              <span class="chip chip-soft">${skill.price || skill.credits || 0} credits</span>
            </div>
            <p class="skill-card-teacher">${skill.user?.name || skill.user_name || 'Unknown teacher'}</p>
            <p class="skill-card-desc">${skill.shortDesc || skill.description || 'No description available.'}</p>
            <div class="skill-card-footer">
              <span class="rating">★ ${skill.rating_avg ? skill.rating_avg.toFixed(1) : 'N/A'}</span>
              <a href="{{ route('skill-details') }}?id=${skill.id}" class="btn btn-sm btn-primary">View Details</a>
            </div>
          </div>
        `).join('');
      } catch (err) {
        console.error("Error loading skills:", err);
        grid.innerHTML = "<p>Error loading skills</p>";
      }
    }

    // Check for URL parameters and apply filters
    function checkUrlParameters() {
      const urlParams = new URLSearchParams(window.location.search);
      const categoryParam = urlParams.get('category');
      const searchParam = urlParams.get('search');
      
      // Handle category parameter
      if (categoryParam) {
        const categorySelect = document.getElementById("browse-category");
        if (categorySelect) {
          categorySelect.value = categoryParam;
        }
      }
      
      // Handle search parameter
      if (searchParam) {
        const searchInput = document.getElementById("browse-search");
        if (searchInput) {
          searchInput.value = decodeURIComponent(searchParam);
        }
      }
    }

    // Load initial skills
    window.addEventListener('DOMContentLoaded', () => {
      // Check for URL parameters first
      checkUrlParameters();
      // Then apply filters (which will use the parameters from URL if set)
      applyBrowseFilters();
    });
  </script>
@endpush
