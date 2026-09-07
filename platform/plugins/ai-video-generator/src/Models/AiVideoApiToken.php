<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;

class AiVideoApiToken extends BaseModel
{
    protected $table = 'ai_video_api_tokens';

    protected $fillable = [
        'name',
        'token_api',
        'webhook_secret',
        'status',
        'health_status',
        'blocked_until',
        'busy_strikes',
        'last_failure_code',
        'last_failed_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'blocked_until' => 'datetime',
        'last_failed_at' => 'datetime',
    ];
}
