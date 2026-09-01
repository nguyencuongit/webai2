<?php

namespace Botble\AiVideoGenerator\Repositories\Eloquent;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Repositories\Interfaces\CreditPackagePaymentInterface;
use Botble\Payment\Models\Payment;

class CreditPackagePaymentRepository implements CreditPackagePaymentInterface
{
    public function lockCustomer(int $customerId): void
    {
        Customer::query()->lockForUpdate()->findOrFail($customerId);
    }

    public function findCompletedForCreditGrant(string $chargeId): ?Payment
    {
        return Payment::query()
            ->where('charge_id', $chargeId)
            ->where('payment_channel', SEPAY_PAYMENT_METHOD_NAME)
            ->where('status', 'completed')
            ->where('metadata->type', 'credit_package_purchase')
            ->lockForUpdate()
            ->first();
    }

    public function createPending(array $attributes, array $metadata): Payment
    {
        $payment = Payment::query()->create($attributes);
        $payment->metadata = $metadata;
        $payment->save();

        return $payment->refresh();
    }
}
