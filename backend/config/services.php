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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT'),
    ],

    'clerk' => [
        'secret_key' => env('CLERK_SECRET_KEY'),
        'jwks_url' => env('CLERK_JWKS_URL'),
        'webhook_secret' => env('CLERK_WEBHOOK_SECRET'),
        // azp vérifié sur chaque JWT (optionnel, recommandé en prod)
        'authorized_party' => env('CLERK_AUTHORIZED_PARTY'),
        // JWKS locaux injectés en CI pour les tests sans appel réseau
        'testing_jwks' => env('CLERK_TESTING_JWKS'),
        // Secret symétrique injecté en CI pour les tests HS256 (sans OpenSSL)
        'testing_secret' => env('CLERK_TESTING_SECRET'),
    ],

];
