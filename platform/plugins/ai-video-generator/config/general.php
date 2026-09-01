<?php

return [
    'base_url' => env('AI_VIDEO_GENERATOR_BASE_URL'),
    'api_key' => env('AI_VIDEO_GENERATOR_API_KEY'),
    'timeout' => env('AI_VIDEO_GENERATOR_TIMEOUT', 60),
    'external_webhook_url' => env('AI_VIDEO_GENERATOR_EXTERNAL_WEBHOOK_URL'),
];
