<?php

namespace Botble\Payment\Services;

use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use FriendsOfBotble\SePay\SePayClient;
use FriendsOfBotble\SePay\Services\SePayPaymentProcessor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PaymentTransactionReportService
{
    private const SEPAY_LOOKBACK_DAYS = 30;

    private const SEPAY_PAGE_SIZE = 100;

    private const SEPAY_MAX_PAGES = 5;

    public function __construct(
        private PaymentInterface $paymentRepository,
        private SePayClient $sePayClient,
        private SePayPaymentProcessor $paymentProcessor,
    ) {}

    public function getReport(?string $date = null, ?string $month = null): array
    {
        $filters = $this->normalizeFilters($date, $month);
        $summary = $this->paymentRepository->getTransactionReportSummary($filters);
        $pendingTransfers = $this->findTransferredButPendingPayments($filters);

        return [
            ...$summary,
            'pending_transfers' => $pendingTransfers['items'],
            'sepay_available' => $pendingTransfers['available'],
            'sepay_error' => $pendingTransfers['error'],
            'chart' => $this->makeChart($this->paymentRepository->getTransactionChartPayments($filters), $filters),
        ];
    }

    public function getPendingTransferByPaymentId(int $paymentId): ?array
    {
        return $this->findTransferredButPendingPayments()['items']
            ->first(fn (array $item) => $item['payment_id'] === $paymentId);
    }

    public function confirmPendingTransfer(int $paymentId): ?array
    {
        $transfer = $this->getPendingTransferByPaymentId($paymentId);
        $payment = $this->paymentRepository->findPendingSePayPaymentById($paymentId);

        if (! $transfer || ! $payment) {
            return null;
        }

        $result = $this->paymentProcessor->complete(
            $payment,
            $transfer['sepay_transaction_id'],
            $transfer['amount'],
            SePayPaymentProcessor::SOURCE_API,
        );
        $completedPayment = $result['payment'];

        if ($result['newly_completed']) {
            $paymentChannel = defined('SEPAY_PAYMENT_METHOD_NAME') ? SEPAY_PAYMENT_METHOD_NAME : 'sepay';

            do_action('payment_after_api_response', $paymentChannel, [
                'source' => 'manual_transaction_report_confirmation',
                'payment_id' => $completedPayment->getKey(),
                'charge_id' => $completedPayment->charge_id,
                'provider_transaction_id' => $transfer['sepay_transaction_id'],
            ], [
                'success' => true,
                'status' => $completedPayment->status?->getValue(),
                'payment_id' => $completedPayment->getKey(),
                'charge_id' => $completedPayment->charge_id,
                'provider_transaction_id' => $transfer['sepay_transaction_id'],
                'source' => 'manual_transaction_report_confirmation',
            ]);

            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'charge_id' => $completedPayment->charge_id,
                'order_id' => $completedPayment->order_id,
                'customer_id' => $completedPayment->customer_id,
                'customer_type' => $completedPayment->customer_type,
                'payment_channel' => $completedPayment->payment_channel?->getValue(),
                'status' => $completedPayment->status?->getValue(),
                'amount' => $completedPayment->amount,
            ], request());
        }

        return $transfer;
    }

    /**
     * @return array{items: Collection, available: bool, error: ?string}
     */
    protected function findTransferredButPendingPayments(array $filters = []): array
    {
        $pendingPayments = $this->paymentRepository->getPendingSePayPayments($filters);

        if ($pendingPayments->isEmpty()) {
            return ['items' => collect(), 'available' => true, 'error' => null];
        }

        if (! $this->sePayClient->isConnected()) {
            return [
                'items' => collect(),
                'available' => false,
                'error' => 'Chưa kết nối SePay API nên chưa thể đối chiếu các giao dịch đang chờ.',
            ];
        }

        try {
            $transactions = $this->getRecentSePayTransactions($filters);
        } catch (\Throwable) {
            return [
                'items' => collect(),
                'available' => false,
                'error' => 'Không thể lấy lịch sử giao dịch từ SePay để đối chiếu lúc này.',
            ];
        }

        $items = collect();

        foreach ($transactions as $transaction) {
            $content = trim((string) ($transaction['transaction_content'] ?? $transaction['content'] ?? ''));
            $amount = $transaction['amount_in'] ?? $transaction['transferAmount'] ?? null;
            $transferType = strtolower(trim((string) ($transaction['transfer_type'] ?? $transaction['transferType'] ?? 'in')));

            if ($content === '' || ! is_numeric($amount) || (float) $amount <= 0 || $transferType !== 'in') {
                continue;
            }

            $payment = $pendingPayments->first(fn ($item) => str_contains($content, (string) $item->charge_id)
                && $this->amountsMatch($item->amount, $amount));

            if (! $payment) {
                continue;
            }

            $items->push([
                'payment_id' => $payment->getKey(),
                'charge_id' => $payment->charge_id,
                'payer_name' => $payment->customer?->name ?: '—',
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency ?: 'VND',
                'payment_status_label' => 'Đã chuyển khoản thành công',
                'sepay_transaction_id' => (string) ($transaction['id'] ?? '—'),
                'transferred_at' => $transaction['transaction_date'] ?? $transaction['transactionDate'] ?? null,
                'created_at' => $payment->created_at,
            ]);
        }

        return ['items' => $items->unique('payment_id')->values(), 'available' => true, 'error' => null];
    }

    protected function getRecentSePayTransactions(array $filters = []): array
    {
        $end = now('Asia/Ho_Chi_Minh');
        $start = $end->copy()->subDays(self::SEPAY_LOOKBACK_DAYS)->startOfDay();

        if (! empty($filters['date'])) {
            $start = CarbonImmutable::parse($filters['date'], 'Asia/Ho_Chi_Minh')->startOfDay();
            $end = $start->endOfDay();
        } elseif (! empty($filters['month'])) {
            $start = CarbonImmutable::createFromFormat('Y-m', $filters['month'], 'Asia/Ho_Chi_Minh')->startOfMonth();
            $end = $start->endOfMonth();
        }
        $transactions = [];

        for ($page = 1; $page <= self::SEPAY_MAX_PAGES; $page++) {
            $response = $this->sePayClient->request('get', 'transactions', [
                'transaction_date_from' => $start->format('Y-m-d H:i:s'),
                'transaction_date_to' => $end->copy()->endOfDay()->format('Y-m-d H:i:s'),
                'transfer_type' => 'in',
                'page' => $page,
                'per_page' => self::SEPAY_PAGE_SIZE,
            ]);
            $pageItems = $response['transactions'] ?? $response;

            if (! is_array($pageItems) || ! array_is_list($pageItems)) {
                break;
            }

            $transactions = [...$transactions, ...array_values(array_filter($pageItems, 'is_array'))];

            if (count($pageItems) < self::SEPAY_PAGE_SIZE) {
                break;
            }
        }

        return $transactions;
    }

    protected function amountsMatch(int|float|string|null $expected, int|float|string|null $actual): bool
    {
        return is_numeric($expected)
            && is_numeric($actual)
            && number_format((float) $expected, 2, '.', '') === number_format((float) $actual, 2, '.', '');
    }

    protected function normalizeFilters(?string $date, ?string $month): array
    {
        $validDate = null;
        $validMonth = null;

        if ($date) {
            try {
                $validDate = CarbonImmutable::createFromFormat('Y-m-d', $date)->toDateString();
            } catch (\Throwable) {
            }
        }

        if (! $validDate && $month) {
            try {
                $validMonth = CarbonImmutable::createFromFormat('Y-m', $month)->format('Y-m');
            } catch (\Throwable) {
            }
        }

        return ['date' => $validDate, 'month' => $validMonth];
    }

    protected function makeChart(Collection $payments, array $filters): array
    {
        if ($filters['date']) {
            $day = CarbonImmutable::parse($filters['date']);

            return $this->makeChartData([$day], $payments, 'Tổng giao dịch ngày ' . $day->format('d/m/Y'), false);
        }

        if ($filters['month']) {
            $start = CarbonImmutable::createFromFormat('Y-m', $filters['month'])->startOfMonth();
            $days = collect(range(0, $start->daysInMonth - 1))->map(fn (int $offset) => $start->addDays($offset))->all();

            return $this->makeChartData($days, $payments, 'Tổng giao dịch theo ngày trong tháng ' . $start->format('m/Y'), false);
        }

        $start = now()->startOfMonth()->subMonths(11)->toImmutable();
        $months = collect(range(0, 11))->map(fn (int $offset) => $start->addMonths($offset))->all();

        return $this->makeChartData($months, $payments, 'Tổng giao dịch theo 12 tháng gần nhất', true);
    }

    protected function makeChartData(array $periods, Collection $payments, string $title, bool $isMonthly): array
    {
        $groups = $payments->groupBy(fn ($payment) => $isMonthly
            ? $payment->created_at->format('Y-m')
            : $payment->created_at->toDateString());

        return [
            'title' => $title,
            'labels' => collect($periods)->map(fn (CarbonImmutable $period) => $isMonthly ? $period->format('m/Y') : $period->format('d/m'))->all(),
            'completed' => collect($periods)->map(fn (CarbonImmutable $period) => $this->countStatus($groups->get($isMonthly ? $period->format('Y-m') : $period->toDateString()), 'completed'))->all(),
            'pending' => collect($periods)->map(fn (CarbonImmutable $period) => $this->countStatus($groups->get($isMonthly ? $period->format('Y-m') : $period->toDateString()), 'pending'))->all(),
            'keys' => $isMonthly ? collect($periods)->map(fn (CarbonImmutable $period) => $period->format('Y-m'))->all() : [],
        ];
    }

    protected function countStatus(?Collection $payments, string $status): int
    {
        return $payments?->filter(fn ($payment) => $payment->status?->getValue() === $status)->count() ?? 0;
    }
}

