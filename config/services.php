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

    /*
    |--------------------------------------------------------------------------
    | Social (OAuth) Login Providers
    |--------------------------------------------------------------------------
    |
    | 'oauth_providers' is the whitelist SocialAuthController checks the
    | {provider} route parameter against — it's the only thing that needs a
    | new entry (plus a driver config block below) to enable a new provider
    | such as Apple, Facebook, or Microsoft. Every provider must have a
    | matching Socialite driver installed and configured with the same keys
    | (client_id, client_secret, redirect) that Socialite itself expects.
    |
    */

    'oauth_providers' => array_filter(explode(',', env('OAUTH_PROVIDERS', 'google'))),

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Browsershot (headless Chrome PDF rendering)
    |--------------------------------------------------------------------------
    |
    | Used by InvoicePdfRenderer for pixel-perfect, full-CSS invoice PDFs.
    | All three are optional — leave unset in production after a plain
    | `npm install puppeteer` there, which downloads its own bundled,
    | version-pinned Chromium and needs no path configuration at all. Only
    | set these if pointing at an existing system Chrome/Chromium install
    | (e.g. for local development, to skip the ~300MB Puppeteer download).
    |
    */

    'browsershot' => [
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pexels (demo catalog photos)
    |--------------------------------------------------------------------------
    |
    | Only used by `php artisan demo:import` to download real, royalty-free
    | stock photos for the demo catalog seeder. Get a free key at
    | https://www.pexels.com/api/.
    |
    */

    'pexels' => [
        'key' => env('PEXELS_API_KEY'),
    ],

];
