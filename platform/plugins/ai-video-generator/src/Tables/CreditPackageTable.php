<?php

namespace Botble\AiVideoGenerator\Tables;

use Botble\AiVideoGenerator\Models\AiVideoCreditPackage;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Illuminate\Database\Eloquent\Builder;

class CreditPackageTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(AiVideoCreditPackage::class)
            ->setAjaxUrl(route('ai-video-generator.credit-packages.table'))
            ->addActions([
                EditAction::make()
                    ->route('ai-video-generator.credit-packages.edit')
                    ->permission('ai-video-generator.credit-packages.edit'),
                DeleteAction::make()
                    ->route('ai-video-generator.credit-packages.destroy')
                    ->permission('ai-video-generator.credit-packages.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                Column::make('code')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.code')),
                NameColumn::make()->route('ai-video-generator.credit-packages.edit'),
                FormattedColumn::make('price')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.price'))
                    ->renderUsing(fn (FormattedColumn $column) => number_format((int) $column->getItem()->price, 0, ',', '.') . ' VND'),
                Column::make('credits')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.credit_packages.credits')),
                CreatedAtColumn::make(),
            ])
            ->queryUsing(function (Builder $query) {
                return $query
                    ->select(['id', 'code', 'name', 'price', 'credits', 'created_at'])
                    ->latest('id');
            });
    }

    public function buttons(): array
    {
        return $this->addCreateButton(
            route('ai-video-generator.credit-packages.create'),
            'ai-video-generator.credit-packages.create'
        );
    }
}
