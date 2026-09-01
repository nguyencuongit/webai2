<?php

namespace Botble\Payment\Services;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Botble\Payment\Models\PaymentLog;

class PaymentLogBackfillService
{
    public function backfillCompletedSePayPayments(?string $ipAddress = null): array
    {
        $counts = [
            'scanned' => 0,
            'created' => 0,
            'linked' => 0,
        ];

        Payment::query()
            ->where('payment_channel', defined('SEPAY_PAYMENT_METHOD_NAME') ? SEPAY_PAYMENT_METHOD_NAME : 'sepay')
            ->where('status', PaymentStatusEnum::COMPLETED)
            ->orderBy('id')
            ->chunkById(100, function ($payments) use (&$counts, $ipAddress): void {
                foreach ($payments as $payment) {
                    $counts['scanned']++;

                    $log = $this->findExistingLog($payment);

                    if ($log) {
                        if (! $log->payment_id || ! $log->charge_id) {
                            $log->fill([
                                'payment_id' => $payment->getKey(),
                                'charge_id' => $payment->charge_id,
                            ])->save();
                            $counts['linked']++;
                        }

                        continue;
                    }

                    $transactionId = $payment->sepay_webhook_transaction_id ?: $payment->sepay_api_transaction_id;
                    $log = new PaymentLog([
                        'payment_id' => $payment->getKey(),
                        'charge_id' => $payment->charge_id,
                        'payment_method' => defined('SEPAY_PAYMENT_METHOD_NAME') ? SEPAY_PAYMENT_METHOD_NAME : 'sepay',
                        'request' => [
                            'source' => 'backfill',
                            'payment_id' => $payment->getKey(),
                            'charge_id' => $payment->charge_id,
                            'note' => 'Bù nhật ký cho giao dịch SePay đã hoàn thành trước khi bật ghi webhook.',
                        ],
                        'response' => [
                            'success' => true,
                            'status' => PaymentStatusEnum::COMPLETED,
                            'payment_id' => $payment->getKey(),
                            'charge_id' => $payment->charge_id,
                            'provider_transaction_id' => $transactionId,
                            'source' => 'backfill',
                        ],
                        'ip_address' => $ipAddress ?: '127.0.0.1',
                    ]);
                    $log->created_at = $payment->created_at;
                    $log->updated_at = $payment->updated_at;
                    $log->save();

                    $counts['created']++;
                }
            });

        return $counts;
    }

    protected function findExistingLog(Payment $payment): ?PaymentLog
    {
        return PaymentLog::query()
            ->where(function ($query) use ($payment): void {
                $query->where('payment_id', $payment->getKey())
                    ->orWhere('charge_id', $payment->charge_id)
                    ->orWhere('request', 'LIKE', '%' . $payment->charge_id . '%')
                    ->orWhere('response', 'LIKE', '%' . $payment->charge_id . '%');
            })
            ->first();
    }
}

