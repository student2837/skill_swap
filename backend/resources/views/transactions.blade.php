@extends('layouts.app')

@section('title', 'Transactions – SkillSwap')

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
      <h2>Transactions</h2>
      <div class="topbar-actions">
        <a href="{{ route('cashout') }}" class="btn-secondary">Cashout</a>
        <a href="{{ route('credits') }}" class="btn-primary">Buy Credits</a>
      </div>
    </header>

    <section class="dash-card glass">
      <div class="dash-card-header">
        <h3>History</h3>
        <p class="muted" id="txMeta">Loading…</p>
      </div>

      <div class="table-wrapper">
        <table class="request-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Amount</th>
              <th>Fee</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody id="txBody"></tbody>
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

    function prettyType(t) {
      const map = {
        credit_purchase: 'Deposit',
        skill_payment: 'Purchase',
        skill_earning: 'Earning',
        cashout: 'Cashout',
        refund: 'Refund',
      };
      return map[t] || t || '—';
    }

    function pill(text) {
      return `<span class="pill">${text}</span>`;
    }

    function row(tx) {
      const type = prettyType(tx.type);
      const amount = tx.amount ?? '—';
      const fee = tx.fee ?? 0;
      const status = tx.status ?? '—';
      const date = tx.created_at ? new Date(tx.created_at).toLocaleString() : '—';
      return `
        <tr>
          <td>${pill(type)}</td>
          <td>${amount}</td>
          <td>${fee}</td>
          <td>${pill(status)}</td>
          <td>${date}</td>
        </tr>
      `;
    }

    async function loadTx() {
      const meta = document.getElementById('txMeta');
      const body = document.getElementById('txBody');
      try {
        const res = await apiClient.getUserTransactions();
        const txs = res?.transactions || [];
        meta.textContent = `Balance: ${res?.balance ?? 0} • Pending cashout: ${res?.pending_cashout ?? 0}`;
        body.innerHTML = txs.length ? txs.map(row).join('') : `<tr><td colspan="5" class="muted">No transactions yet.</td></tr>`;
      } catch (e) {
        console.error(e);
        meta.textContent = 'Failed to load transactions.';
        body.innerHTML = `<tr><td colspan="5" class="muted">Error loading transactions.</td></tr>`;
      }
    }

    window.addEventListener('DOMContentLoaded', loadTx);
  </script>
@endpush

