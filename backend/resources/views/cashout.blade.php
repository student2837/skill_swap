@extends('layouts.app')

@section('title', 'Cashout – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    .cashout-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      gap: 1.25rem;
      align-items: start; /* Align items to start instead of stretch */
    }
    @media (max-width: 900px) {
      .cashout-grid { grid-template-columns: minmax(0, 1fr); }
    }
    
    /* Make the request cashout card fit its content */
    .cashout-grid > .dash-card:first-child {
      align-self: start;
      height: fit-content;
    }
    .kv {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      padding: 0.55rem 0;
      border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    }
    .kv:last-child { border-bottom: none; }
    .kv .k { color: rgba(226,232,240,0.85); }
    .kv .v { font-weight: 600; }
    .danger { color: #fecaca; }
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
    /* Enhanced label styling */
    .filter-label {
      position: relative;
      margin-bottom: 0;
    }
    .profile-form {
      align-items: stretch;
      text-align: left;
    }
    
    .filter-label span {
      display: block;
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
      font-weight: 500;
      color: rgba(226, 232, 240, 0.9);
      letter-spacing: 0.01em;
    }
    .profile-form .payout-default-row {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 0.6rem;
      margin: 0.25rem 0 0.75rem;
      color: rgba(226, 232, 240, 0.9);
      font-size: 0.9rem;
      align-self: flex-start;
      width: 100%;
      text-align: left;
      padding-left: 0;
      flex-direction: row;
    }
    .profile-form .payout-default-row span {
      margin: 0;
    }
    .payout-default-row input[type="checkbox"] {
      width: 18px;
      height: 18px;
      appearance: none;
      border: 2px solid rgba(148, 163, 184, 0.5);
      border-radius: 6px;
      background: rgba(15, 23, 42, 0.6);
      cursor: pointer;
      position: relative;
      transition: all 0.2s ease;
    }
    .payout-default-row input[type="checkbox"]:checked {
      background: linear-gradient(120deg, var(--primary), var(--accent));
      border-color: transparent;
      box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.3);
    }
    .payout-default-row input[type="checkbox"]:checked::after {
      content: '✓';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -52%);
      color: #fff;
      font-size: 12px;
      font-weight: 700;
    }
    
    /* Add spacing after input field */
    .filter-label + .btn-primary,
    .credit-input-wrapper + .btn-primary {
      margin-top: 1.5rem;
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
      <h2>Cashout</h2>
      <div class="topbar-actions">
        <a href="{{ route('wallet') }}" class="btn-secondary">Wallet</a>
      </div>
    </header>

    <section class="cashout-grid">
      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Request cashout</h3>
          <p class="muted">Users pay a <strong>{{ (int) (config('payments.cashout_fee_rate', 0.2) * 100) }}% platform fee</strong>.</p>
        </div>

        <div class="muted" style="margin-bottom:0.5rem;">
          Available balance: <strong id="availableBalance">—</strong> credits
        </div>
        <div class="muted" style="margin-bottom:0.75rem; font-size:0.85rem;">
          Minimum cashout: <strong>{{ (int) config('payments.cashout_min', 10) }} credits</strong>
        </div>

        <div class="profile-form">
          <label class="filter-label">
            <span>Payout method</span>
            <select id="payoutMethodSelect" required>
              <option value="">Select a payout method</option>
            </select>
          </label>
          <div class="muted" style="margin-bottom:0.75rem;">
            <button class="btn-small" id="togglePayoutMethodForm" type="button">Add payout method</button>
          </div>

          <div id="payoutMethodForm" style="display:none; margin-bottom: 0.75rem;">
            <label class="filter-label">
              <span>Provider</span>
              <select id="payoutProvider" required>
                <option value="manual">Bank transfer (manual)</option>
                <option value="paypal">PayPal</option>
              </select>
            </label>
            <label class="filter-label">
              <span>Label</span>
              <input id="payoutLabel" type="text" placeholder="e.g. Main bank account" required />
            </label>
            <label class="filter-label">
              <span id="payoutReferenceLabel">Account reference</span>
              <input id="payoutReference" type="text" placeholder="Reference or email" required />
            </label>
            <label class="filter-label">
              <span>Last 4 (optional)</span>
              <input id="payoutLast4" type="text" maxlength="4" placeholder="1234" inputmode="numeric" pattern="[0-9]{4}" />
            </label>
            <label class="payout-default-row">
              <input type="checkbox" id="payoutDefault" />
              <span>Set as default</span>
            </label>
            <button class="btn-small" id="savePayoutMethodBtn" type="button">Save payout method</button>
            <p class="muted" id="payoutMethodError" style="margin-top:0.5rem;"></p>
          </div>

          <label class="filter-label">
            <span>Amount (credits)</span>
            <div class="credit-input-wrapper">
              <input id="cashoutAmount" type="text" class="credit-input" placeholder="0" inputmode="numeric" pattern="[0-9]*" autocomplete="off" />
              <span class="credit-input-suffix">credits</span>
            </div>
          </label>

          <button class="btn-primary" id="submitCashout" onclick="submitCashout()" disabled style="margin-top: 1.5rem;">
            Submit cashout request
          </button>

          <p class="muted" id="cashoutError" style="margin-top:0.75rem;"></p>
        </div>
      </div>

      <div class="dash-card glass">
        <div class="dash-card-header">
          <h3>Breakdown</h3>
          <p class="muted">Preview (fee shown as {{ (int) (config('payments.cashout_fee_rate', 0.2) * 100) }}%).</p>
        </div>

        <div class="kv">
          <div class="k">Gross</div>
          <div class="v" id="grossAmt">—</div>
        </div>
        <div class="kv">
          <div class="k">Platform fee ({{ (int) (config('payments.cashout_fee_rate', 0.2) * 100) }}%)</div>
          <div class="v" id="feeAmt">—</div>
        </div>
        <div class="kv">
          <div class="k">Net payout</div>
          <div class="v" id="netAmt">—</div>
        </div>

        <div style="margin-top:1rem;">
          <h3 style="margin-bottom:0.5rem;">Your payouts</h3>
          <div class="muted" id="payoutsList">Loading…</div>
        </div>
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

    let currentBalance = 0;
    let payoutMethods = [];
    const CASHOUT_MIN = {{ (int) config('payments.cashout_min', 10) }};
    const CASHOUT_FEE_RATE = {{ (float) config('payments.cashout_fee_rate', 0.2) }};

    function toNum(v) {
      const n = Number(v);
      return Number.isFinite(n) ? n : 0;
    }

    function refreshBreakdown() {
      const inputValue = document.getElementById('cashoutAmount').value.trim();
      const amount = inputValue ? parseInt(inputValue, 10) : 0;
      const gross = amount;
      const fee = Math.floor(gross * CASHOUT_FEE_RATE);
      const net = gross - fee;

      document.getElementById('grossAmt').textContent = gross ? `${gross} credits` : '—';
      document.getElementById('feeAmt').textContent = gross ? `${fee} credits` : '—';
      document.getElementById('netAmt').textContent = gross ? `${net} credits` : '—';

      const btn = document.getElementById('submitCashout');
      const err = document.getElementById('cashoutError');
      err.textContent = '';

      const selectedMethod = document.getElementById('payoutMethodSelect').value;
      if (!selectedMethod) {
        btn.disabled = true;
        err.textContent = 'Please select a payout method.';
        err.className = 'muted danger';
        return;
      }
      if (!amount || amount <= 0) {
        btn.disabled = true;
        return;
      }
      if (amount < CASHOUT_MIN) {
        btn.disabled = true;
        err.textContent = `Minimum cashout amount is ${CASHOUT_MIN} credits.`;
        err.className = 'muted danger';
        return;
      }
      if (amount > currentBalance) {
        btn.disabled = true;
        err.textContent = 'Insufficient balance.';
        err.className = 'muted danger';
        return;
      }
      btn.disabled = false;
    }

    async function loadBalance() {
      const tx = await apiClient.getUserTransactions();
      currentBalance = toNum(tx?.balance ?? 0);
      document.getElementById('availableBalance').textContent = currentBalance;
    }

    function renderPayoutMethods() {
      const select = document.getElementById('payoutMethodSelect');
      select.innerHTML = '<option value="">Select a payout method</option>';
      payoutMethods.forEach((method) => {
        const label = method.details?.label || 'Payout method';
        const last4 = method.details?.last4 ? `•••• ${method.details.last4}` : '';
        const provider = method.provider ? method.provider.toUpperCase() : '';
        const option = document.createElement('option');
        option.value = method.id;
        option.textContent = `${label} ${last4} ${provider}`.trim();
        if (method.is_default) {
          option.selected = true;
        }
        select.appendChild(option);
      });
    }

    async function loadPayoutMethods() {
      try {
        payoutMethods = await apiClient.listPayoutMethods();
        renderPayoutMethods();
      } catch (e) {
        console.error('Failed to load payout methods:', e);
      }
    }

    function updatePayoutReferenceLabel() {
      const provider = document.getElementById('payoutProvider').value;
      const label = document.getElementById('payoutReferenceLabel');
      const input = document.getElementById('payoutReference');
      if (provider === 'paypal') {
        label.textContent = 'PayPal email';
        input.placeholder = 'you@example.com';
      } else {
        label.textContent = 'Account reference';
        input.placeholder = 'Reference or account note';
      }
    }

    async function savePayoutMethod() {
      const err = document.getElementById('payoutMethodError');
      err.textContent = '';
      const provider = document.getElementById('payoutProvider').value;
      const label = document.getElementById('payoutLabel').value.trim();
      const last4 = document.getElementById('payoutLast4').value.trim();
      const providerReference = document.getElementById('payoutReference').value.trim();
      const isDefault = document.getElementById('payoutDefault').checked;

      if (!label || !providerReference) {
        err.textContent = 'Label and reference are required.';
        err.className = 'muted danger';
        return;
      }
      if (last4 && !/^[0-9]{4}$/.test(last4)) {
        err.textContent = 'Last 4 must be exactly 4 digits.';
        err.className = 'muted danger';
        return;
      }

      const method = provider === 'paypal' ? 'paypal_email' : 'bank_transfer';
      try {
        await apiClient.createPayoutMethod({
          provider,
          method,
          label,
          last4: last4 || null,
          provider_reference: providerReference,
          is_default: isDefault,
        });
        document.getElementById('payoutLabel').value = '';
        document.getElementById('payoutLast4').value = '';
        document.getElementById('payoutReference').value = '';
        document.getElementById('payoutDefault').checked = false;
        document.getElementById('payoutMethodForm').style.display = 'none';
        await loadPayoutMethods();
        refreshBreakdown();
      } catch (e) {
        console.error(e);
        err.textContent = e?.message || 'Failed to save payout method.';
        err.className = 'muted danger';
      }
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
              <div style="margin-top: 0.25rem; display: inline-flex; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; border: 1px solid rgba(148, 163, 184, 0.25); background: rgba(15, 23, 42, 0.55); color: #e5e7eb;">${status}</div>
            </div>
          </div>
          <div class="payout-amounts">
            <div class="payout-amount-row">
              <span class="label">Requested amount:</span>
              <span class="value">${grossAmount} credits</span>
            </div>
            ${hasFee ? `
            <div class="payout-amount-row">
              <span class="label">Platform fee (${Math.round(CASHOUT_FEE_RATE * 100)}%):</span>
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
      const box = document.getElementById('payoutsList');
      try {
        const raw = await apiClient.getUserPayouts();
        const payouts = Array.isArray(raw) ? raw : (raw && typeof raw === 'object' ? Object.values(raw) : []);
        if (!payouts.length) {
          box.textContent = 'No payouts yet.';
          return;
        }
        box.innerHTML = payouts.slice(0, 10).map(formatPayout).join('');
      } catch (e) {
        console.error('Error loading payouts:', e);
        box.textContent = 'Failed to load payouts. Please refresh the page.';
      }
    }

    async function submitCashout() {
      const inputValue = document.getElementById('cashoutAmount').value.trim();
      const amount = inputValue ? parseInt(inputValue, 10) : 0;
      const err = document.getElementById('cashoutError');
      err.textContent = '';
      
      // Validate integer
      const payoutMethodId = document.getElementById('payoutMethodSelect').value;
      if (!payoutMethodId) {
        err.textContent = 'Please select a payout method.';
        err.className = 'muted danger';
        return;
      }
      if (!amount || amount < CASHOUT_MIN) {
        err.textContent = `Please enter a valid amount (minimum ${CASHOUT_MIN} credits).`;
        err.className = 'muted danger';
        return;
      }
      
      if (amount > currentBalance) {
        err.textContent = 'Insufficient balance.';
        err.className = 'muted danger';
        return;
      }
      
      try {
        await apiClient.requestPayout(amount, payoutMethodId);
        document.getElementById('cashoutAmount').value = '';
        document.getElementById('cashoutAmount').classList.remove('valid-input', 'invalid-input');
        await loadBalance();
        refreshBreakdown();
        await loadPayouts();
      } catch (e) {
        console.error(e);
        err.textContent = e?.message || 'Cashout failed';
        err.className = 'muted danger';
      }
    }

    window.addEventListener('DOMContentLoaded', async () => {
      await loadBalance();
      await loadPayoutMethods();
      refreshBreakdown();
      await loadPayouts();
      
      const amountInput = document.getElementById('cashoutAmount');
      
      // Integer-only validation function
      function validateIntegerInput(value) {
        // Remove any non-digit characters
        const cleaned = value.replace(/[^0-9]/g, '');
        // Remove leading zeros except for single zero
        const normalized = cleaned === '' ? '' : String(parseInt(cleaned, 10) || '');
        return normalized;
      }
      
      // Enhanced input handling with professional UX - integer only
      amountInput.addEventListener('input', (e) => {
        const originalValue = e.target.value;
        const validatedValue = validateIntegerInput(originalValue);
        
        // Update value if it changed
        if (originalValue !== validatedValue) {
          e.target.value = validatedValue;
        }
        
        // Add validation classes for visual feedback
        const numValue = parseInt(validatedValue, 10);
        amountInput.classList.remove('valid-input', 'invalid-input');
        if (validatedValue && numValue >= CASHOUT_MIN) {
          amountInput.classList.add('valid-input');
        } else if (validatedValue && numValue > 0 && numValue < CASHOUT_MIN) {
          amountInput.classList.add('invalid-input');
        }
        
        refreshBreakdown();
      });
      
      // Add focus/blur animations
      amountInput.addEventListener('focus', () => {
        amountInput.closest('.filter-label')?.classList.add('credit-input-focused');
      });
      
      amountInput.addEventListener('blur', () => {
        amountInput.closest('.filter-label')?.classList.remove('credit-input-focused');
        // Validate on blur - ensure minimum value
        const value = parseInt(amountInput.value, 10);
        if (amountInput.value && (!value || value < CASHOUT_MIN)) {
          amountInput.value = '';
          amountInput.classList.remove('valid-input', 'invalid-input');
          refreshBreakdown();
        } else if (value >= CASHOUT_MIN) {
          amountInput.classList.remove('invalid-input');
          amountInput.classList.add('valid-input');
        }
      });
      
      // Prevent invalid characters - only allow digits
      amountInput.addEventListener('keydown', (e) => {
        // Allow: backspace, delete, tab, escape, enter
        if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true) ||
            // Allow: home, end, left, right, arrow keys
            (e.keyCode >= 35 && e.keyCode <= 40)) {
          return;
        }
        // Only allow digits (0-9) - block everything else including decimals, minus, plus
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
          e.preventDefault();
        }
      });
      
      // Format on paste - extract only integers
      amountInput.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const numbers = validateIntegerInput(paste);
        amountInput.value = numbers;
        refreshBreakdown();
      });
      
      // Prevent context menu on right-click (optional, for better UX)
      amountInput.addEventListener('contextmenu', (e) => {
        e.preventDefault();
      });

      document.getElementById('togglePayoutMethodForm').addEventListener('click', () => {
        const form = document.getElementById('payoutMethodForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
      });
      document.getElementById('savePayoutMethodBtn').addEventListener('click', savePayoutMethod);
      document.getElementById('payoutProvider').addEventListener('change', updatePayoutReferenceLabel);
      document.getElementById('payoutMethodSelect').addEventListener('change', refreshBreakdown);
      updatePayoutReferenceLabel();
    });
  </script>
@endpush

