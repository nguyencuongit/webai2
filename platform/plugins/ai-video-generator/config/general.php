<?php

return [
    'base_url' => env('AI_VIDEO_GENERATOR_BASE_URL'),
    'api_key' => env('AI_VIDEO_GENERATOR_API_KEY'),
    'timeout' => env('AI_VIDEO_GENERATOR_TIMEOUT', 60),
    'external_webhook_url' => env('AI_VIDEO_GENERATOR_EXTERNAL_WEBHOOK_URL'),

    'roboneo' => [
        'live_enabled' => env('ROBONEO_LIVE_ENABLED', false),
        'credentials' => [
            'app_token' => env('ROBONEO_APP_TOKEN'),
            'uid' => env('ROBONEO_UID'),
            'gid' => env('ROBONEO_GID'),
        ],
        'client' => [
            'area_code' => env('ROBONEO_AREA_CODE', 'VN'),
            'language' => env('ROBONEO_LANGUAGE', 'en'),
        ],
        'motion' => [
            'prompt' => env('ROBONEO_MOTION_PROMPT'),
            'max_quote_cost' => (int) env('ROBONEO_MAX_QUOTE_COST', 250),
            'poll_interval_seconds' => (int) env('ROBONEO_POLL_INTERVAL', 5),
            'max_poll_attempts' => (int) env('ROBONEO_MAX_POLL_ATTEMPTS', 240),
        ],
    ],
];
