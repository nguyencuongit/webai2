<?php

namespace Botble\AiVideoGenerator\Repositories\Interfaces;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\Support\Repositories\Interfaces\RepositoryInterface;

interface CustomerCreditInterface extends RepositoryInterface
{
    public function findForCreditAdjustment(int $customerId): Customer;

    public function adjustCredits(Customer $customer, int $amount, bool $isAddition): Customer;
}
