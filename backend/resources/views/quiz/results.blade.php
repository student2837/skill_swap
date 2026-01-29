@extends('layouts.app')

@section('title', 'AI Quiz – Results')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  <style>
    .certificate-wrapper {
      margin-top: 3rem;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .certificate-container {
      width: 100%;
      max-width: 900px;
      padding: 0;
      position: relative;
      overflow: hidden;
      background: #0f172a;
    }

    .certificate-border {
      border: 3px solid;
      border-image: linear-gradient(135deg, var(--primary), var(--accent), var(--primary-soft)) 1;
      border-radius: var(--radius-xl);
      padding: 3rem;
      background: linear-gradient(135deg, rgba(15, 23, 42, 1), rgba(15, 23, 42, 0.98));
      position: relative;
    }

    .certificate-content {
      text-align: center;
      position: relative;
      z-index: 1;
    }

    .certificate-header {
      margin-bottom: 2rem;
    }

    .certificate-logo {
      margin-bottom: 1.5rem;
    }

    .cert-logo-img {
      height: 80px;
      width: auto;
      filter: brightness(1.1);
    }

    .certificate-title {
      font-size: 2.5rem;
      font-weight: 700;
      background: linear-gradient(120deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }

    .certificate-subtitle {
      font-size: 1.1rem;
      color: var(--text-muted);
      font-style: italic;
      margin-top: 0.5rem;
    }

    .certificate-student-name {
      font-size: 2.2rem;
      font-weight: 700;
      color: var(--accent);
      margin: 2rem 0;
      padding: 1rem 0;
      border-top: 2px solid rgba(34, 211, 238, 0.3);
      border-bottom: 2px solid rgba(34, 211, 238, 0.3);
      letter-spacing: 0.05em;
    }

    .certificate-body {
      margin: 2rem 0;
      line-height: 2;
    }

    .certificate-text {
      font-size: 1.1rem;
      color: var(--text-main);
      margin: 0.8rem 0;
    }

    .certificate-course-name {
      font-size: 1.8rem;
      font-weight: 600;
      color: var(--primary-soft);
      margin: 1.5rem 0;
      padding: 1rem;
      background: rgba(79, 70, 229, 0.1);
      border-left: 4px solid var(--primary);
      border-radius: 8px;
      letter-spacing: 0.05em;
    }

    .certificate-teacher-name {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--accent);
      margin: 1rem 0;
    }

    .certificate-footer {
      display: flex;
      justify-content: space-around;
      margin: 2.5rem 0;
      padding: 1.5rem 0;
      border-top: 2px solid rgba(148, 163, 184, 0.2);
      border-bottom: 2px solid rgba(148, 163, 184, 0.2);
    }

    .certificate-score,
    .certificate-date {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .score-label,
    .date-label {
      font-size: 0.9rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .score-value {
      font-size: 1.8rem;
      font-weight: 700;
      background: linear-gradient(120deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .date-value {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--text-main);
    }

    .certificate-number {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(148, 163, 184, 0.2);
      font-family: 'Courier New', monospace;
      letter-spacing: 0.1em;
    }

    .certificate-decoration {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      pointer-events: none;
      z-index: 0;
    }

    .decoration-left,
    .decoration-right {
      position: absolute;
      width: 120px;
      height: 120px;
      border: 2px solid;
      border-image: linear-gradient(135deg, var(--primary), var(--accent)) 1;
      opacity: 0.2;
    }

    .decoration-left {
      top: 20px;
      left: 20px;
      border-radius: 50%;
    }

    .decoration-right {
      bottom: 20px;
      right: 20px;
      border-radius: 50%;
    }

    .certificate-actions {
      margin-top: 2rem;
      display: flex;
      gap: 1rem;
      justify-content: center;
    }

    @media print {
      @page {
        size: landscape;
        margin: 0.5in;
      }

      html, body {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        background: #0f172a !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
        overflow: hidden;
      }

      .certificate-wrapper {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        page-break-inside: avoid;
        break-inside: avoid;
      }

      .certificate-actions,
      .site-header,
      .section-header,
      .results-grid {
        display: none;
      }

      .certificate-container {
        max-width: 100%;
        width: 100%;
        max-height: 100%;
        box-shadow: none;
        background: #0f172a !important;
        page-break-inside: avoid;
        break-inside: avoid;
        page-break-after: avoid;
        break-after: avoid;
        page-break-before: avoid;
        break-before: avoid;
      }

      .certificate-border {
        border: 3px solid;
        border-image: linear-gradient(135deg, #4f46e5, #22d3ee, #6366f1) 1;
        background: linear-gradient(135deg, rgba(15, 23, 42, 1), rgba(15, 23, 42, 0.98)) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color-adjust: exact;
        page-break-inside: avoid;
        break-inside: avoid;
      }

      .certificate-content {
        page-break-inside: avoid;
        break-inside: avoid;
      }

      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
    }

    /* Results Grid - Professional Layout */
    .results-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2rem;
    }

    .results-summary,
    .results-details {
      padding: 1.75rem;
      border-radius: var(--radius-lg);
    }

    .results-summary h2,
    .results-details h2 {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text-main);
      margin: 0 0 1.5rem 0;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid rgba(148, 163, 184, 0.15);
      letter-spacing: 0.02em;
    }

    /* Overall Performance Section */
    .results-score {
      text-align: center;
      margin: 1.25rem 0;
    }

    .score-main {
      font-size: 3rem;
      font-weight: 700;
      line-height: 1.1;
      background: linear-gradient(120deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      display: block;
      margin-bottom: 0.75rem;
    }


    .results-meta {
      margin-top: 1rem;
      font-size: 0.875rem;
      color: var(--text-muted);
      text-align: center;
    }

    /* Student & Teacher Meta Information */
    .exam-meta-info {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      flex-wrap: wrap;
    }

    .meta-item {
      display: flex;
      flex-direction: column;
      gap: 0.375rem;
    }

    .meta-label {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-main);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      opacity: 0.85;
    }

    .meta-value {
      font-size: 1.65rem;
      font-weight: 700;
      color: var(--text-main);
      letter-spacing: -0.01em;
      line-height: 1.3;
    }

    .meta-divider {
      color: var(--text-muted);
      opacity: 0.3;
      font-size: 1rem;
      font-weight: 300;
    }

    /* Overall Performance Section */
    .performance-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
    }

    .status-badge {
      font-size: 1.1rem;
      font-weight: 600;
      padding: 0.625rem 1.5rem;
      border-radius: 999px;
      letter-spacing: 0.03em;
      text-align: center;
      display: inline-block;
      min-width: 120px;
    }

    /* Detailed Report Section */
    .report-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 1.25rem;
      margin-top: 0.75rem;
    }

    .report-stat {
      display: flex;
      flex-direction: column;
      gap: 0.625rem;
      padding: 1.5rem 1.25rem;
      background: rgba(15, 23, 42, 0.3);
      border: 1px solid rgba(148, 163, 184, 0.15);
      border-radius: var(--radius-lg);
      transition: all 0.2s ease;
      min-height: 100px;
    }

    .report-stat:hover {
      border-color: rgba(96, 165, 250, 0.3);
      background: rgba(15, 23, 42, 0.4);
    }

    .stat-label {
      font-size: 0.7rem;
      font-weight: 500;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      line-height: 1.4;
      opacity: 0.65;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-main);
      line-height: 1.2;
      margin-top: 0.375rem;
    }

    .report-stat:first-child .stat-value {
      background: linear-gradient(120deg, var(--primary-soft), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .stat-correct {
      color: var(--accent);
    }

    .stat-incorrect {
      color: #f87171;
    }

    /* Certificate Section Header */
    .certificate-section-header {
      margin: 3rem 0 1.5rem 0;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid rgba(148, 163, 184, 0.15);
    }

    .certificate-section-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--text-main);
      margin: 0;
      letter-spacing: 0.02em;
    }

    /* Status Badge Styles */
    .status-pass {
      background: rgba(34, 211, 238, 0.12);
      color: var(--accent);
      border: 1px solid rgba(34, 211, 238, 0.25);
    }

    .status-fail {
      background: rgba(239, 68, 68, 0.12);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.25);
    }


    @media (max-width: 768px) {
      .certificate-title {
        font-size: 1.8rem;
      }

      .certificate-student-name {
        font-size: 1.6rem;
      }

      .certificate-course-name {
        font-size: 1.4rem;
      }

      .certificate-footer {
        flex-direction: column;
        gap: 1.5rem;
      }

      .certificate-border {
        padding: 2rem 1.5rem;
      }

      .results-grid {
        grid-template-columns: 1fr;
      }

      .score-main {
        font-size: 2.25rem;
      }

      .report-summary {
        grid-template-columns: 1fr;
        gap: 0.75rem;
      }

      .report-stat {
        padding: 1.25rem 1rem;
        min-height: auto;
      }

      .stat-value {
        font-size: 1.75rem;
      }

      .exam-meta-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
      }

      .meta-divider {
        display: none;
      }

      .status-badge {
        font-size: 1rem;
        padding: 0.5rem 1.25rem;
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
      <div class="section-header">
        <h1 style="font-size: 2.5rem; font-weight: 600; margin-bottom: 1.5rem; color: var(--text-main);">
          Exam Results – {{ $exam['course_name'] ?? 'Course' }}
        </h1>
        <div class="exam-meta-info">
          <div class="meta-item">
            <span class="meta-label">Student</span>
            <span class="meta-value">{{ $exam['student_name'] ?? 'N/A' }}</span>
          </div>
          <span class="meta-divider">·</span>
          <div class="meta-item">
            <span class="meta-label">Teacher</span>
            <span class="meta-value">{{ $exam['teacher_name'] ?? 'N/A' }}</span>
          </div>
        </div>
      </div>

      <div class="results-grid">
        <section class="glass results-summary">
          @php
            $score = $results['score'] ?? null;
            $percentage = $results['percentage'] ?? null;
            $passed = $results['passed'] ?? ($results['pass'] ?? null);
            $statusText = $passed ? 'Passed' : 'Failed';
          @endphp

          <h2>Overall Performance</h2>
          <div class="performance-content">
            <p class="results-score">
              @if (! is_null($percentage))
                <span class="score-main">{{ number_format($percentage, 1) }}%</span>
              @elseif (! is_null($score))
                <span class="score-main">{{ $score }}</span>
              @else
                <span class="score-main">N/A</span>
              @endif
            </p>
            <div class="status-badge {{ $passed ? 'status-pass' : 'status-fail' }}">
              {{ $statusText }}
            </div>
            @if (isset($results['passing_score']))
              <p class="results-meta">
                Passing score: {{ $results['passing_score'] }}%
              </p>
            @elseif(isset($exam['passing_score']))
              <p class="results-meta">
                Passing score: {{ $exam['passing_score'] }}%
              </p>
            @endif
          </div>
        </section>

        <section class="glass results-details">
          <h2>Detailed Report</h2>

          @php
            $report = $results['grading_report'] ?? $results['report'] ?? $results['details'] ?? null;
            $totalQuestions = $report['total_questions'] ?? count($exam['questions'] ?? []);
            $correctAnswers = $report['correct_answers'] ?? null;
            $incorrectAnswers = $report['incorrect_answers'] ?? null;
            $questionResults = $report['question_results'] ?? null;
          @endphp

          @if (is_array($report))
            <div class="report-summary">
              <div class="report-stat">
                <span class="stat-label">Total Questions:</span>
                <span class="stat-value">{{ $totalQuestions }}</span>
              </div>
              @if ($correctAnswers !== null)
                <div class="report-stat">
                  <span class="stat-label">Correct Answers:</span>
                  <span class="stat-value stat-correct">{{ $correctAnswers }}</span>
                </div>
              @endif
              @if ($incorrectAnswers !== null)
                <div class="report-stat">
                  <span class="stat-label">Incorrect Answers:</span>
                  <span class="stat-value stat-incorrect">{{ $incorrectAnswers }}</span>
                </div>
              @endif
            </div>

          @elseif (is_string($report))
            <p>{{ $report }}</p>
          @else
            <p style="color: var(--text-muted);">No detailed report available.</p>
          @endif
        </section>
      </div>

      @php
        $certificate = $results['certificate'] ?? $results['certificate_text'] ?? null;
        $passed = $results['passed'] ?? ($results['pass'] ?? null);
        $percentage = $results['percentage'] ?? null;
        $completionDate = $results['completion_date'] ?? date('F j, Y');
      @endphp

      @if ($certificate && $passed)
        <div class="certificate-section-header">
          <h2 class="certificate-section-title">Certificate of Completion</h2>
        </div>
        <section class="certificate-wrapper">
          <div class="certificate-container glass">
            <div class="certificate-border">
              <div class="certificate-content">
                <!-- Certificate Header -->
                <div class="certificate-header">
                  <div class="certificate-logo">
                    <img src="{{ asset('assets/logo.png') }}" alt="SkillSwap" class="cert-logo-img" />
                  </div>
                  <h1 class="certificate-title">CERTIFICATE OF COMPLETION</h1>
                  <div class="certificate-subtitle">This is to certify that</div>
                </div>

                <!-- Student Name -->
                <div class="certificate-student-name">
                  {{ strtoupper($exam['student_name'] ?? 'Student Name') }}
                </div>

                <!-- Course Details -->
                <div class="certificate-body">
                  <p class="certificate-text">
                    has successfully completed the course
                  </p>
                  <p class="certificate-course-name">
                    {{ strtoupper($exam['course_name'] ?? 'Course Name') }}
                  </p>
                  <p class="certificate-text">
                    under the instruction of
                  </p>
                  <p class="certificate-teacher-name">
                    {{ $exam['teacher_name'] ?? 'Instructor Name' }}
                  </p>
                </div>

                <!-- Score and Date -->
                <div class="certificate-footer">
                  <div class="certificate-score">
                    <span class="score-label">Score Achieved:</span>
                    <span class="score-value">{{ number_format($percentage, 2) }}%</span>
                  </div>
                  <div class="certificate-date">
                    <span class="date-label">Date of Completion:</span>
                    <span class="date-value">{{ $completionDate }}</span>
                  </div>
                </div>

                <!-- Certificate Number (Optional) -->
                <div class="certificate-number">
                  Certificate ID: {{ strtoupper(substr(md5($exam['course_name'] . $exam['student_name'] . time()), 0, 12)) }}
                </div>

                <!-- Decorative Elements -->
                <div class="certificate-decoration">
                  <div class="decoration-left"></div>
                  <div class="decoration-right"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Download/Print Button -->
          <div class="certificate-actions">
            <button onclick="window.print()" class="btn btn-primary">
              Print Certificate
            </button>
            <button onclick="downloadCertificate()" class="btn btn-ghost">
              Download as PDF
            </button>
          </div>
        </section>
      @endif
    </div>
  </main>

  @push('scripts')
    <script>
      function downloadCertificate() {
        // Create a new window for printing/downloading
        const printWindow = window.open('', '_blank');
        const certificateContent = document.querySelector('.certificate-container').outerHTML;
        
        // Get all style sheets to preserve certificate styling
        let allStyles = '';
        Array.from(document.styleSheets).forEach(sheet => {
          try {
            Array.from(sheet.cssRules || []).forEach(rule => {
              allStyles += rule.cssText + '\n';
            });
          } catch (e) {
            // Cross-origin stylesheets may throw errors, skip them
          }
        });
        
        // Also include inline styles from the page
        const inlineStyles = document.querySelector('style')?.textContent || '';
        
        printWindow.document.write(`
          <!DOCTYPE html>
          <html>
            <head>
              <title>Certificate - {{ $exam['course_name'] ?? 'Course' }}</title>
              <style>
                @page {
                  size: landscape;
                  margin: 0.5in;
                }
                
                * {
                  -webkit-print-color-adjust: exact !important;
                  print-color-adjust: exact !important;
                  color-adjust: exact !important;
                  page-break-inside: avoid !important;
                  break-inside: avoid !important;
                }
                
                html, body {
                  width: 100%;
                  height: 100%;
                  margin: 0;
                  padding: 0;
                  background: #0f172a !important;
                  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                  color-adjust: exact;
                  overflow: hidden;
                }
                
                ${inlineStyles}
                
                .certificate-wrapper {
                  margin: 0;
                  padding: 0;
                  width: 100%;
                  height: 100%;
                  display: flex;
                  flex-direction: column;
                  align-items: center;
                  justify-content: center;
                  page-break-inside: avoid;
                  break-inside: avoid;
                }
                
                .certificate-container {
                  width: 100%;
                  max-width: 100%;
                  max-height: 100%;
                  padding: 0;
                  position: relative;
                  overflow: hidden;
                  background: #0f172a !important;
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                  color-adjust: exact;
                  page-break-inside: avoid;
                  break-inside: avoid;
                  page-break-after: avoid;
                  break-after: avoid;
                  page-break-before: avoid;
                  break-before: avoid;
                }
                
                .certificate-border {
                  border: 3px solid;
                  border-image: linear-gradient(135deg, #4f46e5, #22d3ee, #6366f1) 1;
                  border-radius: 22px;
                  padding: 3rem;
                  background: linear-gradient(135deg, rgba(15, 23, 42, 1), rgba(15, 23, 42, 0.98)) !important;
                  position: relative;
                  -webkit-print-color-adjust: exact;
                  print-color-adjust: exact;
                  color-adjust: exact;
                  page-break-inside: avoid;
                  break-inside: avoid;
                }
                
                .certificate-content {
                  text-align: center;
                  position: relative;
                  z-index: 1;
                  page-break-inside: avoid;
                  break-inside: avoid;
                }
                
                .certificate-title {
                  font-size: 2.5rem;
                  font-weight: 700;
                  background: linear-gradient(120deg, #4f46e5, #22d3ee);
                  -webkit-background-clip: text;
                  -webkit-text-fill-color: transparent;
                  background-clip: text;
                  margin-bottom: 0.5rem;
                  letter-spacing: 0.1em;
                  text-transform: uppercase;
                }
                
                .certificate-student-name {
                  font-size: 2.2rem;
                  font-weight: 700;
                  color: #22d3ee;
                  margin: 2rem 0;
                  padding: 1rem 0;
                  border-top: 2px solid rgba(34, 211, 238, 0.3);
                  border-bottom: 2px solid rgba(34, 211, 238, 0.3);
                  letter-spacing: 0.05em;
                }
                
                .certificate-course-name {
                  font-size: 1.8rem;
                  font-weight: 600;
                  color: #6366f1;
                  margin: 1.5rem 0;
                  padding: 1rem;
                  background: rgba(79, 70, 229, 0.1);
                  border-left: 4px solid #4f46e5;
                  border-radius: 8px;
                  letter-spacing: 0.05em;
                }
                
                .certificate-text {
                  font-size: 1.1rem;
                  color: #e5e7eb;
                  margin: 0.8rem 0;
                }
                
                .certificate-teacher-name {
                  font-size: 1.3rem;
                  font-weight: 600;
                  color: #22d3ee;
                  margin: 1rem 0;
                }
                
                .certificate-footer {
                  display: flex;
                  justify-content: space-around;
                  margin: 2.5rem 0;
                  padding: 1.5rem 0;
                  border-top: 2px solid rgba(148, 163, 184, 0.2);
                  border-bottom: 2px solid rgba(148, 163, 184, 0.2);
                }
                
                .score-value {
                  font-size: 1.8rem;
                  font-weight: 700;
                  background: linear-gradient(120deg, #4f46e5, #22d3ee);
                  -webkit-background-clip: text;
                  -webkit-text-fill-color: transparent;
                  background-clip: text;
                }
                
                .date-value {
                  font-size: 1.2rem;
                  font-weight: 600;
                  color: #e5e7eb;
                }
                
                .certificate-number {
                  font-size: 0.85rem;
                  color: #9ca3af;
                  margin-top: 2rem;
                  padding-top: 1rem;
                  border-top: 1px solid rgba(148, 163, 184, 0.2);
                  font-family: 'Courier New', monospace;
                  letter-spacing: 0.1em;
                }
                
                @media print {
                  @page {
                    size: landscape;
                    margin: 0.5in;
                  }
                  
                  html, body {
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                    background: #0f172a !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    color-adjust: exact;
                    overflow: hidden;
                  }
                  
                  .certificate-wrapper {
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                    page-break-inside: avoid;
                    break-inside: avoid;
                  }
                  
                  .certificate-container {
                    max-width: 100%;
                    max-height: 100%;
                    page-break-inside: avoid;
                    break-inside: avoid;
                    page-break-after: avoid;
                    break-after: avoid;
                    page-break-before: avoid;
                    break-before: avoid;
                  }
                  
                  .certificate-border {
                    background: linear-gradient(135deg, rgba(15, 23, 42, 1), rgba(15, 23, 42, 0.98)) !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    color-adjust: exact;
                    page-break-inside: avoid;
                    break-inside: avoid;
                  }
                  
                  .certificate-content {
                    page-break-inside: avoid;
                    break-inside: avoid;
                  }
                  
                  * {
                    page-break-inside: avoid !important;
                    break-inside: avoid !important;
                  }
                }
              </style>
            </head>
            <body>
              ${certificateContent}
            </body>
          </html>
        `);
        printWindow.document.close();
        printWindow.print();
      }
    </script>
  @endpush
@endsection

