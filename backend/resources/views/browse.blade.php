@extends('layouts.app')

@section('title', 'Browse Skills – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  <style>
    #browseHeader {
      display: none;
    }
    /* Browse skill cards styling */
    #browseSkillsGrid {
      grid-template-columns: repeat(auto-fill, minmax(240px, 260px)) !important;
    }
    #browseSkillsGrid .skill-card {
      padding: 0.9rem 1rem !important;
      width: 100%;
      max-width: 260px;
      min-height: 280px;
      background: linear-gradient(180deg, #0f172a, #1e40af) !important;
      display: flex !important;
      flex-direction: column !important;
      overflow: visible !important;
      position: relative !important;
    }
    #browseSkillsGrid .skill-card::before {
      display: none !important;
      content: none !important;
    }
    #browseSkillsGrid .skill-card-header {
      border-bottom: none !important;
      padding-bottom: 0 !important;
      margin-bottom: 0 !important;
      flex: 0 0 auto;
    }
    #browseSkillsGrid .skill-card-header h3 {
      font-size: 1.4rem !important;
      line-height: 1.2 !important;
      margin: 0 !important;
      padding: 0 !important;
      text-align: left !important;
    }
    #browseSkillsGrid .skill-card-header h3::after {
      display: none;
    }
    #browseSkillsGrid .skill-card-header .chip {
      font-size: 0.85rem !important;
    }
    #browseSkillsGrid .skill-card-teacher {
      color: rgba(255, 255, 255, 0.85) !important;
      margin: 0 !important;
      margin-top: 0.3rem !important;
      padding: 0 !important;
      padding-left: 0 !important;
      font-size: 1.3rem !important;
      font-weight: normal !important;
      line-height: 1.2 !important;
      text-align: left !important;
      flex: 0 0 auto;
    }
    #browseSkillsGrid .skill-card-teacher::before {
      display: none;
    }
    #browseSkillsGrid .skill-card-teacher .verified-check {
      display: inline-block;
      margin-left: 0.3rem;
      color: #3b82f6;
      font-weight: 700;
      font-size: 1rem;
      vertical-align: middle;
    }
    #browseSkillsGrid .skill-card-desc {
      margin: 0 !important;
      margin-top: 0.5rem !important;
      margin-left: 0 !important;
      padding: 0 !important;
      padding-left: 0 !important;
      font-size: 0.95rem !important;
      text-align: left !important;
      display: block !important;
      visibility: visible !important;
      position: relative !important;
      flex: 0 0 auto;
      flex-grow: 0;
    }
    #browseSkillsGrid .skill-card-footer {
      border-top: none !important;
      margin-top: auto !important;
      padding-top: 1rem !important;
      display: flex !important;
      justify-content: space-between !important;
      align-items: center !important;
      width: 100% !important;
      flex: 0 0 auto;
    }
    #browseSkillsGrid .skill-card-footer .rating .rating-value {
      font-size: 1rem !important;
    }
    #browseSkillsGrid .skill-card-footer .btn {
      font-size: 0.9rem !important;
    }
  </style>
@endpush

@section('body-class', 'page')

@section('content')
  <div class="page-bg"></div>

  <!-- Header -->
  <header class="site-header glass" id="browseHeader">
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
          ←
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

    // Show header only for non-authenticated users
    (async function() {
      try {
        const header = document.getElementById('browseHeader');
        if (!header) return;
        
        // Check if user is authenticated
        const token = localStorage.getItem('token');
        
        if (token && apiClient.isAuthenticated()) {
          // User is logged in, hide header
          header.style.display = 'none';
        } else {
          // User is not logged in, show header
          header.style.display = 'block';
        }
      } catch (err) {
        console.error("Error checking authentication for header:", err);
        // On error, show header (assume not logged in)
        const header = document.getElementById('browseHeader');
        if (header) header.style.display = 'block';
      }
    })();

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
            <p class="skill-card-teacher">${skill.user?.name || skill.user_name || 'Unknown teacher'}${(skill.user?.is_verified || skill.is_verified) ? '<span class="verified-check">✓</span>' : ''}</p>
            <p class="skill-card-desc">${skill.shortDesc || skill.description || 'No description available.'}</p>
            <div class="skill-card-footer">
              <span class="rating"><span class="rating-star">★</span> <span class="rating-value">${skill.rating_avg ? skill.rating_avg.toFixed(1) : 'N/A'}</span></span>
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
          // Category can be ID or name. Prefer matching by ID; fallback to name match.
          if (/^\d+$/.test(categoryParam)) {
            categorySelect.value = categoryParam;
          } else {
            const normalized = categoryParam.trim().toLowerCase();
            const match = Array.from(categorySelect.options).find(opt => {
              const optName = (opt.dataset?.name || opt.textContent || '').trim().toLowerCase();
              return optName === normalized;
            });
            if (match) categorySelect.value = match.value;
          }
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

    async function loadBrowseCategories() {
      const categorySelect = document.getElementById("browse-category");
      if (!categorySelect) return;

      try {
        const categories = await apiClient.listCategories();
        if (!Array.isArray(categories) || categories.length === 0) return;

        // Keep the "All" option, then append categories from DB
        const allOption = categorySelect.querySelector('option[value=""]');
        categorySelect.innerHTML = '';
        if (allOption) categorySelect.appendChild(allOption);
        else {
          const opt = document.createElement('option');
          opt.value = '';
          opt.textContent = 'All';
          categorySelect.appendChild(opt);
        }

        // Sort categories: alphabetically first, then "other" should always be last
        const sortedCategories = [...categories].sort((a, b) => {
          const aName = (a.name || '').toLowerCase();
          const bName = (b.name || '').toLowerCase();
          if (aName === 'other') return 1;
          if (bName === 'other') return -1;
          return aName.localeCompare(bName); // Alphabetical order for others
        });
        
        sortedCategories.forEach(cat => {
          if (!cat || cat.id == null) return;
          const opt = document.createElement('option');
          opt.value = String(cat.id);
          opt.textContent = cat.name ?? `Category #${cat.id}`;
          opt.dataset.name = String(cat.name ?? '');
          categorySelect.appendChild(opt);
        });
      } catch (err) {
        console.error("Error loading categories:", err);
      }
    }

    // Load initial skills
    window.addEventListener('DOMContentLoaded', async () => {
      await loadBrowseCategories();
      // Check for URL parameters after categories are populated
      checkUrlParameters();
      // Then apply filters (which will use the parameters from URL if set)
      applyBrowseFilters();
    });
  </script>
@endpush
