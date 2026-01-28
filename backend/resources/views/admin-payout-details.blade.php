@extends('layouts.app')

@section('title', 'Payout Details – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    .grid2 { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:1.25rem; }
    @media (max-width: 900px){ .grid2 { grid-template-columns:minmax(0,1fr);} }
    .kv { display:flex; justify-content:space-between; gap:1rem; padding:0.55rem 0; border-bottom:1px solid rgba(148,163,184,0.12); }
    .kv:last-child { border-bottom:none; }
    .pill { display:inline-flex; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.75rem; border:1px solid rgba(148,163,184,0.25); background:rgba(15,23,42,0.55); color:#e5e7eb; }
    .muted-sm { color: rgba(226,232,240,0.7); font-size:0.85rem; }
  </style>
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

  <main class="main-content">
    <header class="topbar glass">
      <h2>Payout Details</h2>
      <div class="topbar-actions">
        <a href="{{ route('admin.payouts') }}" class="btn-secondary">Back to payouts</a>
      </div>
    </header>

    <section class="grid2">
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Summary</h3>
          <p class="muted" id="summaryMeta">Loading…</p>
        </div>
        <div id="summaryBox" class="muted-sm"></div>
      </div>

      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Status timeline</h3>
          <p class="muted">Key timestamps</p>
        </div>
        <div id="timelineBox" class="muted-sm"></div>
      </div>
    </section>

    <section class="dash-card glass" style="margin-top:1.25rem;">
      <div class="dash-card-header">
        <h3>Wallet movements</h3>
        <p class="muted">Transactions referencing this payout</p>
      </div>
      <div id="movementsBox" class="muted-sm">Loading…</div>
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

    const payoutId = Number("{{ $id }}");

    function num(v){ const n = Number(v); return Number.isFinite(n) ? n : 0; }
    function pill(t){ return `<span class="pill">${t ?? '—'}</span>`; }
    function dt(s){ return s ? new Date(s).toLocaleString() : '—'; }

    async function ensureAdmin() {
      const u = await apiClient.getUser();
      if (!u?.is_admin) {
        window.location.href = "{{ route('dashboard') }}";
        return false;
      }
      return true;
    }

    function kv(k, v){ return `<div class="kv"><div>${k}</div><div><strong>${v}</strong></div></div>`; }

    function computeFeeFallback(p) {
      const fee = (p.fee ?? p.fee_amount);
      const net = (p.net ?? p.net_amount);
      const gross = num(p.amount);
      const feeVal = fee !== undefined ? num(fee) : 0;
      const netVal = net !== undefined ? num(net) : (gross - feeVal);
      return { gross, feeVal, netVal };
    }

    async function load() {
      const ok = await ensureAdmin();
      if (!ok) return;

      const meta = document.getElementById('summaryMeta');
      const summary = document.getElementById('summaryBox');
      const timeline = document.getElementById('timelineBox');
      const moves = document.getElementById('movementsBox');

      try {
        const payouts = await apiClient.getAllPayouts();
        const p = payouts.find(x => Number(x.id) === payoutId);
        if (!p) {
          meta.textContent = 'Not found';
          summary.textContent = 'Payout not found.';
          timeline.textContent = '—';
          moves.textContent = '—';
          return;
        }

        const { gross, feeVal, netVal } = computeFeeFallback(p);
        meta.textContent = `Payout ${p.id}`;

        summary.innerHTML =
          kv('User', p.user?.name ? `${p.user.name} (${p.user.email || ''})` : (p.user_id ?? '—')) +
          kv('Gross requested', gross) +
          kv('Fee', feeVal) +
          kv('Net paid', netVal) +
          kv('Provider', pill(p.provider ?? '—')) +
          kv('Provider reference', p.provider_reference ?? '—') +
          kv('Status', pill(formatStatus(p.status ?? '—')));

        timeline.innerHTML =
          kv('Created', dt(p.created_at)) +
          kv('Approved', dt(p.approved_at)) +
          kv('Processed', dt(p.processed_at));

        // Wallet movements: show admin transactions referencing this payout
        const txs = await apiClient.getAllTransactions();
        const ref = `payout_${p.id}`;
        const related = txs.filter(t => t.reference_id === ref);
        moves.innerHTML = related.length
          ? related.map(t =>
              kv(
                `${t.user?.name ? t.user.name : 'User'} • ${t.type} • ${pill(t.status)}`,
                `amount=${t.amount} fee=${t.fee ?? 0} • ${dt(t.created_at)}`
              )
            ).join('')
          : 'No related transactions found.';
      } catch (e) {
        console.error(e);
        meta.textContent = 'Error';
        summary.textContent = 'Failed to load payout.';
        timeline.textContent = '—';
        moves.textContent = 'Failed to load movements.';
      }
    }

    window.addEventListener('DOMContentLoaded', load);
  </script>
@endpush

