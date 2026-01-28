<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Credit packages
     |--------------------------------------------------------------------------
     | Map purchasable packages (USD -> credits). Adjust to your business logic.
     | Transaction.amount stores credits, wallet credits only after confirmed payment.
     */
    'packages' => [
        'starter' => ['usd' => 5, 'credits' => 5],
        'plus' => ['usd' => 10, 'credits' => 10],
        'pro' => ['usd' => 25, 'credits' => 25],
        'mega' => ['usd' => 50, 'credits' => 50],
    ],
];

