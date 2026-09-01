<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Theme\Facades\Theme;

class MyVideosController extends BaseController
{
    public function index()
    {
        return Theme::scope('my-videos')->render();
    }
}
