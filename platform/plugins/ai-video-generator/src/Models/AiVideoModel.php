<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiVideoModel extends BaseModel
{
    protected $table = 'ai_video_models';

    protected $fillable = ['name', 'code', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function endpoints(): HasMany
    {
        return $this->hasMany(AiVideoModelEndpoint::class, 'model_id');
    }
}
