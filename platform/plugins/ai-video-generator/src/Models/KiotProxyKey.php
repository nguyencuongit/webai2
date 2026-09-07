<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KiotProxyKey extends BaseModel
{
    protected $table = 'ai_video_kiot_proxy_keys';

    protected $fillable = [
        'name',
        'proxy_key',
        'region',
        'is_active',
        'max_concurrent_tasks',
        'health_status',
        'leased_by',
        'lease_until',
        'current_http_proxy',
        'current_socks5_proxy',
        'proxy_fingerprint',
        'location',
        'expiration_at',
        'next_request_at',
        'rotate_required',
        'failure_count',
        'last_failure_code',
        'last_failed_at',
        'last_success_at',
        'blocked_until',
        'last_used_at',
    ];

    protected $casts = [
        'proxy_key' => 'encrypted',
        'current_http_proxy' => 'encrypted',
        'current_socks5_proxy' => 'encrypted',
        'is_active' => 'boolean',
        'max_concurrent_tasks' => 'integer',
        'rotate_required' => 'boolean',
        'lease_until' => 'datetime',
        'expiration_at' => 'datetime',
        'next_request_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'last_success_at' => 'datetime',
        'blocked_until' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function taskLeases(): HasMany
    {
        return $this->hasMany(KiotProxyTaskLease::class, 'kiot_proxy_key_id');
    }
}
