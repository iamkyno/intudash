<?php

return [

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

    'smsportal' => [
        'client_id' => env('SMSPORTAL_CLIENT_ID'),
        'api_secret' => env('SMSPORTAL_API_SECRET'),
        'base_url' => env('SMSPORTAL_BASE_URL', 'https://rest.smsportal.com/v1'),
        'test_mode' => env('SMSPORTAL_TEST_MODE', true),
    ],

];
