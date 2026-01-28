@extends('layouts.app')

@section('title', 'Credits – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    /* Credits page: full-screen transactions viewer */
    .tx-overlay {
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: rgba(2, 6, 23, 0.92);
      backdrop-filter: blur(10px);
      display: none;
    }
    .tx-overlay-inner {
      height: 100%;
      padding: 1.2rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .tx-overlay-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: 0.9rem 1.2rem;
      border-radius: var(--radius);
      border: 1px solid rgba(148, 163, 184, 0.2);
      background: rgba(15, 23, 42, 0.72);
    }
    .tx-back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.45rem 0.9rem;
      border-radius: 999px;
      border: 1px solid rgba(148, 163, 184, 0.35);
      background: rgba(15, 23, 42, 0.85);
      color: #fff;
      cursor: pointer;
      font-size: 0.85rem;
      transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease, background 0.2s ease;
    }
    .tx-back-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
      filter: brightness(1.12);
    }
    .tx-overlay-table {
      flex: 1;
      overflow: hidden;
      border-radius: var(--radius);
      border: 1px solid rgba(148, 163, 184, 0.2);
      background: rgba(15, 23, 42, 0.72);
    }
    .tx-overlay-table .table-wrapper {
      margin-top: 0;
      height: 100%;
      overflow: auto;
      border-radius: 0;
    }
  </style>
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Credits & Transactions</h2>

      <div class="topbar-actions">
        <a class="btn-primary" href="#buyCredits">Buy credits</a>
        <a class="btn-secondary" href="{{ route('cashout') }}">Request cashout</a>
      </div>
    </header>

    <!-- Buy credits (REAL payment flow: Whish Collect) -->
    <section id="buyCredits" class="dash-card glass" style="margin-bottom: 1.15rem;">
      <div class="dash-card-header">
        <h3>Buy Credits</h3>
        <p class="muted">
          <strong>1 credit = 1 USD</strong> • Credits are added <strong>after successful payment confirmation</strong>.
        </p>
      </div>

      <div class="muted" style="margin-bottom: 0.6rem;">Select a package:</div>
      <div id="packageGrid" class="stats-grid" style="margin-top: 0;"></div>

      <div style="margin-top: 1rem;">
        <div class="muted" style="margin-bottom: 0.5rem;">Payment method:</div>
        <label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:0.5rem;">
          <input type="radio" name="payMethod" value="whish" checked />
          <span>Credit / Debit Card (Whish)</span>
        </label>
        <label style="display:flex; gap:0.5rem; align-items:center;">
          <input type="radio" name="payMethod" value="paypal" />
          <span>PayPal</span>
        </label>

        <button
          class="btn-primary"
          id="payBtn"
          type="button"
          onclick="startWhishCollect()"
          disabled
          style="display:inline-flex; margin: 0.85rem 0 0 0; cursor: pointer;"
        >
          Continue to payment
        </button>
      </div>
      <p class="muted" id="buyErr" style="margin-top: 0.75rem;"></p>
    </section>

    <!-- Stats -->
    <section class="stats-grid">
      <div class="stat-card glass">
        <h3>Current balance</h3>
        <p class="stat-value" id="currentBalance">—</p>
        <p class="stat-label">Available credits</p>
      </div>

      <div class="stat-card glass">
        <h3>Pending cashout</h3>
        <p class="stat-value" id="pendingCashout">—</p>
        <p class="stat-label">Processing</p>
      </div>

      <div class="stat-card glass">
        <h3>Taught this month</h3>
        <p class="stat-value" id="taughtThisMonth">—</p>
        <p class="stat-label">Credits earned</p>
      </div>

      <div class="stat-card glass">
        <h3>Learned this month</h3>
        <p class="stat-value" id="learnedThisMonth">—</p>
        <p class="stat-label">Credits spent</p>
      </div>
    </section>

    <!-- Transactions -->
    <section class="dash-card-full glass">
      <div class="dash-card-header">
        <h3 id="transactionsTitle">Recent transactions</h3>

        <button id="viewAllTransactionsBtn" class="btn-small" type="button">
          View all transactions
        </button>
      </div>

      <div class="table-wrapper">
        <table class="request-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Details</th>
              <th>Amount</th>
              <th>Balance</th>
            </tr>
          </thead>
          <tbody id="transactionsBody">
            <tr><td colspan="5">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Full-screen: All transactions -->
    <div id="transactionsOverlay" class="tx-overlay" aria-hidden="true">
      <div class="tx-overlay-inner">
        <div class="tx-overlay-header">
          <button id="closeTransactionsOverlayBtn" class="tx-back-btn" type="button">← Back</button>
          <h3 style="margin:0; font-size: 1.05rem;">All transactions</h3>
          <div style="width: 90px;"></div>
        </div>

        <div class="tx-overlay-table">
          <div class="table-wrapper">
            <table class="request-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Details</th>
                  <th>Amount</th>
                  <th>Balance</th>
                </tr>
              </thead>
              <tbody id="allTransactionsBody">
                <tr><td colspan="5">Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('js/api-client.js') }}"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    const token = localStorage.getItem("token");
    if (!token) window.location.href = "{{ route('login') }}";

    const API = "{{ url('/api') }}";
    const apiClient = new ApiClient(API);
    if (token) {
      apiClient.setToken(token);
    }

    // Block admin access - redirect to admin dashboard
    (async function() {
      try {
        const user = await apiClient.getUser();
        if (user.is_admin) {
          window.location.href = "{{ route('admin.dashboard') }}";
        }
      } catch (err) {
        console.error("Error checking admin status:", err);
      }
    })();

    function formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatTransactionType(type) {
      const typeMap = {
        'skill_payment': 'Lesson learned',
        'skill_earning': 'Lesson taught',
        'credit_purchase': 'Credits purchased',
        'cashout': 'Cashout requested',
        'refund': 'Refund'
      };
      return typeMap[type] || type;
    }

    function formatTransactionDetails(transaction) {
      if (transaction.type === 'skill_payment' || transaction.type === 'skill_earning') {
        return 'Session completed'; // Could be enhanced to show skill name from reference_id
      } else if (transaction.type === 'credit_purchase') {
        const ref = transaction.reference_id || '';
        if (typeof ref === 'string' && ref.startsWith('whish_collect_')) return 'Whish (Card)';
        return 'Deposit';
      } else if (transaction.type === 'cashout') {
        return 'Transfer to bank';
      }
      return '—';
    }

    const RECENT_TX_LIMIT = 5;
    let allTransactions = [];

    function renderTransactions() {
      const tbody = document.getElementById("transactionsBody");
      const titleEl = document.getElementById("transactionsTitle");
      const btn = document.getElementById("viewAllTransactionsBtn");

      if (!tbody) return;
      tbody.innerHTML = "";

      // Always show latest 5 in the main table
      const rows = allTransactions.slice(0, RECENT_TX_LIMIT);

      if (!rows.length) {
        tbody.innerHTML = "<tr><td colspan='5'>No transactions yet</td></tr>";
        if (titleEl) titleEl.textContent = "Recent transactions";
        return;
      }

      rows.forEach(transaction => {
        const row = document.createElement('tr');
        const isPositive = transaction.type === 'skill_earning' || transaction.type === 'credit_purchase';
        const amountClass = isPositive ? 'tx-pos' : 'tx-neg';
        const amountSign = isPositive ? '+' : '-';

        row.innerHTML = `
          <td>${formatDate(transaction.created_at)}</td>
          <td>${formatTransactionType(transaction.type)}</td>
          <td>${formatTransactionDetails(transaction)}</td>
          <td class="${amountClass}">${amountSign}${transaction.amount}</td>
          <td>${transaction.balance ?? '—'}</td>
        `;
        tbody.appendChild(row);
      });

      if (titleEl) titleEl.textContent = "Recent transactions";
      if (btn) btn.style.display = '';
    }

    function renderAllTransactions() {
      const tbody = document.getElementById("allTransactionsBody");
      if (!tbody) return;
      tbody.innerHTML = "";

      if (!allTransactions.length) {
        tbody.innerHTML = "<tr><td colspan='5'>No transactions yet</td></tr>";
        return;
      }

      allTransactions.forEach(transaction => {
        const row = document.createElement('tr');
        const isPositive = transaction.type === 'skill_earning' || transaction.type === 'credit_purchase';
        const amountClass = isPositive ? 'tx-pos' : 'tx-neg';
        const amountSign = isPositive ? '+' : '-';

        row.innerHTML = `
          <td>${formatDate(transaction.created_at)}</td>
          <td>${formatTransactionType(transaction.type)}</td>
          <td>${formatTransactionDetails(transaction)}</td>
          <td class="${amountClass}">${amountSign}${transaction.amount}</td>
          <td>${transaction.balance ?? '—'}</td>
        `;
        tbody.appendChild(row);
      });
    }

    function openTransactionsOverlay() {
      const overlay = document.getElementById('transactionsOverlay');
      if (!overlay) return;
      overlay.style.display = 'block';
      overlay.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      renderAllTransactions();
    }

    function closeTransactionsOverlay() {
      const overlay = document.getElementById('transactionsOverlay');
      if (!overlay) return;
      overlay.style.display = 'none';
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    async function loadCreditsData() {
      try {
        const data = await apiClient.getUserTransactions();
        
        // Update stats
        document.getElementById("currentBalance").textContent = data.balance || 0;
        document.getElementById("pendingCashout").textContent = data.pending_cashout || 0;
        document.getElementById("taughtThisMonth").textContent = data.taught_this_month > 0 ? `+${data.taught_this_month}` : '0';
        document.getElementById("learnedThisMonth").textContent = data.learned_this_month > 0 ? `-${data.learned_this_month}` : '0';

        // Store all transactions and render (recent by default)
        allTransactions = Array.isArray(data.transactions) ? data.transactions : [];
        renderTransactions();
        renderAllTransactions();
      } catch (err) {
        console.error("Error loading credits data:", err);
        document.getElementById("transactionsBody").innerHTML = "<tr><td colspan='5'>Error loading transactions</td></tr>";
      }
    }

    // ---- REAL buy credits flow (Whish Collect) ----
    const CREDIT_PACKAGES = @json(config('credit_packages.packages'));
    let selectedPackageKey = null;

    function renderPackages() {
      const grid = document.getElementById('packageGrid');
      if (!grid) return;
      grid.innerHTML = '';

      const entries = Object.entries(CREDIT_PACKAGES || {});
      if (!entries.length) {
        grid.innerHTML = "<p class='muted'>No packages configured.</p>";
        return;
      }

      entries.forEach(([key, pkg]) => {
        const usd = pkg?.usd ?? '—';
        const credits = pkg?.credits ?? '—';
        const card = document.createElement('div');
        card.className = 'stat-card glass';
        card.style.cursor = 'pointer';
        card.onclick = () => selectPackage(key);
        card.innerHTML = `
          <h3>$${usd}</h3>
          <p class="stat-value">${credits}</p>
          <p class="stat-label">credits</p>
        `;
        card.dataset.key = key;
        grid.appendChild(card);
      });
    }

    function selectPackage(key) {
      selectedPackageKey = key;
      document.querySelectorAll('#packageGrid .stat-card').forEach(el => {
        el.style.outline = el.dataset.key === key ? '2px solid rgba(34, 211, 238, 0.75)' : 'none';
      });
      document.getElementById('payBtn').disabled = false;
      document.getElementById('buyErr').textContent = '';
    }

    function startWhishCollect() {
      // Inform user that payment will be available soon
      alert("Payment processing will be available soon. Thank you for your patience!");
      return;
    }
    
    async function startWhishCollectOld() {
      const err = document.getElementById('buyErr');
      err.textContent = '';

      if (!selectedPackageKey) {
        err.textContent = 'Please select a package.';
        return;
      }

      const method = document.querySelector('input[name=\"payMethod\"]:checked')?.value;
      const payBtn = document.getElementById('payBtn');
      
      try {
        if (method === 'paypal') {
          const res = await apiClient.post('/deposits/paypal/order', { package: selectedPackageKey });
          if (!res?.approval_url) throw new Error('No approval_url returned from backend');
          if (res.reference) localStorage.setItem('last_paypal_reference', res.reference);
          // Open PayPal in new window
          const paypalWindow = window.open(res.approval_url, 'paypal_payment', 'width=800,height=600,scrollbars=yes,resizable=yes');
          if (paypalWindow) {
            payBtn.disabled = true;
            payBtn.textContent = 'Processing...';
            // Monitor the window to re-enable button when closed
            const checkClosed = setInterval(() => {
              if (paypalWindow.closed) {
                clearInterval(checkClosed);
                payBtn.disabled = false;
                payBtn.textContent = 'Continue to payment';
                // Refresh credits after payment window closes
                setTimeout(() => loadCreditsData(), 1000);
              }
            }, 500);
          } else {
            // If popup blocked, fallback to redirect
            window.location.href = res.approval_url;
          }
          return;
        }

        // default: Whish collect - open in new window instead of redirecting
        const res = await apiClient.post('/deposits/whish/collect', { package: selectedPackageKey });
        if (!res?.collect_url) throw new Error('No collect_url returned from backend');
        if (res.reference) localStorage.setItem('last_whish_reference', res.reference);
        
        // Open Whish payment in a new window/tab instead of redirecting
        const whishWindow = window.open(res.collect_url, 'whish_payment', 'width=800,height=600,scrollbars=yes,resizable=yes');
        
        if (whishWindow) {
          // Disable button and show processing state
          payBtn.disabled = true;
          const originalText = payBtn.textContent;
          payBtn.textContent = 'Payment window opened...';
          
          // Show success message
          err.textContent = 'Payment window opened in a new tab. Complete your payment there. This page will refresh automatically when you close the payment window.';
          err.style.color = 'rgba(34, 197, 94, 0.9)';
          
          // Monitor the payment window to detect when it closes
          const checkClosed = setInterval(() => {
            if (whishWindow.closed) {
              clearInterval(checkClosed);
              // Re-enable button
              payBtn.disabled = false;
              payBtn.textContent = originalText;
              // Refresh credits data after payment window closes
              setTimeout(() => {
                loadCreditsData().then(() => {
                  err.textContent = 'Payment window closed. Credits will be added automatically once payment is confirmed.';
                  err.style.color = 'rgba(34, 197, 94, 0.9)';
                  setTimeout(() => {
                    err.textContent = '';
                  }, 8000);
                });
              }, 1000);
            }
          }, 500);
        } else {
          // If popup was blocked, show error message
          err.textContent = 'Popup blocked. Please allow popups for this site and try again.';
          err.style.color = 'rgba(239, 68, 68, 0.9)';
          // Fallback: try to open in new tab after a delay
          setTimeout(() => {
            const fallbackWindow = window.open(res.collect_url, '_blank');
            if (fallbackWindow) {
              err.textContent = 'Payment opened in a new tab. Complete your payment there.';
              err.style.color = 'rgba(34, 197, 94, 0.9)';
            }
          }, 1000);
        }
      } catch (e) {
        console.error(e);
        err.textContent = e?.message || 'Failed to start payment';
        err.style.color = 'rgba(239, 68, 68, 0.9)';
        payBtn.disabled = false;
        payBtn.textContent = 'Continue to payment';
      }
    }

    // Check URL parameters for buy action
    const urlParams = new URLSearchParams(window.location.search);
    const shouldBuy = urlParams.get('buy');
    const amountParam = urlParams.get('amount');

    document.getElementById('viewAllTransactionsBtn')?.addEventListener('click', (e) => {
      e.preventDefault();
      openTransactionsOverlay();
    });
    document.getElementById('closeTransactionsOverlayBtn')?.addEventListener('click', (e) => {
      e.preventDefault();
      closeTransactionsOverlay();
    });
    document.getElementById('transactionsOverlay')?.addEventListener('click', (e) => {
      if (e.target && e.target.id === 'transactionsOverlay') {
        closeTransactionsOverlay();
      }
    });

    loadCreditsData().then(() => {
      renderPackages();
      // If buy parameter is present, scroll to the Buy Credits section (no fake top-up).
      if (shouldBuy === 'true') {
        setTimeout(() => document.getElementById('buyCredits')?.scrollIntoView({ behavior: 'smooth' }), 300);
      }
    });

    // (no extra method UX needed)
  </script>
@endpush
