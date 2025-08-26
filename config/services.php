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

    'gmail' => [
        'google_client_id' => env('GOOGLE_CLIENT_ID'),
        'google_client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'google_redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'google_project_id' => env('GOOGLE_PROJECT_ID'),
    ],

    'sms' => [
        'api_key' => env('SMS_API_KEY'),
        'endpoint' => env('SMS_API_ENDPOINT'),
        'from' => env('SMS_FROM'),
    ],

    'hostafrica' => [
        'endpoint' => env('HOSTAFRICA_API_ENDPOINT', 'https://my.hostafrica.com/modules/addons/DomainsReseller/api/index.php'),
        'username' => env('HOSTAFRICA_API_USERNAME'),
        'token' => env('HOSTAFRICA_API_TOKEN'),
    ],

    'rdap' => [
        'ke_endpoint' => env('RDAP_KE_ENDPOINT', 'https://rdap.kenic.or.ke'),
    ],

    'hostraha' => [
        'base_url' => env('HOSTRAHA_BASE_URL'),
        'username' => env('HOSTRAHA_USERNAME'),
        'api_key' => env('HOSTRAHA_API_KEY'),
    ],

    'paystack' => [
        'base_url' => env('PAYSTACK_BASE_URL'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
    ],
];
