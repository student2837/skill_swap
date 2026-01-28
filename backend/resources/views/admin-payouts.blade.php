@extends('layouts.app')

@section('title', 'Payout Management – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    .table-wrapper { overflow: auto; }
    .action-row { display:flex; gap:0.5rem; }
    .btn-xs { 
      padding:0.35rem 0.7rem; 
      font-size:0.78rem; 
      border-radius:999px !important; 
    }
    
    .btn-primary.btn-xs,
    .btn-danger.btn-xs,
    .btn-success.btn-xs {
      border-radius:999px !important;
      padding:0.35rem 0.7rem;
    }
    
    button.btn-xs {
      border-radius:999px !important;
    }
    .pill { display:inline-flex; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.75rem; border:1px solid rgba(96,165,250,0.4); background:rgba(96,165,250,0.15); color:#93c5fd; }
    .link { color:#93c5fd; text-decoration:none; }
    .link:hover { text-decoration:underline; }
    .btn-xs[disabled] { opacity: 0.45; cursor: not-allowed; filter: grayscale(0.25); transform: none !important; }
  </style>
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

  <main class="main-content">
    <header class="topbar glass">
      <h2>Payout Management</h2>
      <div class="topbar-actions">
        <a href="{{ route('admin.wallet') }}" class="btn-secondary">Platform Wallet</a>
      </div>
    </header>

    <section class="dash-card glass">
      <div class="dash-card-header">
        <h3>All payouts</h3>
        <p class="muted" id="meta">Loading…</p>
      </div>

      <div class="table-wrapper">
        <table class="request-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Requested</th>
              <th>Fee</th>
              <th>Net</th>
              <th>Provider</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="rows"></tbody>
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

    function pill(t){ return `<span class="pill">${t ?? '—'}</span>`; }
    function num(v){ const n = Number(v); return Number.isFinite(n) ? n : 0; }
    
    function formatStatus(status) {
      const statusMap = {
        'pending': 'Pending',
        'approved': 'Approved',
        'processing': 'In Progress',
        'paid': 'Paid',
        'rejected': 'Rejected',
        'failed': 'Failed'
      };
      const normalized = String(status || 'pending').toLowerCase();
      return statusMap[normalized] || status;
    }

    async function ensureAdmin() {
      const u = await apiClient.getUser();
      if (!u?.is_admin) {
        window.location.href = "{{ route('dashboard') }}";
        return false;
      }
      return true;
    }

    function computeFeeFallback(p) {
      // UI display only: use requested gross if available, otherwise fallback.
      const fee = (p.fee ?? p.fee_amount);
      const net = (p.net ?? p.net_amount);
      const gross = num(p.gross_amount ?? p.amount);
      const feeVal = fee !== undefined ? num(fee) : 0;
      const netVal = net !== undefined ? num(net) : (gross - feeVal);
      return { gross, feeVal, netVal };
    }

    function row(p) {
      const user = p.user?.name ? `${p.user.name} (${p.user.email || ''})` : (p.user_id ?? '—');
      const { gross, feeVal, netVal } = computeFeeFallback(p);
      const provider = p.provider ?? '—';
      const statusNorm = String(p.status ?? 'pending').toLowerCase();
      const statusDisplay = formatStatus(p.status ?? 'pending');
      const detailsUrl = `{{ url('/admin/payouts') }}/${p.id}`;

      const actionLabel =
        statusNorm === 'paid' ? 'Paid'
        : statusNorm === 'rejected' ? 'Rejected'
        : statusNorm === 'failed' ? 'Failed'
        : statusDisplay;

      // Show action buttons based on status
      let actionsHtml = '';
      if (statusNorm === 'pending') {
        actionsHtml = `<div class="action-row">
             <button class="btn-danger btn-xs" onclick="approve(${p.id})">Approve</button>
             <button class="btn-danger btn-xs" onclick="rejectP(${p.id})">Reject</button>
           </div>`;
      } else if (statusNorm === 'processing' || statusNorm === 'approved') {
        // Show "Mark Paid" button for automated providers that are still processing
        actionsHtml = `<div class="action-row">
             <button class="btn-success btn-xs" onclick="markPaid(${p.id})">Mark Paid</button>
           </div>`;
      } else {
        actionsHtml = pill(actionLabel);
      }

      return `
        <tr>
          <td><a class="link" href="${detailsUrl}">${p.id}</a></td>
          <td>${user}</td>
          <td>${gross}</td>
          <td>${feeVal}</td>
          <td>${netVal}</td>
          <td>${pill(provider)}</td>
          <td>${pill(statusDisplay)}</td>
          <td>
            ${actionsHtml}
          </td>
        </tr>
      `;
    }

    async function approve(id) {
      await apiClient.approvePayout(id);
      await load();
    }

    async function rejectP(id) {
      const note = prompt('Reject note (required):');
      if (!note) return;
      await apiClient.rejectPayout(id, note);
      await load();
    }

    async function markPaid(id) {
      if (!confirm('Mark this payout as paid? This will complete the payout process.')) return;
      try {
        await apiClient.markPayoutAsPaid(id);
        await load();
      } catch (e) {
        console.error(e);
        alert('Error marking payout as paid: ' + (e?.message || 'Unknown error'));
      }
    }

    async function load() {
      const ok = await ensureAdmin();
      if (!ok) return;
      const meta = document.getElementById('meta');
      const rows = document.getElementById('rows');
      try {
        const payouts = await apiClient.getAllPayouts();
        meta.textContent = `${payouts.length} payouts`;
        rows.innerHTML = payouts.length ? payouts.map(row).join('') : `<tr><td colspan="8" class="muted">No payouts found.</td></tr>`;
      } catch (e) {
        console.error(e);
        meta.textContent = 'Failed to load payouts.';
        rows.innerHTML = `<tr><td colspan="8" class="muted">Error loading payouts.</td></tr>`;
      }
    }

    window.addEventListener('DOMContentLoaded', load);
  </script>
@endpush

