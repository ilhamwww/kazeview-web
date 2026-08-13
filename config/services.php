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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ninerouter' => [
        'base_url' => rtrim(env('NINEROUTER_BASE_URL', 'https://ninerouter.kazeview.com/v1'), '/'),
        'key' => env('NINEROUTER_API_KEY'),
        'vision_model' => env('NINEROUTER_VISION_MODEL', 'ag/gemini-3.6-flash-high'),
        'embedding_model' => env('NINEROUTER_EMBEDDING_MODEL', 'gemini/gemini-embedding-2-preview'),
        'prompt_version' => env('NINEROUTER_MOTOR_PROMPT_VERSION', 'motor-helmet-v2'),
        'timeout' => (int) env('NINEROUTER_TIMEOUT', 60),
        'connect_timeout' => (int) env('NINEROUTER_CONNECT_TIMEOUT', 10),
        'search_limit' => (int) env('AI_PHOTO_SEARCH_LIMIT', 20),
    ],

];
