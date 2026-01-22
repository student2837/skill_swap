@extends('layouts.app')

@section('title', 'Credits – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.sidebar')

  <!-- Main -->
  <main class="main-content">
    <header class="topbar glass">
      <h2>Credits & Transactions</h2>

      <div class="topbar-actions">
        <button class="btn-primary" onclick="handleBuyCredits()">
          Buy credits
        </button>

        <button class="btn-secondary" onclick="handleRequestCashout()">
          Request cashout
        </button>
      </div>
    </header>

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
        <h3>Recent transactions</h3>

        <button
          class="btn-small js-show-toast"
          data-toast-message="Full transaction history (demo)."
        >
          View all
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
        return 'Card payment';
      } else if (transaction.type === 'cashout') {
        return 'Transfer to bank';
      }
      return '—';
    }

    async function loadCreditsData() {
      try {
        const data = await apiClient.getUserTransactions();
        
        // Update stats
        document.getElementById("currentBalance").textContent = data.balance || 0;
        document.getElementById("pendingCashout").textContent = data.pending_cashout || 0;
        document.getElementById("taughtThisMonth").textContent = data.taught_this_month > 0 ? `+${data.taught_this_month}` : '0';
        document.getElementById("learnedThisMonth").textContent = data.learned_this_month > 0 ? `-${data.learned_this_month}` : '0';

        // Update transactions table
        const tbody = document.getElementById("transactionsBody");
        tbody.innerHTML = "";

        if (!data.transactions || !data.transactions.length) {
          tbody.innerHTML = "<tr><td colspan='5'>No transactions yet</td></tr>";
          return;
        }

        data.transactions.forEach(transaction => {
          const row = document.createElement('tr');
          const isPositive = transaction.type === 'skill_earning' || transaction.type === 'credit_purchase';
          const amountClass = isPositive ? 'tx-pos' : 'tx-neg';
          const amountSign = isPositive ? '+' : '-';
          
          row.innerHTML = `
            <td>${formatDate(transaction.created_at)}</td>
            <td>${formatTransactionType(transaction.type)}</td>
            <td>${formatTransactionDetails(transaction)}</td>
            <td class="${amountClass}">${amountSign}${transaction.amount}</td>
            <td>${transaction.balance || '—'}</td>
          `;
          tbody.appendChild(row);
        });
      } catch (err) {
        console.error("Error loading credits data:", err);
        document.getElementById("transactionsBody").innerHTML = "<tr><td colspan='5'>Error loading transactions</td></tr>";
      }
    }

    async function handleBuyCredits() {
      const amount = prompt("Enter the number of credits you want to buy:");
      if (!amount || isNaN(amount) || parseInt(amount) <= 0) {
        alert("Please enter a valid amount");
        return;
      }

      try {
        // Create a credit purchase transaction
        await apiClient.createTransaction({
          type: 'credit_purchase',
          amount: parseInt(amount)
        });

        // Update user's credits (this would normally be done by backend)
        // For now, we'll reload the data
        alert("Credits purchased successfully!");
        loadCreditsData();
        
        // Notify dashboard to refresh credits
        localStorage.setItem('creditsUpdated', Date.now().toString());
        setTimeout(() => localStorage.removeItem('creditsUpdated'), 100);
      } catch (err) {
        alert("Failed to purchase credits: " + (err.message || "Unknown error"));
      }
    }

    async function handleRequestCashout() {
      const amount = prompt("Enter the number of credits you want to cash out:");
      if (!amount || isNaN(amount) || parseInt(amount) <= 0) {
        alert("Please enter a valid amount");
        return;
      }

      try {
        await apiClient.requestPayout(parseInt(amount));
        alert("Cashout request submitted successfully!");
        loadCreditsData();
      } catch (err) {
        alert("Failed to request cashout: " + (err.message || "Unknown error"));
      }
    }

    // Check if createTransaction method exists in api-client
    if (!apiClient.createTransaction) {
      apiClient.createTransaction = async function(transactionData) {
        return await this.post('/transactions', transactionData);
      };
    }

    // Check URL parameters for buy action
    const urlParams = new URLSearchParams(window.location.search);
    const shouldBuy = urlParams.get('buy');
    const amountParam = urlParams.get('amount');

    loadCreditsData().then(() => {
      // If buy parameter is present, open the buy credits dialog
      if (shouldBuy === 'true') {
        // Show a message if amount is provided, then prompt for user input
        setTimeout(() => {
          if (amountParam && !isNaN(amountParam) && parseInt(amountParam) > 0) {
            // Show info about how much they need, but let them choose the amount
            const needed = parseInt(amountParam);
            alert(`You need ${needed} credits to book the session.\n\nPlease enter the amount of credits you want to buy.`);
          }
          // Always prompt the user to enter the amount they want
          handleBuyCredits();
        }, 500);
      }
    });
  </script>
@endpush
