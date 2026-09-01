<?php

namespace Botble\AiVideoGenerator\Tables;

use Botble\AiVideoGenerator\Models\AiContentPost;
use Botble\AiVideoGenerator\Tables\Columns\ActiveStatusColumn;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Illuminate\Database\Eloquent\Builder;

class AiContentPostTable extends TableAbstract
{
    public function setup(): void
    {
        $this->model(AiContentPost::class)->setAjaxUrl(route('ai-video-generator.content-posts.table'))
            ->addActions([EditAction::make()->route('ai-video-generator.content-posts.edit'), DeleteAction::make()->route('ai-video-generator.content-posts.destroy')])
            ->addColumns([IdColumn::make(), NameColumn::make()->title('Tiêu đề')->route('ai-video-generator.content-posts.edit'), Column::make('display_location')->title('Vị trí'), ActiveStatusColumn::make(), CreatedAtColumn::make()])
            ->queryUsing(fn (Builder $query) => $query->select(['id', 'title', 'display_location', 'status', 'created_at'])->latest('id'));
    }
    public function buttons(): array { return $this->addCreateButton(route('ai-video-generator.content-posts.create'), 'ai-video-generator.index'); }
}
