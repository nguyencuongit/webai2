<?php

namespace Botble\Payment\Repositories\Eloquent;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PaymentRepository extends RepositoriesAbstract implements PaymentInterface
{
    public function getTransactionReportSummary(array $filters = []): array
    {
        $model = $this->getModel();
        $baseQuery = $this->applyReportFilters($model->newQuery(), $filters);
        $completedPayments = (clone $baseQuery)->where('status', PaymentStatusEnum::COMPLETED);

        $customers = (clone $completedPayments)
            ->whereNotNull('customer_id')
            ->get(['customer_id', 'customer_type'])
            ->map(fn ($payment) => ($payment->customer_type ?: 'customer') . ':' . $payment->customer_id)
            ->unique()
            ->count();

        return [
            'total_transactions' => (clone $baseQuery)->count(),
            'total_completed_transactions' => (clone $completedPayments)->count(),
            'total_pending_transactions' => (clone $baseQuery)
                ->where('status', PaymentStatusEnum::PENDING)
                ->count(),
            'total_completed_amount' => (float) (clone $completedPayments)->sum('amount'),
            'total_customers' => $customers,
        ];
    }

    public function getPendingSePayPayments(array $filters = []): Collection
    {
        $query = $this->getModel()
            ->newQuery()
            ->where('payment_channel', defined('SEPAY_PAYMENT_METHOD_NAME') ? SEPAY_PAYMENT_METHOD_NAME : 'sepay')
            ->where('status', PaymentStatusEnum::PENDING)
            ->whereNotNull('charge_id')
            ->with('customer')
            ->select(['id', 'charge_id', 'amount', 'currency', 'customer_id', 'customer_type', 'created_at']);

        return $this->applyReportFilters($query, $filters)
            ->latest('id')
            ->get();
    }

    public function findPendingSePayPaymentById(int $id): mixed
    {
        return $this->getModel()
            ->newQuery()
            ->whereKey($id)
            ->where('payment_channel', defined('SEPAY_PAYMENT_METHOD_NAME') ? SEPAY_PAYMENT_METHOD_NAME : 'sepay')
            ->where('status', PaymentStatusEnum::PENDING)
            ->with('customer')
            ->first();
    }

    public function getTransactionChartPayments(array $filters = []): Collection
    {
        return $this->applyReportFilters($this->getModel()->newQuery(), $filters)
            ->whereIn('status', [PaymentStatusEnum::COMPLETED, PaymentStatusEnum::PENDING])
            ->select(['status', 'created_at'])
            ->orderBy('created_at')
            ->get();
    }

    protected function applyReportFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['date'])) {
            return $query->whereDate('created_at', $filters['date']);
        }

        if (! empty($filters['month'])) {
            return $query->whereBetween('created_at', [
                CarbonImmutable::createFromFormat('Y-m', $filters['month'])->startOfMonth(),
                CarbonImmutable::createFromFormat('Y-m', $filters['month'])->endOfMonth(),
            ]);
        }

        return $query;
    }
}

