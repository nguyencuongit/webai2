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
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
