<?php

namespace Botble\AiVideoGenerator\Repositories\Interfaces;

use Botble\Payment\Models\Payment;

interface CreditPackagePaymentInterface
{
    public function lockCustomer(int $customerId): void;

    public function findCompletedForCreditGrant(string $chargeId): ?Payment;

    public function createPending(array $attributes, array $metadata): Payment;
}
