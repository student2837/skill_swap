@extends('layouts.app')

@section('title', 'Admin Cashout – SkillSwap')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}" />
  <style>
    .kv { display:flex; justify-content:space-between; gap:1rem; padding:0.55rem 0; border-bottom:1px solid rgba(148,163,184,0.12); }
    .kv:last-child{ border-bottom:none; }
    .danger{ color:#fecaca; }
    
    /* Add spacing around credit input field */
    .filter-label {
      margin-top: 1.25rem;
      margin-bottom: 1.5rem;
    }
    
    .filter-label span {
      display: block;
      margin-bottom: 0.75rem;
      font-size: 0.875rem;
      font-weight: 500;
      color: rgba(226, 232, 240, 0.9);
      letter-spacing: 0.01em;
    }
    
    /* Ensure balance text has proper spacing */
    .muted {
      margin-bottom: 0.75rem !important;
    }
    .profile-form {
      align-items: stretch;
      text-align: left;
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
  </style>
@endpush

@section('content')
  <div class="dashboard-bg"></div>

  @include('components.user-sidebar')
  @include('components.admin-sidebar')
  @include('components.sidebar-init')

  <main class="main-content">
    <header class="topbar glass">
      <h2>Admin Cashout</h2>
      <div class="topbar-actions">
        <a href="{{ route('admin.wallet') }}" class="btn-secondary">Platform Wallet</a>
      </div>
    </header>

    <section class="dash-card glass">
      <div class="dash-card-header">
        <h3>Cash out platform earnings</h3>
        <p class="muted"><strong>0% platform fee</strong> (admin cashout).</p>
      </div>

      <div class="muted">
        Available balance: <strong id="adminBalance">—</strong> credits
      </div>

      <div class="profile-form">
        <label class="filter-label">
          <span>Amount (credits)</span>
          <div class="credit-input-wrapper">
            <input id="amount" type="text" class="credit-input" placeholder="0" inputmode="numeric" pattern="[0-9]*" autocomplete="off" />
            <span class="credit-input-suffix">credits</span>
          </div>
        </label>

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
            <input id="payoutLabel" type="text" placeholder="e.g. Platform bank account" required />
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
      </div>

      <div class="kv" style="margin-top: 0.5rem;">
        <div>Gross</div>
        <div><strong id="gross">—</strong></div>
      </div>
      <div class="kv">
        <div>Platform fee (0%)</div>
        <div><strong id="fee">—</strong></div>
      </div>
      <div class="kv">
        <div>Net payout</div>
        <div><strong id="net">—</strong></div>
      </div>

      <button class="btn-primary" id="submit" onclick="submitCashout()" disabled style="margin-top: 1.5rem;">Submit admin cashout</button>
      <p class="muted" id="err" style="margin-top:0.75rem;"></p>
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

    let bal = 0;
    let payoutMethods = [];
    const num = v => (Number.isFinite(Number(v)) ? Number(v) : 0);

    async function ensureAdmin() {
      const u = await apiClient.getUser();
      if (!u?.is_admin) {
        window.location.href = "{{ route('dashboard') }}";
        return false;
      }
      return true;
    }

    // Integer-only validation function
    function validateIntegerInput(value) {
      const cleaned = value.replace(/[^0-9]/g, '');
      const normalized = cleaned === '' ? '' : String(parseInt(cleaned, 10) || '');
      return normalized;
    }
    
    function updatePreview() {
      const inputValue = document.getElementById('amount').value.trim();
      const amount = inputValue ? parseInt(inputValue, 10) : 0;
      document.getElementById('gross').textContent = amount ? `${amount} credits` : '—';
      document.getElementById('fee').textContent = amount ? `0 credits` : '—';
      document.getElementById('net').textContent = amount ? `${amount} credits` : '—';

      const btn = document.getElementById('submit');
      const err = document.getElementById('err');
      err.textContent = '';
      const payoutMethodId = document.getElementById('payoutMethodSelect').value;
      if (!payoutMethodId) { btn.disabled = true; err.textContent = 'Please select a payout method.'; err.className='muted danger'; return; }
      if (!amount || amount <= 0) { btn.disabled = true; return; }
      if (amount > bal) { btn.disabled = true; err.textContent = 'Insufficient balance.'; err.className='muted danger'; return; }
      btn.disabled = false;
    }

    async function loadBalance() {
      const tx = await apiClient.getUserTransactions();
      bal = num(tx?.balance ?? 0);
      document.getElementById('adminBalance').textContent = bal;
    }

    async function submitCashout() {
      const inputValue = document.getElementById('amount').value.trim();
      const amount = inputValue ? parseInt(inputValue, 10) : 0;
      const err = document.getElementById('err');
      err.textContent = '';
      const payoutMethodId = document.getElementById('payoutMethodSelect').value;
      
      if (!amount || amount < 1) {
        err.textContent = 'Please enter a valid amount (minimum 1 credit).';
        err.className = 'muted danger';
        return;
      }
      if (!payoutMethodId) {
        err.textContent = 'Please select a payout method.';
        err.className = 'muted danger';
        return;
      }
      
      if (amount > bal) {
        err.textContent = 'Insufficient balance.';
        err.className = 'muted danger';
        return;
      }
      
      try {
        await apiClient.requestPayout(amount, payoutMethodId);
        document.getElementById('amount').value = '';
        document.getElementById('amount').classList.remove('valid-input', 'invalid-input');
        await loadBalance();
        updatePreview();
      } catch (e) {
        console.error(e);
        err.textContent = e?.message || 'Cashout failed';
        err.className='muted danger';
      }
    }

    window.addEventListener('DOMContentLoaded', async () => {
      const ok = await ensureAdmin();
      if (!ok) return;
      await loadBalance();
      await loadPayoutMethods();
      updatePreview();
      
      const amountInput = document.getElementById('amount');
      
      // Integer-only input handling
      amountInput.addEventListener('input', (e) => {
        const originalValue = e.target.value;
        const validatedValue = validateIntegerInput(originalValue);
        
        if (originalValue !== validatedValue) {
          e.target.value = validatedValue;
        }
        
        // Add validation classes
        const numValue = parseInt(validatedValue, 10);
        amountInput.classList.remove('valid-input', 'invalid-input');
        if (validatedValue && numValue >= 1) {
          amountInput.classList.add('valid-input');
        } else if (validatedValue && numValue === 0) {
          amountInput.classList.add('invalid-input');
        }
        
        updatePreview();
      });
      
      // Focus/blur animations
      amountInput.addEventListener('focus', () => {
        amountInput.closest('label')?.classList.add('credit-input-focused');
      });
      
      amountInput.addEventListener('blur', () => {
        amountInput.closest('label')?.classList.remove('credit-input-focused');
        const value = parseInt(amountInput.value, 10);
        if (amountInput.value && (!value || value < 1)) {
          amountInput.value = '';
          amountInput.classList.remove('valid-input', 'invalid-input');
          updatePreview();
        } else if (value >= 1) {
          amountInput.classList.remove('invalid-input');
          amountInput.classList.add('valid-input');
        }
      });
      
      // Prevent invalid characters
      amountInput.addEventListener('keydown', (e) => {
        if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true) ||
            (e.keyCode >= 35 && e.keyCode <= 40)) {
          return;
        }
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
          e.preventDefault();
        }
      });
      
      // Format on paste
      amountInput.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const numbers = validateIntegerInput(paste);
        amountInput.value = numbers;
        updatePreview();
      });

      document.getElementById('togglePayoutMethodForm').addEventListener('click', () => {
        const form = document.getElementById('payoutMethodForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
      });
      document.getElementById('savePayoutMethodBtn').addEventListener('click', savePayoutMethod);
      document.getElementById('payoutProvider').addEventListener('change', updatePayoutReferenceLabel);
      document.getElementById('payoutMethodSelect').addEventListener('change', updatePreview);
      updatePayoutReferenceLabel();
    });

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
        updatePreview();
      } catch (e) {
        console.error(e);
        err.textContent = e?.message || 'Failed to save payout method.';
        err.className = 'muted danger';
      }
    }
  </script>
@endpush

