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

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'webhook_url' => env('WHATSAPP_WEBHOOK_URL'),
        'token' => env('WHATSAPP_TOKEN'),
        'from' => env('WHATSAPP_FROM', 'Mitho Map'),
        'timeout' => env('WHATSAPP_TIMEOUT', 10),
        'verification_ttl_minutes' => env('WHATSAPP_VERIFICATION_TTL_MINUTES', 10),
        'verification_max_attempts' => env('WHATSAPP_VERIFICATION_MAX_ATTEMPTS', 5),
    ],

    'apple' => [
        'client_ids' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('APPLE_CLIENT_IDS', env('APPLE_CLIENT_ID', '')))
        ))),
    ],

];
