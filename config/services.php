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

    'coaching_messages' => [
        'api_key' => env('COACHING_MESSAGE_API_KEY'),
        'shared_secret' => env('COACHING_MESSAGE_SHARED_SECRET'),
        'source_system' => env('COACHING_MESSAGE_SOURCE_SYSTEM', 'sistem-pemeriksaan-jpl'),
        'timestamp_tolerance_seconds' => (int) env('COACHING_MESSAGE_TIMESTAMP_TOLERANCE_SECONDS', 300),
        'max_body_bytes' => (int) env('COACHING_MESSAGE_MAX_BODY_BYTES', 32768),
    ],

];
