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

    // Kurs mata uang menu Financials (App\Support\FinancialsDemo::fxRates), base IDR
    'frankfurter' => [
        'url'           => env('FRANKFURTER_URL', 'https://api.frankfurter.dev/v1'),
        'timeout'       => (int) env('FRANKFURTER_TIMEOUT', 5),
        'cache_minutes' => (int) env('FRANKFURTER_CACHE_MINUTES', 360),
        // tampilkan keterangan "1 USD = Rp ... · Frankfurter API" di bawah filter (sementara disembunyikan)
        'show_rate'     => (bool) env('FRANKFURTER_SHOW_RATE', false),
    ],

];
