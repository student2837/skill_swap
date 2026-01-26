@extends('layouts.app')

@section('title', 'SkillSwap – Learn & Teach Skills')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
@endpush

@section('content')
  <div class="page-bg"></div>

  <!-- Header -->
  <header class="site-header glass">
    <div class="container nav-container">
      <a href="{{ route('index') }}" class="logo-wrap">
        <img src="{{ asset('assets/logo.png') }}" alt="SkillSwap Logo" class="logo-img" />
      </a>

      <nav class="nav-links">
        <a href="{{ route('browse') }}">Browse Skills</a>
        <a href="#how-it-works" onclick="event.preventDefault(); scrollToCreditSystem();">How it Works</a>
        <a href="#categories">Categories</a>
      </nav>

      <div class="nav-actions">
        <div class="search-wrapper">
          <input
            type="search"
            id="nav-search"
            name="search"
            placeholder="Search skills…"
            autocomplete="off"
            role="searchbox"
          />
          <button
            class="icon-button"
            id="nav-search-btn"
            onclick="handleNavSearch()"
          >
            🔍
          </button>
        </div>

        <a href="{{ route('login') }}" class="btn btn-sm btn-white">Log in</a>
        <a href="{{ route('register') }}" class="btn btn-sm btn-primary">
          Join Now
        </a>
      </div>
    </div>
  </header>

  <!-- Main -->
  <main>
    <section class="hero">
      <div class="container hero-grid">
        <div class="hero-text">
          <div class="pill-badge">
            <span class="pill-dot"></span>
            <span>Teach skills. Earn credits. Learn anything.</span>
          </div>

          <h1>
            Trade your <span class="gradient-text">knowledge</span>
            for new skills.
          </h1>

          <p class="hero-subtitle">
            Teach what you know, earn credits, and book 1-to-1 sessions with
            top-rated instructors around the world.
          </p>

          <div class="hero-actions">
            <a href="{{ route('register') }}" class="btn btn-primary">
              Become a Teacher
            </a>
            <a href="{{ route('browse') }}" class="btn btn-sm btn-white">
              Browse Skills
            </a>
          </div>

          <div class="hero-meta">
            <div>
              <span class="hero-meta-number">2.1k+</span>
              <span class="hero-meta-label">Active learners</span>
            </div>
            <div>
              <span class="hero-meta-number">980+</span>
              <span class="hero-meta-label">Skills available</span>
            </div>
          </div>
        </div>

        <div class="hero-panel glass">
          <div class="hero-panel-header">
            <span class="hero-tag">Live marketplace</span>
            <span class="hero-status">
              <span class="status-dot"></span>Sessions running now
            </span>
          </div>

          <ul class="hero-skill-list">
            <li class="hero-skill-item">
              <div>
                <p class="hero-skill-name">Python for Beginners</p>
                <p class="hero-skill-teacher">
                  Dr. Mubarak • 4 yrs teaching
                </p>
              </div>
              <div class="hero-skill-meta">
                <span class="chip chip-soft">5 credits</span>
                <span class="rating">★ 4.9</span>
              </div>
            </li>

            <li class="hero-skill-item">
              <div>
                <p class="hero-skill-name">UI/UX Design Sprint</p>
                <p class="hero-skill-teacher">
                  Jane Smith • 3 yrs teaching
                </p>
              </div>
              <div class="hero-skill-meta">
                <span class="chip chip-soft">6 credits</span>
                <span class="rating">★ 4.8</span>
              </div>
            </li>

            <li class="hero-skill-item">
              <div>
                <p class="hero-skill-name">Guitar Essentials</p>
                <p class="hero-skill-teacher">
                  Alex Johnson • 5 yrs teaching
                </p>
              </div>
              <div class="hero-skill-meta">
                <span class="chip chip-soft">4 credits</span>
                <span class="rating">★ 4.7</span>
              </div>
            </li>
          </ul>

          <div class="hero-panel-footer">
            <p>Teach once, learn forever.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section section-split" id="how-it-works">
      <div class="container section-split-grid">
        <div>
          <h2 id="credit-system-title">How the credit system works</h2>
          <p class="section-subtitle">
            Credits are the internal currency of SkillSwap. You can buy them, earn them by teaching, or cash them out as money.
          </p>
          <ol class="steps-list">
            <li>
              <span class="step-number">1</span>
              <div>
                <h3>Create your free account</h3>
                <p>Sign up as a learner, teacher, or both.</p>
              </div>
            </li>
            <li>
              <span class="step-number">2</span>
              <div>
                <h3>Teach a skill & earn credits</h3>
                <p>Offer any skill you're good at and get paid in credits.</p>
              </div>
            </li>
            <li>
              <span class="step-number">3</span>
              <div>
                <h3>Spend or cash out</h3>
                <p>Use credits to book skills or withdraw through secure payouts.</p>
              </div>
            </li>
          </ol>
        </div>
        <div class="highlights-grid">
          <article class="highlight-card glass">
            <h3>Verified reviews</h3>
            <p>Only students who completed a session can review.</p>
          </article>
          <article class="highlight-card glass">
            <h3>Escrow protection</h3>
            <p>Credits are held until the session is done.</p>
          </article>
          <article class="highlight-card glass">
            <h3>Flexible scheduling</h3>
            <p>Teachers choose hours, students pick times.</p>
          </article>
          <article class="highlight-card glass">
            <h3>Multi-role accounts</h3>
            <p>Be a student, teacher, or both with one profile.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section" id="categories">
      <div class="container">
        <div class="section-header">
          <h2>Explore by category</h2>
          <p>Find the right track for your next skill.</p>
        </div>
        <div class="categories-grid">
          <button class="category-card" onclick="window.location.href='{{ route('browse') }}?category=programming'">
            <span class="cat-icon">💻</span>
            <span class="cat-title">Programming</span>
            <span class="cat-sub">Python, Web, Data</span>
          </button>
          <button class="category-card" onclick="window.location.href='{{ route('browse') }}?category=design'">
            <span class="cat-icon">🎨</span>
            <span class="cat-title">Design</span>
            <span class="cat-sub">UI/UX, Graphic</span>
          </button>
          <button class="category-card" onclick="window.location.href='{{ route('browse') }}?category=music'">
            <span class="cat-icon">🎵</span>
            <span class="cat-title">Music</span>
            <span class="cat-sub">Guitar, Piano</span>
          </button>
          <button class="category-card" onclick="window.location.href='{{ route('browse') }}?category=languages'">
            <span class="cat-icon">🌍</span>
            <span class="cat-title">Languages</span>
            <span class="cat-sub">Arabic, English…</span>
          </button>
          <button class="category-card" onclick="window.location.href='{{ route('browse') }}?category=other'">
            <span class="cat-icon">📚</span>
            <span class="cat-title">Other</span>
            <span class="cat-sub">Various skills</span>
          </button>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div>
        <p class="footer-brand">SkillSwap</p>
        <p class="footer-text">
          A marketplace where students and teachers trade skills using credits.
        </p>
      </div>

      <div class="footer-links">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Contact</a>
      </div>

      <p class="footer-copy">
        © 2025 SkillSwap. All rights reserved.
      </p>
    </div>
  </footer>
