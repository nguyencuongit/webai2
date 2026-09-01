<?php

namespace Botble\Payment\Repositories\Interfaces;

use Botble\Support\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Support\Collection;

interface PaymentInterface extends RepositoryInterface
{
    public function getTransactionReportSummary(array $filters = []): array;

    public function getPendingSePayPayments(array $filters = []): Collection;

    public function findPendingSePayPaymentById(int $id): mixed;

    public function getTransactionChartPayments(array $filters = []): Collection;
}

