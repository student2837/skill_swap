@extends('layouts.app')

@section('title', 'AI Quiz – Start')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  <style>
    .start-card {
      max-width: 720px;
      margin: 2rem auto 0;
      padding: 2rem;
    }

    main.section {
      min-height: 100vh;
      display: flex;
      align-items: center;
    }

    main.section > .container {
      width: 100%;
    }

    .start-actions {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 2rem;
      flex-wrap: wrap;
    }

    .page-actions {
      position: absolute;
      top: 2rem;
      left: 2rem;
    }

    .dashboard-btn {
      background: linear-gradient(120deg, rgba(79, 70, 229, 0.18), rgba(34, 211, 238, 0.18));
      border: 1px solid rgba(96, 165, 250, 0.45);
      color: #e2e8f0;
      padding: 0.7rem 1.35rem;
      border-radius: 999px;
      font-weight: 600;
      letter-spacing: 0.02em;
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.45);
      transition: all 0.2s ease;
      text-decoration: none;
    }

    .dashboard-btn:hover {
      border-color: rgba(96, 165, 250, 0.85);
      background: linear-gradient(120deg, rgba(79, 70, 229, 0.35), rgba(34, 211, 238, 0.3));
      color: #ffffff;
      transform: translateY(-1px);
    }

    .start-warning {
      border-left: 4px solid #f59e0b;
      background: rgba(245, 158, 11, 0.08);
      padding: 1rem 1.25rem;
      border-radius: 12px;
      margin-top: 1.5rem;
      color: var(--text-main);
    }

    .loader {
      display: none;
      margin: 2rem auto 0;
      width: 56px;
      height: 56px;
      border: 4px solid rgba(148, 163, 184, 0.25);
      border-top-color: var(--accent);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    .loader-text {
      display: none;
      text-align: center;
      margin-top: 1rem;
      color: var(--text-muted);
    }

    .site-header {
      display: none;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>
@endpush

@section('content')
  <div class="page-bg"></div>

  <header class="site-header glass">
    <div class="container nav-container">
      <a href="{{ route('index') }}" class="logo-wrap">
        <img src="{{ asset('assets/logo.png') }}" alt="SkillSwap Logo" class="logo-img" />
      </a>
      <nav class="nav-links">
        <a href="{{ route('browse') }}">Browse Skills</a>
      </nav>
    </div>
  </header>

  <main class="section">
    <div class="container">
      <div class="page-actions">
        <a href="{{ route('dashboard') }}" class="dashboard-btn">Back to Dashboard</a>
      </div>
      <div class="glass start-card">
        <h1>Start Quiz</h1>
        <p>
          You are about to start your quiz. Once you confirm, you cannot go back until you finish the quiz.
          The questions will load automatically.
        </p>

        <div class="start-warning">
          <strong>Important:</strong>
          Do not refresh or close the page while the quiz is loading.
        </div>

        <div class="start-actions">
          <button id="startQuizBtn" class="btn btn-primary">Start Quiz</button>
          <a href="{{ route('requests') }}" class="btn btn-ghost">Cancel</a>
        </div>

        <div class="loader" id="quizLoader"></div>
        <div class="loader-text" id="loaderText">Generating your quiz, please wait...</div>
      </div>
    </div>
  </main>

  @push('scripts')
    <script>
      const token = localStorage.getItem("token");
      if (!token) {
        window.location.href = "{{ route('login') }}";
      }

      const requestId = {{ $requestId }};
      const startBtn = document.getElementById('startQuizBtn');
      const loader = document.getElementById('quizLoader');
      const loaderText = document.getElementById('loaderText');

      function lockBackNavigation() {
        history.pushState(null, document.title, window.location.href);
        window.addEventListener('popstate', () => {
          history.pushState(null, document.title, window.location.href);
          alert("You cannot go back after starting the quiz. Please finish the quiz.");
        });
      }

      async function startQuiz() {
        startBtn.disabled = true;
        loader.style.display = 'block';
        loaderText.style.display = 'block';
        lockBackNavigation();

        try {
          const response = await fetch(`{{ url('/api/quiz/access-request') }}/${requestId}`, {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'Authorization': `Bearer ${token}`
            }
          });

          const data = await response.json();
          if (!response.ok) {
            throw new Error(data.error || data.message || 'Unable to start quiz.');
          }

          if (data.redirect_url) {
            // Replace current history entry so back won't return to start page.
            window.location.replace(data.redirect_url);
            return;
          }

          throw new Error('No redirect URL received.');
        } catch (err) {
          alert(err.message || 'Unable to start quiz. Please try again.');
          window.location.href = "{{ route('requests') }}";
        }
      }

      startBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const confirmStart = confirm(
          "Once you start the quiz, you cannot go back until you finish it. Continue?"
        );
        if (!confirmStart) {
          return;
        }
        startQuiz();
      });
    </script>
  @endpush
@endsection
