@extends('layouts.app')

@section('title', 'Payment Status – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    .status-card { max-width: 720px; margin: 0 auto; }
    .status-pill {
      display:inline-flex;
      padding:0.25rem 0.75rem;
      border-radius:999px;
      font-weight:600;
      border:1px solid rgba(148,163,184,0.25);
      background:rgba(15,23,42,0.65);
      color:#e5e7eb;
    }
    .ok { border-color: rgba(34,197,94,0.6); color:#86efac; }
    .pending { border-color: rgba(250,204,21,0.6); color:#fde68a; }
    .fail { border-color: rgba(239,68,68,0.6); color:#fecaca; }
  </style>
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

  <main class="main-content">
    <header class="topbar glass">
      <h2>Payment Status</h2>
      <div class="topbar-actions">
        <a href="{{ route('credits') }}" class="btn-secondary">Back to Credits</a>
        <a href="{{ route('transactions') }}" class="btn-primary">Transactions</a>
      </div>
    </header>

    <section class="dash-card glass status-card">
      <div class="dash-card-header">
        <h3>Deposit confirmation</h3>
        <p class="muted">Status is based on backend confirmation (transaction status).</p>
      </div>

      <div id="statusLine" class="muted">Loading…</div>
      <div id="detailsLine" class="muted" style="margin-top:0.5rem;"></div>
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

    function pill(text, cls) {
      return `<span class="status-pill ${cls || ''}">${text}</span>`;
    }

    function getReference() {
      const params = new URLSearchParams(window.location.search);
      // PayPal returns "token" query param as order_id
      const paypalToken = params.get('token') || '';
      if (paypalToken) return `paypal_order_${paypalToken}`;

      const fromUrl = params.get('reference') || params.get('ref') || '';
      if (fromUrl) return fromUrl;

      return localStorage.getItem('last_whish_reference')
        || localStorage.getItem('last_paypal_reference')
        || '';
    }

    async function loadStatus() {
      const statusLine = document.getElementById('statusLine');
      const detailsLine = document.getElementById('detailsLine');
      const reference = getReference();

      if (!reference) {
        statusLine.innerHTML = pill('Payment pending', 'pending') + ' (no reference found)';
        detailsLine.textContent = 'If you just paid, please wait for confirmation, then check Transactions.';
        return;
      }

      try {
        const res = await apiClient.getUserTransactions();
        const txs = Array.isArray(res?.transactions) ? res.transactions : [];
        const tx = txs.find(t => t.reference_id === reference) || null;

        if (!tx) {
          statusLine.innerHTML = pill('Payment pending', 'pending');
          detailsLine.textContent = `Reference: ${reference}. Waiting for backend confirmation.`;
          return;
        }

        if (tx.status === 'completed') {
          statusLine.innerHTML = pill('Payment successful — credits added', 'ok');
        } else if (tx.status === 'failed') {
          statusLine.innerHTML = pill('Payment failed', 'fail');
        } else {
          statusLine.innerHTML = pill('Payment pending', 'pending');
        }

        detailsLine.textContent = `Reference: ${reference} • Amount: ${tx.amount} credits • Status: ${tx.status}`;
      } catch (e) {
        console.error(e);
        statusLine.innerHTML = pill('Payment pending', 'pending');
        detailsLine.textContent = `Reference: ${reference}. Could not load status from backend.`;
      }
    }

    window.addEventListener('DOMContentLoaded', loadStatus);
  </script>
@endpush

