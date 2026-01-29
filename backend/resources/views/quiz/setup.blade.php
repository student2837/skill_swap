@extends('layouts.app')

@section('title', 'AI Quiz – Setup')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
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
      <div class="section-header">
        <h1>Set up an AI‑generated exam</h1>
        <p>Describe your course and learning outcomes. The AI service will generate multiple‑choice questions automatically.</p>
      </div>

      @if (session('status'))
        <div class="alert alert-info" role="status">
          {{ session('status') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger" role="alert">
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('quiz.generate') }}" class="glass card-form">
        @csrf

        <div class="form-grid">
          <div class="form-group">
            <label for="course_name">Course name</label>
            <input
              type="text"
              id="course_name"
              name="course_name"
              value="{{ old('course_name') }}"
              required
            />
          </div>

          <div class="form-group">
            <label for="teacher_name">Teacher name</label>
            <input
              type="text"
              id="teacher_name"
              name="teacher_name"
              value="{{ old('teacher_name') }}"
              required
            />
          </div>

          <div class="form-group">
            <label for="student_name">Student name</label>
            <input
              type="text"
              id="student_name"
              name="student_name"
              value="{{ old('student_name') }}"
              required
            />
          </div>

          <div class="form-group">
            <label for="passing_score">Passing score (%)</label>
            <input
              type="number"
              id="passing_score"
              name="passing_score"
              min="1"
              max="100"
              value="{{ old('passing_score', 70) }}"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label for="learning_outcomes">
            Learning outcomes
            <span class="field-hint">(one per line)</span>
          </label>
          <textarea
            id="learning_outcomes"
            name="learning_outcomes"
            rows="6"
            required
          >{{ old('learning_outcomes') }}</textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            Generate exam with AI
          </button>
        </div>
      </form>
    </div>
  </main>
@endsection

