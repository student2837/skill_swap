@extends('layouts.app')

@section('title', 'Wallet – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    .kv { display:flex; justify-content:space-between; gap:1rem; padding:0.55rem 0; border-bottom:1px solid rgba(148,163,184,0.12); }
    .kv:last-child { border-bottom:none; }
    .pill { display:inline-flex; padding:0.2rem 0.6rem; border-radius:999px; font-size:0.75rem; border:1px solid rgba(148,163,184,0.25); background:rgba(15,23,42,0.55); color:#e5e7eb; }
    .payout-card {
      background: rgba(15, 23, 42, 0.4);
      border: 1px solid rgba(148, 163, 184, 0.15);
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 0.75rem;
    }
    .payout-card:last-child { margin-bottom: 0; }
    .payout-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.75rem;
      gap: 1rem;
    }
    .payout-date {
      color: rgba(226, 232, 240, 0.7);
      font-size: 0.85rem;
    }
    .payout-amounts {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
      font-size: 0.9rem;
    }
    .payout-amount-row {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
    }
    .payout-amount-row .label {
      color: rgba(226, 232, 240, 0.7);
    }
    .payout-amount-row .value {
      font-weight: 600;
      color: #e5e7eb;
    }
    .payout-net {
      border-top: 1px solid rgba(148, 163, 184, 0.12);
      padding-top: 0.5rem;
      margin-top: 0.5rem;
    }
    .payout-net .value {
      color: #60a5fa;
      font-size: 1.05rem;
    }
    .payout-note {
      margin-top: 0.75rem;
      padding: 0.5rem;
      background: rgba(239, 68, 68, 0.1);
      border-left: 3px solid rgba(239, 68, 68, 0.5);
      border-radius: 4px;
      font-size: 0.85rem;
      color: rgba(254, 202, 202, 0.9);
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
      <h2>Wallet</h2>
      <div class="topbar-actions">
        <a href="{{ route('cashout') }}" class="btn-secondary">Cashout</a>
        <a href="{{ route('credits') }}" class="btn-primary">Buy Credits</a>
      </div>
    </header>

    <section class="stats-grid">
      <div class="stat-card glass">
        <h3>Available balance</h3>
        <p class="stat-value" id="walletBalance">—</p>
        <p class="stat-label">Credits available</p>
      </div>

      <div class="stat-card glass">
        <h3>Locked balance</h3>
        <p class="stat-value" id="walletLocked">—</p>
        <p class="stat-label">Pending cashouts</p>
      </div>

      <div class="stat-card glass">
        <h3>Total earnings</h3>
        <p class="stat-value" id="walletTotal">—</p>
        <p class="stat-label">Available + locked</p>
      </div>
    </section>

    <section class="dash-card glass">
      <div class="dash-card-header">
        <h3>Wallet</h3>
        <p class="muted">Keep track of your credits and pending cashouts.</p>
      </div>
      <div class="muted" id="walletTip">Loading…</div>
    </section>

    <section class="dash-card glass" style="margin-top:1.15rem;">
      <div class="dash-card-header">
        <h3>Recent cashouts</h3>
        <p class="muted">Your payout requests and their status.</p>
      </div>
      <div id="payoutsBox" class="muted">Loading…</div>
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

    async function loadWallet() {
      try {
        // Use existing backend response from /transactions
        const tx = await apiClient.getUserTransactions();
        const balance = Number(tx?.balance ?? 0);
        const locked = Number(tx?.pending_cashout ?? 0);
        const total = balance + locked;

        document.getElementById('walletBalance').textContent = balance;
        document.getElementById('walletLocked').textContent = locked;
        document.getElementById('walletTotal').textContent = total;

        const tip = document.getElementById('walletTip');
        if (tip) {
          tip.textContent = locked > 0
            ? `You have a cashout in progress (${locked} credits locked).`
            : 'No cashouts in progress. Use the Cashout page to request a payout.';
        }
      } catch (err) {
        console.error('Error loading wallet:', err);
        document.getElementById('walletBalance').textContent = '—';
        document.getElementById('walletLocked').textContent = '—';
        document.getElementById('walletTotal').textContent = '—';
        const tip = document.getElementById('walletTip');
        if (tip) tip.textContent = 'Unable to load wallet.';
      }
    }

    function pill(text) {
      return `<span class="pill">${text}</span>`;
    }

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

    function formatPayout(p) {
      const when = p.created_at ? new Date(p.created_at).toLocaleString() : '—';
      let grossAmount = p.gross_amount ?? p.amount ?? 0;
      let feeAmount = p.fee_amount ?? 0;
      let netAmount = p.net_amount ?? p.amount ?? 0;
      
      // Calculate fee if missing (for old payouts)
      // If gross_amount equals net_amount and fee is 0, calculate backwards
      if (feeAmount === 0 && grossAmount === netAmount && grossAmount > 0) {
        // Calculate: net = gross * 0.8, so gross = net / 0.8
        grossAmount = Math.ceil(netAmount / 0.8);
        feeAmount = grossAmount - netAmount;
      }
      
      // Ensure net is calculated correctly
      if (!p.net_amount && grossAmount > 0) {
        netAmount = grossAmount - feeAmount;
      }
      
      const status = formatStatus(p.status ?? 'pending');
      
      // Always show fee breakdown if there's a difference between gross and net
      const hasFee = feeAmount > 0 && grossAmount !== netAmount;
      
      let html = `
        <div class="payout-card">
          <div class="payout-header">
            <div>
              <div class="payout-date">${when}</div>
              <div style="margin-top: 0.25rem;">${pill(status)}</div>
            </div>
          </div>
          <div class="payout-amounts">
            <div class="payout-amount-row">
              <span class="label">Requested amount:</span>
              <span class="value">${grossAmount} credits</span>
            </div>
            ${hasFee ? `
            <div class="payout-amount-row">
              <span class="label">Platform fee ({{ (int) (config('payments.cashout_fee_rate', 0.2) * 100) }}%):</span>
              <span class="value">-${feeAmount} credits</span>
            </div>
            ` : ''}
            <div class="payout-amount-row payout-net">
              <span class="label"><strong>Net payout:</strong></span>
              <span class="value"><strong>${netAmount} credits</strong></span>
            </div>
          </div>
          ${p.admin_note ? `
          <div class="payout-note">
            <strong>Admin note:</strong> ${p.admin_note}
          </div>
          ` : ''}
          ${p.processed_at ? `
          <div style="margin-top: 0.5rem; font-size: 0.8rem; color: rgba(226, 232, 240, 0.6);">
            Processed: ${new Date(p.processed_at).toLocaleString()}
          </div>
          ` : ''}
        </div>
      `;
      return html;
    }

    async function loadPayouts() {
      const box = document.getElementById('payoutsBox');
      if (!box) return;
      try {
        const raw = await apiClient.getUserPayouts();
        const payouts = Array.isArray(raw) ? raw : (raw && typeof raw === 'object' ? Object.values(raw) : []);
        if (!payouts.length) {
          box.textContent = 'No cashout requests yet.';
          return;
        }
        box.innerHTML = payouts.slice(0, 8).map(formatPayout).join('');

        // Enrich the wallet tip with the latest payout if there is a pending/progress cashout.
        const tip = document.getElementById('walletTip');
        if (tip) {
          const latest = payouts[0];
          const gross = (latest?.gross_amount ?? latest?.amount ?? null);
          const status = latest?.status ?? null;
          if (gross !== null && status) {
            tip.textContent = `Latest cashout: ${gross} credits • ${status}`;
          }
        }
      } catch (e) {
        console.error('Error loading payouts:', e);
        box.textContent = 'Failed to load cashouts. Please refresh the page.';
      }
    }

    window.addEventListener('DOMContentLoaded', async () => {
      await loadWallet();
      await loadPayouts();
    });
  </script>
@endpush

