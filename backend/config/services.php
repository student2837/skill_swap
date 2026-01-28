<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Payments
     |--------------------------------------------------------------------------
     */
    'paypal' => [
        'base_url' => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'currency' => env('PAYPAL_CURRENCY', 'USD'),
        'timeout' => env('PAYPAL_TIMEOUT', 20),
        'payout_email_subject' => env('PAYPAL_PAYOUT_EMAIL_SUBJECT', 'You have a payout'),
        'payout_note' => env('PAYPAL_PAYOUT_NOTE', 'SkillSwap cashout'),
        'checkout_return_url' => env('PAYPAL_CHECKOUT_RETURN_URL'),
        'checkout_cancel_url' => env('PAYPAL_CHECKOUT_CANCEL_URL'),
    ],

    'whish' => [
        'collect_base_url' => env('WHISH_COLLECT_BASE_URL', 'https://example-whish.test'),
        'merchant_id' => env('WHISH_MERCHANT_ID'),
        'secret' => env('WHISH_SECRET'),
        'currency' => env('WHISH_CURRENCY', 'USD'),
        'webhook_url' => env('WHISH_WEBHOOK_URL'),
        'return_url' => env('WHISH_RETURN_URL'),
    ],

];