@endsection

@push('scripts')
  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script>
    // Load statistics (user count and skills count)
    async function loadStatistics() {
      try {
        const API = "{{ url('/api') }}";
        const apiClient = new ApiClient(API);
        
        const stats = await apiClient.getStatistics();
        
        console.log('Statistics loaded:', stats); // Debug log
        
        if (stats && (stats.total_users !== undefined || stats.active_skills !== undefined)) {
          // Format numbers
          const formatNumber = (num) => {
            if (num >= 1000) {
              return (num / 1000).toFixed(1) + 'k+';
            }
            return num + '+';
          };
          
          // Update active learners (first hero-meta-number)
          const heroMetaNumbers = document.querySelectorAll('.hero-meta-number');
          console.log('Found hero-meta-number elements:', heroMetaNumbers.length); // Debug log
          
          if (heroMetaNumbers.length >= 2) {
            if (stats.total_users !== undefined) {
              heroMetaNumbers[0].textContent = formatNumber(stats.total_users);
              console.log('Updated active learners:', formatNumber(stats.total_users)); // Debug log
            }
            // Update skills available (second hero-meta-number)
            if (stats.active_skills !== undefined) {
              heroMetaNumbers[1].textContent = formatNumber(stats.active_skills);
              console.log('Updated skills available:', formatNumber(stats.active_skills)); // Debug log
            }
          }
        }
      } catch (err) {
        console.error('Error loading statistics:', err);
        // Keep default values if error
      }
    }

    // Load top skills for live marketplace
    async function loadTopSkills() {
      try {
        const API = "{{ url('/api') }}";
        const apiClient = new ApiClient(API);
        const token = localStorage.getItem("token");
        if (token) {
          apiClient.setToken(token);
        }

        // Fetch all active skills
        const skills = await apiClient.listAllSkills();

        const heroSkillList = document.querySelector('.hero-skill-list');
        if (!heroSkillList) return;

        // If no skills in database, keep the default placeholder data
        if (!skills || !skills.length) {
          return; // Keep the default HTML skills
        }

        // Sort skills by rating (highest first), then by students count (most first)
        const sortedSkills = skills
          .filter(skill => skill.rating_avg && skill.rating_avg > 0) // Only skills with ratings
          .sort((a, b) => {
            // First sort by rating (descending)
            if (b.rating_avg !== a.rating_avg) {
              return (b.rating_avg || 0) - (a.rating_avg || 0);
            }
            // If ratings are equal, sort by students count (descending)
            return (b.students_count || 0) - (a.students_count || 0);
          })
          .slice(0, 3); // Get top 3

        // If no skills with ratings, try to show any skills (without rating filter)
        let skillsToShow = sortedSkills;
        if (sortedSkills.length === 0) {
          // Show top 3 skills by students count or most recent
          skillsToShow = skills
            .sort((a, b) => (b.students_count || 0) - (a.students_count || 0))
            .slice(0, 3);
        }

        // If still no skills, keep default placeholder data
        if (skillsToShow.length === 0) {
          return; // Keep the default HTML skills
        }

        // Update the hero skill list with real data
        heroSkillList.innerHTML = skillsToShow.map(skill => {
          const teacherName = skill.user?.name || 'Unknown teacher';
          const studentsCount = skill.students_count || 0;
          const rating = skill.rating_avg ? skill.rating_avg.toFixed(1) : 'N/A';
          const price = skill.price || 0;
          
          return `
            <li class="hero-skill-item">
              <div>
                <p class="hero-skill-name">${skill.title || 'Untitled'}</p>
                <p class="hero-skill-teacher">
                  ${teacherName} • ${studentsCount} ${studentsCount === 1 ? 'student' : 'students'}
                </p>
              </div>
              <div class="hero-skill-meta">
                <span class="chip chip-soft">${price} credits</span>
                <span class="rating">★ ${rating}</span>
              </div>
            </li>
          `;
        }).join('');
      } catch (err) {
        console.error('Error loading top skills:', err);
        // If error, keep the default placeholder skills
      }
    }

    // Handle search from navigation
    function handleNavSearch() {
      const searchInput = document.getElementById('nav-search');
      const searchQuery = searchInput.value.trim();
      
      if (searchQuery) {
        // Redirect to browse with search parameter
        window.location.href = `{{ route('browse') }}?search=${encodeURIComponent(searchQuery)}`;
      }
    }

    // Smooth scroll to credit system section
    function scrollToCreditSystem() {
      const element = document.getElementById('credit-system-title');
      if (element) {
        // Get header height to account for sticky navigation
        const header = document.querySelector('.site-header');
        const headerHeight = header ? header.offsetHeight + 20 : 120; // Add extra padding
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerHeight;

        window.scrollTo({
          top: offsetPosition,
          behavior: 'smooth'
        });
      } else {
        // Fallback to section if title not found
        const section = document.getElementById('how-it-works');
        if (section) {
          const header = document.querySelector('.site-header');
          const headerHeight = header ? header.offsetHeight + 20 : 120;
          const sectionPosition = section.getBoundingClientRect().top;
          const offsetPosition = sectionPosition + window.pageYOffset - headerHeight;
          
          window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
          });
        }
      }
    }

    // Handle hash navigation on page load
    window.addEventListener('DOMContentLoaded', () => {
      // Check if URL has #how-it-works hash
      if (window.location.hash === '#how-it-works') {
        setTimeout(() => {
          scrollToCreditSystem();
        }, 100);
      }

      const searchInput = document.getElementById('nav-search');
      if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            handleNavSearch();
          }
        });
      }
      
      loadTopSkills();
      loadStatistics();
    });
  </script>
@endpush
