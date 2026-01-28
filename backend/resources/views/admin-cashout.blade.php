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

      <label class="filter-label">
        <span>Amount (credits)</span>
        <div class="credit-input-wrapper">
          <input id="amount" type="text" class="credit-input" placeholder="0" inputmode="numeric" pattern="[0-9]*" autocomplete="off" />
          <span class="credit-input-suffix">credits</span>
        </div>
      </label>

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
      
      if (!amount || amount < 1) {
        err.textContent = 'Please enter a valid amount (minimum 1 credit).';
        err.className = 'muted danger';
        return;
      }
      
      if (amount > bal) {
        err.textContent = 'Insufficient balance.';
        err.className = 'muted danger';
        return;
      }
      
      try {
        await apiClient.requestPayout(amount);
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
    });
  </script>
@endpush

