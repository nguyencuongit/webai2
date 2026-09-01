<?php

namespace Botble\AiVideoGenerator\Repositories\Eloquent;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Repositories\Interfaces\CustomerCreditInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;

class CustomerCreditRepository extends RepositoriesAbstract implements CustomerCreditInterface
{
    public function findForCreditAdjustment(int $customerId): Customer
    {
        return Customer::query()
            ->lockForUpdate()
            ->findOrFail($customerId);
    }

    public function adjustCredits(Customer $customer, int $amount, bool $isAddition): Customer
    {
        $customer->credits_balance += $isAddition ? $amount : -$amount;
        $customer->save();

        return $customer->refresh();
    }
}
