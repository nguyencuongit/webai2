<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Admin;

use Botble\AiVideoGenerator\Models\ExternalVideoTask;
use Botble\AiVideoGenerator\Tables\ExternalVideoTaskTable;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;

class ExternalVideoTaskController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Lịch sử call API', route('ai-video-generator.external-tasks.index'));
    }

    public function index(ExternalVideoTaskTable $table)
    {
        $this->pageTitle('Lịch sử call API');

        return $table->renderTable();
    }

    public function show(ExternalVideoTask $task)
    {
        $this->pageTitle("Lịch sử call API #{$task->getKey()}");

        return view('plugins/ai-video-generator::external-tasks.show', compact('task'));
    }
}
