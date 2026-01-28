<?php

return [
    // credits -> USD mapping for payouts (default 1 credit = 1 USD)
    'credit_to_usd_rate' => env('CREDIT_TO_USD_RATE', 1.0),
    // Cashout rules
    'cashout_min' => (int) env('CASHOUT_MIN_CREDITS', 10),
    'cashout_fee_rate' => (float) env('CASHOUT_FEE_RATE', 0.20),
    // Allowed payout providers & methods (extensible for Stripe later)
    'payout_providers' => ['manual', 'paypal'],
    'payout_methods' => ['bank_transfer', 'paypal_email'],
];

