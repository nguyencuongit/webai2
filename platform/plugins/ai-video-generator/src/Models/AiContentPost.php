<?php

namespace Botble\AiVideoGenerator\Models;

use Botble\Base\Models\BaseModel;

class AiContentPost extends BaseModel
{
    protected $table = 'ai_content_posts';

    protected $fillable = [
        'title', 'excerpt', 'content', 'image', 'link', 'display_location', 'status',
    ];

    protected $casts = ['status' => 'boolean'];
}
