<?php

namespace Botble\AiVideoGenerator\Tables;

use Botble\AiVideoGenerator\Models\AiVideoModelEndpoint;
use Botble\AiVideoGenerator\Tables\Columns\ActiveStatusColumn;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Illuminate\Database\Eloquent\Builder;

class AiVideoModelEndpointTable extends TableAbstract
{
    public function setup(): void
    {
        $this->model(AiVideoModelEndpoint::class)->setAjaxUrl(route('ai-video-generator.model-endpoints.table'))
            ->addActions([EditAction::make()->route('ai-video-generator.model-endpoints.edit'), DeleteAction::make()->route('ai-video-generator.model-endpoints.destroy')])
            ->addColumns([IdColumn::make(), NameColumn::make()->route('ai-video-generator.model-endpoints.edit'), Column::make('code')->title('Mã model'), FormattedColumn::make('price')->title('Giá')->renderUsing(fn (FormattedColumn $column) => (string) ((int) $column->getItem()->price)), ActiveStatusColumn::make(), CreatedAtColumn::make()])
            ->queryUsing(fn (Builder $query) => $query->select(['id', 'name', 'code', 'price', 'status', 'created_at'])->latest('id'));
    }
    public function buttons(): array { return $this->addCreateButton(route('ai-video-generator.model-endpoints.create'), 'ai-video-generator.index'); }
}
