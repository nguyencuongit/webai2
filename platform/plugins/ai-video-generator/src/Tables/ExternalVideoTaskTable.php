<?php

namespace Botble\AiVideoGenerator\Tables;

use Botble\AiVideoGenerator\Models\ExternalVideoTask;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\ViewAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\DateTimeColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;

class ExternalVideoTaskTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(ExternalVideoTask::class)
            ->addActions([
                ViewAction::make()
                    ->route('ai-video-generator.external-tasks.show')
                    ->permission('ai-video-generator.index'),
            ])
            ->addColumns([
                IdColumn::make(),
                Column::make('task_id')->title('Task ID'),
                Column::make('status')->title('Trạng thái'),
                Column::make('url_image')->title('URL ảnh'),
                Column::make('url_video')->title('URL video đầu vào'),
                DateTimeColumn::make('created_at'),
                DateTimeColumn::make('updated_at'),
            ])
            ->queryUsing(fn (Builder $query) => $query
                ->select([
                    'id',
                    'task_id',
                    'status',
                    'url_image',
                    'url_video',
                    'payload',
                    'created_at',
                    'updated_at',
                ])
                ->latest('id'));
    }
}
