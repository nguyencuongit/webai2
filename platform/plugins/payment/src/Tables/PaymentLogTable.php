<?php

namespace Botble\Payment\Tables;

use Botble\Payment\Models\PaymentLog;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\ViewAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\DateTimeColumn;
use Botble\Table\Columns\EnumColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Database\Eloquent\Builder;

class PaymentLogTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(PaymentLog::class)
            ->addActions([
                ViewAction::make()
                    ->route('payments.logs.show'),
                DeleteAction::make()->route('payments.logs.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                Column::make('charge_id')->title('Mã giao dịch'),
                EnumColumn::make('payment_method'),
                Column::make('ip_address'),
                DateTimeColumn::make('created_at'),
            ])
            ->addBulkActions([
                DeleteBulkAction::make()->permission('payments.logs.destroy'),
            ])
            ->queryUsing(
                fn (Builder $query) => $query->select(['id', 'payment_id', 'charge_id', 'payment_method', 'request', 'ip_address', 'created_at'])
            );
    }

    public function getTableToolbarContent(): string
    {
        return view('plugins/payment::logs.partials.backfill-button')->render();
    }
}

