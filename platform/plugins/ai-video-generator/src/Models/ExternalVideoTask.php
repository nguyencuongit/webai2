<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;

class ExternalVideoTask extends BaseModel
{
    protected $table = 'ai_video_external_tasks';

    protected $fillable = [
        'task_id',
        'url_image',
        'url_video',
        'status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
