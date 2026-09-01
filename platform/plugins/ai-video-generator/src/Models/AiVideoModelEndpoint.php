<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\AiVideoGenerator\Enums\AiVideoModelEndpointTag;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVideoModelEndpoint extends BaseModel
{
    protected $table = 'ai_video_model_endpoints';

    protected $fillable = [
        'model_id', 'name', 'code', 'endpoint', 'image', 'description', 'tag', 'endpoint_field', 'endpoints', 'fields', 'required_fields', 'defaults', 'durations', 'qualities',
        'aspect_ratios', 'character_orientations', 'shot_types', 'options', 'price', 'status',
    ];

    protected $casts = [
        'status' => 'boolean', 'price' => 'decimal:4', 'tag' => AiVideoModelEndpointTag::class, 'endpoints' => 'array', 'fields' => 'array', 'required_fields' => 'array',
        'defaults' => 'array', 'durations' => 'array', 'qualities' => 'array', 'aspect_ratios' => 'array',
        'character_orientations' => 'array', 'shot_types' => 'array', 'options' => 'array',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiVideoModel::class, 'model_id');
    }
}
