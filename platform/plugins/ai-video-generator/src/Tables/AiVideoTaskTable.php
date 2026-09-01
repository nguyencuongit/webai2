<?php

namespace Botble\AiVideoGenerator\Tables;

use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\ViewAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\DateTimeColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;

class AiVideoTaskTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(AiGenerationTask::class)
            ->addActions([
                ViewAction::make()
                    ->route('ai-video-generator.tasks.show')
                    ->permission('ai-video-generator.index'),
            ])
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('customer_id')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.tasks.customer'))
                    ->renderUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();

                        return $item->customer?->email ?: ($item->customer_id ?: '-');
                    }),
                Column::make('task_id')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.tasks.task_id')),
                Column::make('status')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.tasks.status')),
                DateTimeColumn::make('completed_at')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.tasks.completed_at')),
                DateTimeColumn::make('created_at'),
            ])
            ->queryUsing(function (Builder $query) {
                return $query
                    ->with('customer:id,email')
                    ->select([
                        'id',
                        'customer_id',
                        'task_id',
                        'status',
                        'is_completed',
                        'generated',
                        'has_nsfw',
                        'payload',
                        'completed_at',
                        'created_at',
                        'updated_at',
                    ])
                    ->latest('id');
            });
    }
}
