<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Repositories\Interfaces\CustomerCreditInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerCreditService
{
    public const OPERATION_CREDIT = 'credit';

    public const OPERATION_DEBIT = 'debit';

    public function __construct(protected CustomerCreditInterface $customerCreditRepository)
    {
    }

    public function credit(int $customerId, int $amount, int $actorId): Customer
    {
        return $this->adjust($customerId, $amount, $actorId, self::OPERATION_CREDIT);
    }

    public function debit(int $customerId, int $amount, int $actorId): Customer
    {
        return $this->adjust($customerId, $amount, $actorId, self::OPERATION_DEBIT);
    }

    public function adjust(int $customerId, int $amount, int $actorId, string $operation): Customer
    {
        $this->validateAdjustment($customerId, $amount, $actorId, $operation);

        return DB::transaction(function () use ($customerId, $amount, $operation): Customer {
            $customer = $this->customerCreditRepository->findForCreditAdjustment($customerId);
            $isAddition = $operation === self::OPERATION_CREDIT;

            if (! $isAddition && $customer->credits_balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Số dư credit không đủ để thực hiện giao dịch.'],
                ]);
            }

            return $this->customerCreditRepository->adjustCredits($customer, $amount, $isAddition);
        });
    }

    protected function validateAdjustment(int $customerId, int $amount, int $actorId, string $operation): void
    {
        $errors = [];

        if ($customerId <= 0) {
            $errors['customer_id'] = ['Khách hàng không hợp lệ.'];
        }

        if ($actorId <= 0) {
            $errors['actor_id'] = ['Người thực hiện không hợp lệ.'];
        }

        if ($amount <= 0) {
            $errors['amount'] = ['Số credit cộng hoặc trừ phải lớn hơn 0.'];
        }

        if (! in_array($operation, [self::OPERATION_CREDIT, self::OPERATION_DEBIT], true)) {
            $errors['operation'] = ['Loại giao dịch không hợp lệ.'];
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
