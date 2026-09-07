<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;

class RoboNeoNetworkIncident extends BaseModel
{
    protected $table = 'ai_video_roboneo_network_incidents';

    protected $fillable = [
        'source',
        'task_reference',
        'api_token_id',
        'kiot_proxy_key_id',
        'proxy_fingerprint',
        'code',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];
}
