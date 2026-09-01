<?php

namespace Botble\AiVideoGenerator\Tables;

use Botble\AiVideoGenerator\Models\AiVideoModel;
use Botble\AiVideoGenerator\Tables\Columns\ActiveStatusColumn;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Illuminate\Database\Eloquent\Builder;

class AiVideoModelTable extends TableAbstract
{
    public function setup(): void
    {
        $this->model(AiVideoModel::class)->setAjaxUrl(route('ai-video-generator.models.table'))
            ->addActions([EditAction::make()->route('ai-video-generator.models.edit'), DeleteAction::make()->route('ai-video-generator.models.destroy')])
            ->addColumns([IdColumn::make(), NameColumn::make()->route('ai-video-generator.models.edit'), Column::make('code')->title('Mã model'), ActiveStatusColumn::make(), CreatedAtColumn::make()])
            ->queryUsing(fn (Builder $query) => $query->select(['id', 'name', 'code', 'status', 'created_at'])->latest('id'));
    }
    public function buttons(): array { return $this->addCreateButton(route('ai-video-generator.models.create'), 'ai-video-generator.index'); }
}
