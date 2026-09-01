<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Tables\AiVideoTaskTable;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;

class AiVideoTaskController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/ai-video-generator::ai-video-generator.tasks.name'), route('ai-video-generator.tasks.index'));
    }

    public function index(AiVideoTaskTable $table)
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.tasks.name'));

        return $table->renderTable();
    }

    public function show(AiGenerationTask $task)
    {
        $this->pageTitle(trans('plugins/ai-video-generator::ai-video-generator.tasks.view', ['id' => $task->getKey()]));

        return view('plugins/ai-video-generator::tasks.show', compact('task'));
    }
}
