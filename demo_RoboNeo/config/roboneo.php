<?php

return [
    'live_enabled' => env('ROBONEO_LIVE_ENABLED', false),

    'hosts' => [
        'ai_engine' => env('ROBONEO_AI_ENGINE_URL', 'https://ai-engine-gateway-roboneo.meitu.com'),
        'web_api' => env('ROBONEO_WEB_API_URL', 'https://webapi.roboneo.com'),
        'account_api' => env('ROBONEO_ACCOUNT_API_URL', 'https://api.account.meitu.com'),
        'strategy' => env('ROBONEO_STRATEGY_URL', 'https://strategy.app.meitudata.com'),
    ],

    'credentials' => [
        'app_token' => env('ROBONEO_APP_TOKEN'),
        'uid' => env('ROBONEO_UID'),
        'gid' => env('ROBONEO_GID'),
    ],

    'client' => [
        'id' => '1189857647',
        'scene' => 'roboneo',
        'area_code' => env('ROBONEO_AREA_CODE', 'VN'),
        'language' => env('ROBONEO_LANGUAGE', 'en'),
        'web_version' => '4.9.0',
        'zip_version' => '4.76000',
        'user_agent' => env(
            'ROBONEO_USER_AGENT',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '.
            '(KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'
        ),
    ],

    'motion' => [
        'api_name' => 'video_bonbon_motioncontrol_v26',
        'tree_id' => '93',
        'quality' => 'std',
        'poll_interval_seconds' => (int) env('ROBONEO_POLL_INTERVAL', 5),
        'max_poll_attempts' => (int) env('ROBONEO_MAX_POLL_ATTEMPTS', 240),
        'max_quote_cost' => (int) env('ROBONEO_MAX_QUOTE_COST', 250),
    ],
];
