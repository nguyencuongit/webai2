<?php

namespace Botble\AiVideoGenerator\Tables;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Tables\Columns\ActiveStatusColumn;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\EmailColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Illuminate\Database\Eloquent\Builder;

class CustomerTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Customer::class)
            ->setAjaxUrl(route('ai-video-generator.customers.table'))
            ->addActions([
                EditAction::make()->route('ai-video-generator.customers.edit')->permission('ai-video-generator.customers.edit'),
                DeleteAction::make()->route('ai-video-generator.customers.destroy')->permission('ai-video-generator.customers.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                NameColumn::make()->route('ai-video-generator.customers.edit'),
                EmailColumn::make(),
                Column::make('credits_balance')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.customers.credits_balance')),
                FormattedColumn::make('parent_id')
                    ->title(trans('plugins/ai-video-generator::ai-video-generator.customers.parent'))
                    ->renderUsing(function (FormattedColumn $column) {
                        $parent = $column->getItem()->parent;

                        return $parent ? trim($parent->name . ' - ' . $parent->email) : '-';
                    }),
                ActiveStatusColumn::make(),
                CreatedAtColumn::make(),
            ])
            ->queryUsing(function (Builder $query) {
                return $query
                    ->with('parent:id,name,email')
                    ->select(['id', 'name', 'email', 'credits_balance', 'parent_id', 'status', 'created_at'])
                    ->latest('id');
            });
    }

    public function buttons(): array
    {
        return $this->addCreateButton(
            route('ai-video-generator.customers.create'),
            'ai-video-generator.customers.create'
        );
    }
}
