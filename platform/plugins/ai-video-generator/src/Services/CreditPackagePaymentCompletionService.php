<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Repositories\Interfaces\CreditPackagePaymentInterface;
use Illuminate\Support\Facades\DB;

class CreditPackagePaymentCompletionService
{
    public function __construct(
        protected CreditPackagePaymentInterface $paymentRepository,
        protected CustomerCreditService $customerCreditService
    ) {
    }

    public function handle(string $chargeId): void
    {
        DB::transaction(function () use ($chargeId): void {
            $payment = $this->paymentRepository->findCompletedForCreditGrant($chargeId);

            if (! $payment || data_get($payment->metadata, 'credit_granted_at')) {
                return;
            }

            $customerId = (int) $payment->customer_id;
            $credits = (int) data_get($payment->metadata, 'credits');

            if ($customerId <= 0 || $credits <= 0 || $payment->customer_type !== Customer::class) {
                return;
            }

            $this->customerCreditService->credit($customerId, $credits, $customerId);

            $metadata = $payment->metadata ?? [];
            $metadata['credit_granted_at'] = now()->toISOString();
            $payment->metadata = $metadata;
            $payment->save();
        });
    }
}
