<?php

namespace Botble\AiVideoGenerator\Tables;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Tables\Columns\ActiveStatusColumn;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;

class AiVideoApiTokenTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(AiVideoApiToken::class)
            ->setAjaxUrl(route('ai-video-generator.api-tokens.table'))
            ->addActions([
                EditAction::make()
                    ->route('ai-video-generator.api-tokens.edit')
                    ->permission('ai-video-generator.api-tokens.edit'),
                DeleteAction::make()
                    ->route('ai-video-generator.api-tokens.destroy')
                    ->permission('ai-video-generator.api-tokens.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                Column::make('token_api')->title('API token'),
                ActiveStatusColumn::make(),
                CreatedAtColumn::make(),
            ])
            ->queryUsing(fn (Builder $query) => $query
                ->select(['id', 'token_api', 'status', 'created_at'])
                ->latest('id'));
    }

    public function buttons(): array
    {
        return $this->addCreateButton(
            route('ai-video-generator.api-tokens.create'),
            'ai-video-generator.api-tokens.create'
        );
    }
}
