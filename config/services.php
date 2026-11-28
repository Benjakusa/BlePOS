<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'mpesa' => [
        'encryption_key' => env('MPESA_ENCRYPTION_KEY'),
        'encryption_keys' => json_decode((string) env('MPESA_ENCRYPTION_KEYS', '{}'), true) ?: [],
        'active_encryption_key_version' => (string) env('MPESA_ENCRYPTION_ACTIVE_KEY_VERSION', 'v1'),
        'sandbox_base_url' => env('MPESA_SANDBOX_BASE_URL', 'https://sandbox.safaricom.co.ke'),
        'production_base_url' => env('MPESA_PRODUCTION_BASE_URL', 'https://api.safaricom.co.ke'),
        'sandbox_mode' => env('MPESA_SANDBOX_MODE', true),
        'callback_secret' => env('MPESA_CALLBACK_SECRET', ''),
        'callback_allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('MPESA_CALLBACK_ALLOWED_IPS', ''))))),
        'allow_platform_fallback_for_tenant_sales' => (bool) env('MPESA_ALLOW_PLATFORM_FALLBACK_FOR_TENANT_SALES', false),
    ],

];
