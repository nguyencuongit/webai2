<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;

class AiVideoCreditPackage extends BaseModel
{
    protected $table = 'ai_video_credit_packages';

    protected $fillable = [
        'code',
        'name',
        'price',
        'credits',
        'features',
        'is_popular',
    ];

    protected $casts = [
        'price' => 'integer',
        'credits' => 'integer',
        'is_popular' => 'boolean',
    ];
}
