@extends('layouts.app')

@section('title', 'Certificate – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
  <style>
    .certificate-page {
      max-width: 980px;
      margin: 0 auto;
    }

    .page-actions {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 1.5rem;
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

    .certificate-shell {
      min-height: 360px;
    }

    .loading-state {
      text-align: center;
      padding: 3rem 1rem;
      color: var(--text-muted);
    }

    .certificate-wrapper {
      margin-top: 1.5rem;
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
  </style>
@endpush

@section('content')
  <div class="page-bg"></div>

  <main class="section">
    <div class="container certificate-page">
      <div class="page-actions">
        <a href="{{ route('dashboard') }}" class="dashboard-btn">Back to Dashboard</a>
      </div>

      <div class="glass certificate-shell" id="certificateShell">
        <div class="loading-state">Loading certificate...</div>
      </div>
    </div>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script>
    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    const token = localStorage.getItem("token");
    if (token) {
      apiClient.setToken(token);
    } else {
      window.location.href = "{{ route('login') }}";
    }

    const certificateId = {{ $certificateId }};
    const shell = document.getElementById('certificateShell');

    function renderCertificate(cert) {
      shell.innerHTML = `
        <section class="certificate-wrapper">
          <div class="certificate-container glass">
            <div class="certificate-border">
              <div class="certificate-content">
                <div class="certificate-header">
                  <div class="certificate-logo">
                    <img src="{{ asset('assets/logo.png') }}" alt="SkillSwap" class="cert-logo-img" />
                  </div>
                  <h1 class="certificate-title">CERTIFICATE OF COMPLETION</h1>
                  <div class="certificate-subtitle">This is to certify that</div>
                </div>
                <div class="certificate-student-name">${(cert.student_name || 'Student').toUpperCase()}</div>
                <div class="certificate-body">
                  <p class="certificate-text">has successfully completed the course</p>
                  <p class="certificate-course-name">${(cert.course_name || 'Course').toUpperCase()}</p>
                  <p class="certificate-text">under the instruction of</p>
                  <p class="certificate-teacher-name">${cert.teacher_name || 'Instructor'}</p>
                </div>
                <div class="certificate-footer">
                  <div class="certificate-score">
                    <span class="score-label">Score Achieved:</span>
                    <span class="score-value">${cert.percentage != null ? Number(cert.percentage).toFixed(2) + '%' : 'N/A'}</span>
                  </div>
                  <div class="certificate-date">
                    <span class="date-label">Date of Completion:</span>
                    <span class="date-value">${cert.completion_date || ''}</span>
                  </div>
                </div>
                <div class="certificate-number">Certificate ID: ${cert.certificate_code || ''}</div>
              </div>
            </div>
          </div>
        </section>
      `;
    }

    async function loadCertificate() {
      try {
        const cert = await apiClient.getCertificate(certificateId);
        if (!cert) {
          throw new Error('Certificate not found.');
        }
        renderCertificate(cert);
      } catch (err) {
        shell.innerHTML = `<div class="loading-state">${err.message || 'Certificate not found.'}</div>`;
      }
    }

    loadCertificate();
  </script>
@endpush
