<?php

namespace Botble\AiVideoGenerator\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;

class AiVideoGeneratorController extends BaseController
{
    public function index()
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.name'));

        return view('plugins/ai-video-generator::index');
    }

    public function settings()
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.settings.title'));

        return view('plugins/ai-video-generator::settings');
    }
}
