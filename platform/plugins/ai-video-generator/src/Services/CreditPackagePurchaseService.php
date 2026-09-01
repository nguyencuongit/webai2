<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Models\AiVideoCreditPackage;
use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Repositories\Interfaces\CreditPackagePaymentInterface;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreditPackagePurchaseService
{
    public function __construct(protected CreditPackagePaymentInterface $paymentRepository)
    {
    }

    public function start(Customer $customer, int $packageId): Payment
    {
        if ($packageId <= 0) {
            throw ValidationException::withMessages([
                'package_id' => ['Gói Credit không hợp lệ.'],
            ]);
        }

        $package = AiVideoCreditPackage::query()->findOrFail($packageId);

        if ($package->price <= 0 || $package->credits <= 0) {
            throw ValidationException::withMessages([
                'package_id' => ['Gói Credit này chưa sẵn sàng để mua.'],
            ]);
        }

        return DB::transaction(function () use ($customer, $package): Payment {
            $this->paymentRepository->lockCustomer($customer->getKey());

            $packageCode = Str::upper((string) preg_replace('/[^a-zA-Z0-9]/', '', $package->code));
            $chargeId = Str::substr($packageCode ?: 'GOI', 0, 10) . '-' . Str::upper(Str::random(5));

            return $this->paymentRepository->createPending([
                'amount' => $package->price,
                'payment_fee' => 0,
                'currency' => 'VND',
                'user_id' => 0,
                'charge_id' => $chargeId,
                'payment_channel' => SEPAY_PAYMENT_METHOD_NAME,
                'description' => 'Mua ' . number_format($package->credits, 0, ',', '.') . ' Credit - ' . $package->name,
                'status' => PaymentStatusEnum::PENDING,
                'order_id' => null,
                'payment_type' => 'confirm',
                'customer_id' => $customer->getKey(),
                'customer_type' => Customer::class,
            ], [
                'type' => 'credit_package_purchase',
                'package_id' => $package->getKey(),
                'package_code' => $package->code,
                'package_name' => $package->name,
                'credits' => (int) $package->credits,
            ]);
        });
    }
}
