<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGenerationTask extends BaseModel
{
    protected $table = 'ai_video_tasks';

    protected $fillable = [
        'customer_id',
        'task_id',
        'status',
        'is_completed',
        'generated',
        'has_nsfw',
        'payload',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'generated' => 'array',
        'has_nsfw' => 'array',
        'payload' => 'array',
        'completed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
