@extends('layouts.app')

@section('title', 'Platform Wallet – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    .table-wrapper { overflow: auto; }
    .pill {
      display:inline-flex;
      padding:0.2rem 0.6rem;
      border-radius:999px;
      font-size:0.75rem;
      border:1px solid rgba(148,163,184,0.25);
      background:rgba(15,23,42,0.55);
      color:#e5e7eb;
    }
  </style>
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

  <main class="main-content">
    <header class="topbar glass">
      <h2>Platform Wallet</h2>
      <div class="topbar-actions">
        <a href="{{ route('admin.cashout') }}" class="btn-secondary">Admin Cashout</a>
      </div>
    </header>

    <section class="stats-grid">
      <div class="stat-card glass">
        <h3>Platform Earnings</h3>
        <p class="stat-value" id="platformEarnings">—</p>
        <p class="stat-label">Admin wallet balance</p>
      </div>
    </section>

    <section class="dash-card glass">
      <div class="dash-card-header">
        <h3>Recent platform transactions</h3>
        <p class="muted" id="platformTxMeta">Loading…</p>
      </div>
      <div class="table-wrapper">
        <table class="request-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="platformTxBody">
            <tr><td colspan="4">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script>
    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    const token = localStorage.getItem("token");
    if (token) apiClient.setToken(token);

    async function ensureAdmin() {
      const u = await apiClient.getUser();
      if (!u?.is_admin) {
        window.location.href = "{{ route('dashboard') }}";
        return null;
      }
      return u;
    }

    async function loadPlatformWallet() {
      const u = await ensureAdmin();
      if (!u) return;

      try {
        const tx = await apiClient.getUserTransactions();
        const balance = tx?.balance ?? 0;
        document.getElementById('platformEarnings').textContent = balance;

        const list = Array.isArray(tx?.transactions) ? tx.transactions : [];
        const meta = document.getElementById('platformTxMeta');
        const body = document.getElementById('platformTxBody');

        if (meta) meta.textContent = `${list.length} transactions`;

        const prettyType = (t) => ({
          credit_purchase: 'Deposit',
          skill_payment: 'Purchase',
          skill_earning: 'Earning',
          cashout: 'Cashout',
          refund: 'Refund',
        }[t] || t || '—');

        const pill = (t) => `<span class="pill">${t ?? '—'}</span>`;

        if (!list.length) {
          body.innerHTML = `<tr><td colspan="4" class="muted">No transactions yet.</td></tr>`;
          return;
        }

        body.innerHTML = list.slice(0, 10).map(t => `
          <tr>
            <td>${t.created_at ? new Date(t.created_at).toLocaleString() : '—'}</td>
            <td>${pill(prettyType(t.type))}</td>
            <td>${t.amount ?? 0}</td>
            <td>${pill(t.status)}</td>
          </tr>
        `).join('');
      } catch (e) {
        console.error(e);
        document.getElementById('platformEarnings').textContent = u.credits ?? 0;
        const meta = document.getElementById('platformTxMeta');
        const body = document.getElementById('platformTxBody');
        if (meta) meta.textContent = 'Failed to load transactions.';
        if (body) body.innerHTML = `<tr><td colspan="4" class="muted">Error loading transactions.</td></tr>`;
      }
    }

    window.addEventListener('DOMContentLoaded', loadPlatformWallet);
  </script>
@endpush

