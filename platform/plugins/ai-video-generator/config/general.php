<?php

return [
    'base_url' => env('AI_VIDEO_GENERATOR_BASE_URL'),
    'api_key' => env('AI_VIDEO_GENERATOR_API_KEY'),
    'timeout' => env('AI_VIDEO_GENERATOR_TIMEOUT', 60),
    'external_webhook_url' => env('AI_VIDEO_GENERATOR_EXTERNAL_WEBHOOK_URL'),
    'external_webhook_token' => env('AI_VIDEO_GENERATOR_EXTERNAL_WEBHOOK_TOKEN'),
    'external_api_token' => env('AI_VIDEO_GENERATOR_EXTERNAL_API_TOKEN'),

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
            'busy_retry_delays_seconds' => [60, 180, 300],
            'admission_deadline_minutes' => (int) env('ROBONEO_ADMISSION_DEADLINE_MINUTES', 50),
            'task_lock_seconds' => (int) env('ROBONEO_TASK_LOCK_SECONDS', 2400),
            'token_lease_seconds' => (int) env('ROBONEO_TOKEN_LEASE_SECONDS', 600),
            'global_submit_lock_seconds' => (int) env('ROBONEO_GLOBAL_SUBMIT_LOCK_SECONDS', 180),
            'token_cooldown_min_seconds' => (int) env('ROBONEO_TOKEN_COOLDOWN_MIN_SECONDS', 300),
            'token_cooldown_max_seconds' => (int) env('ROBONEO_TOKEN_COOLDOWN_MAX_SECONDS', 600),
            'global_cooldown_min_seconds' => (int) env('ROBONEO_GLOBAL_COOLDOWN_MIN_SECONDS', 45),
            'global_cooldown_max_seconds' => (int) env('ROBONEO_GLOBAL_COOLDOWN_MAX_SECONDS', 90),
            'transient_retry_min_seconds' => (int) env('ROBONEO_TRANSIENT_RETRY_MIN_SECONDS', 30),
            'transient_retry_max_seconds' => (int) env('ROBONEO_TRANSIENT_RETRY_MAX_SECONDS', 90),
            'no_token_retry_seconds' => (int) env('ROBONEO_NO_TOKEN_RETRY_SECONDS', 30),
        ],
    ],
];
