@extends('layouts.app')

@section('title', 'AI Quiz – Exam')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  <style>
    .exam-container {
      max-width: 900px;
      margin: 0 auto;
    }

    .exam-header {
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.82));
      backdrop-filter: blur(18px);
      border: 1px solid rgba(148, 163, 184, 0.3);
      border-radius: var(--radius-lg);
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.65);
    }

    .exam-header h1 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: var(--text-main);
    }

    .exam-header-meta {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-top: 0.5rem;
    }

    .exam-header-meta strong {
      color: var(--text-main);
    }

    .exam-form {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .question-card {
      background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.82));
      backdrop-filter: blur(18px);
      border: 1px solid rgba(148, 163, 184, 0.3);
      border-radius: var(--radius-lg);
      padding: 2rem;
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.65);
      transition: transform 0.2s ease, border-color 0.2s ease;
    }

    .question-card:hover {
      transform: translateY(-2px);
      border-color: rgba(96, 165, 250, 0.5);
    }

    .question-header {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .question-number {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 2.5rem;
      height: 2.5rem;
      background: linear-gradient(120deg, var(--primary), var(--accent));
      color: #fff;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      flex-shrink: 0;
    }

    .question-text {
      flex: 1;
      font-size: 1.1rem;
      line-height: 1.6;
      color: var(--text-main);
      font-weight: 500;
    }

    .choices-container {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      margin-left: 3.5rem;
    }

    .choice-item {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      padding: 1rem;
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .choice-item:hover {
      background: rgba(96, 165, 250, 0.15);
      border-color: rgba(96, 165, 250, 0.4);
      transform: translateX(4px);
    }

    .choice-item input[type="radio"] {
      margin-top: 0.2rem;
      width: 1.2rem;
      height: 1.2rem;
      cursor: pointer;
      accent-color: var(--primary);
      flex-shrink: 0;
    }

    .choice-item input[type="radio"]:checked + .choice-content {
      color: var(--accent);
    }

    .choice-content {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      flex: 1;
    }

    .choice-key {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 2rem;
      height: 2rem;
      background: rgba(96, 165, 250, 0.2);
      border: 1px solid rgba(96, 165, 250, 0.4);
      border-radius: 6px;
      font-weight: 600;
      color: #93c5fd;
      font-size: 0.9rem;
      flex-shrink: 0;
    }

    .choice-item input[type="radio"]:checked ~ .choice-content .choice-key {
      background: linear-gradient(120deg, var(--primary), var(--accent));
      border-color: var(--accent);
      color: #fff;
    }

    .choice-label {
      flex: 1;
      color: var(--text-main);
      line-height: 1.5;
      font-size: 0.95rem;
    }

    .choice-item input[type="radio"]:checked ~ .choice-content .choice-label {
      color: var(--text-main);
      font-weight: 500;
    }

    .form-actions {
      margin-top: 2rem;
      display: flex;
      justify-content: center;
      padding-top: 2rem;
      border-top: 1px solid rgba(148, 163, 184, 0.2);
    }

    .submit-btn {
      padding: 1rem 2.5rem;
      font-size: 1.1rem;
      font-weight: 600;
    }

    .alert {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.4);
      border-radius: var(--radius-lg);
      padding: 1rem 1.5rem;
      margin-bottom: 1.5rem;
      color: #fecaca;
    }

    .alert ul {
      margin-top: 0.5rem;
      padding-left: 1.5rem;
    }

    .alert li {
      margin-bottom: 0.25rem;
    }

    .site-header {
      display: none;
    }

    @media (max-width: 768px) {
      .choices-container {
        margin-left: 0;
      }

      .question-header {
        flex-direction: column;
        gap: 0.75rem;
      }

      .exam-header-meta {
        flex-direction: column;
        gap: 0.5rem;
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
    <div class="container exam-container">
      <div class="exam-header">
        <h1>{{ $exam['course_name'] ?? 'Exam' }}</h1>
        <div class="exam-header-meta">
          <span>Teacher: <strong>{{ $exam['teacher_name'] ?? 'N/A' }}</strong></span>
          <span>Student: <strong>{{ $exam['student_name'] ?? 'N/A' }}</strong></span>
          <span>Passing Score: <strong>{{ $exam['passing_score'] ?? '-' }}%</strong></span>
        </div>
      </div>

      @if ($errors->any())
        <div class="alert" role="alert">
          <strong>Error:</strong>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('quiz.grade') }}" class="exam-form">
        @csrf

        @php
          $questions = $exam['questions'] ?? [];
        @endphp

        @if (empty($questions))
          <div class="alert" role="alert">
            <p><strong>No questions found.</strong></p>
            <p>Please go back and regenerate the exam.</p>
            <a href="{{ route('quiz.setup') }}" class="btn btn-sm btn-primary" style="margin-top: 1rem; display: inline-block;">Back to Setup</a>
          </div>
        @endif

        @php
          $questionIndex = 0;
        @endphp
        @forelse ($questions as $index => $question)
          @php
            $questionIndex++;
            $questionNum = $questionIndex;
            $qText = $question['question'] ?? $question['text'] ?? 'Question ' . $questionNum;
            $choices = $question['choices'] ?? $question['options'] ?? [];
            $questionId = $question['id'] ?? (string) $index;
          @endphp

          <div class="question-card">
            <div class="question-header">
              <span class="question-number">Q{{ $questionNum }}</span>
              <div class="question-text">{{ $qText }}</div>
            </div>

            <div class="choices-container">
              @forelse ($choices as $key => $label)
                <label class="choice-item">
                  <input
                    type="radio"
                    name="answers[{{ $questionId }}]"
                    value="{{ $key }}"
                    @checked(old("answers.$questionId") === $key)
                    required
                  />
                  <div class="choice-content">
                    <span class="choice-key">{{ $key }}</span>
                    <span class="choice-label">{{ $label }}</span>
                  </div>
                </label>
              @empty
                <p style="color: var(--text-muted); margin-left: 3.5rem;">No choices provided for this question.</p>
              @endforelse
            </div>
          </div>
        @empty
          <div class="alert">
            <p>No questions found for this exam.</p>
          </div>
        @endforelse

        @if (!empty($questions))
          <div class="form-actions">
            <button type="submit" class="btn btn-primary submit-btn">
              Submit Answers for Grading
            </button>
          </div>
        @endif
      </form>
    </div>
  </main>
  @push('scripts')
    <script>
      // Mark quiz as in-progress for back-navigation guards.
      try {
        sessionStorage.setItem('quiz_in_progress', '1');
      } catch (e) {}

      // If this page was restored from back/forward cache, force reload
      // so server-side guards can redirect to results.
      window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
          window.location.reload();
        }
      });

      // Replace current entry, then lock back navigation.
      history.replaceState(null, document.title, window.location.href);
      history.pushState(null, document.title, window.location.href);
      window.addEventListener('popstate', () => {
        history.replaceState(null, document.title, window.location.href);
        history.pushState(null, document.title, window.location.href);
        alert("You cannot go back until you finish the quiz.");
      });
    </script>
  @endpush
@endsection

