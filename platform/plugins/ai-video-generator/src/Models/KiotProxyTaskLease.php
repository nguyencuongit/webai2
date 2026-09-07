<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;

class KiotProxyTaskLease extends BaseModel
{
    protected $table = 'ai_video_kiot_proxy_leases';

    protected $fillable = [
        'kiot_proxy_key_id',
        'owner',
        'lease_until',
        'accepted_at',
    ];

    protected $casts = [
        'lease_until' => 'datetime',
        'accepted_at' => 'datetime',
    ];
}
