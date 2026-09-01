<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Fronts;

use Botble\AiVideoGenerator\Models\AiVideoCreditPackage;
use Botble\AiVideoGenerator\Models\Customer;
use Botble\AiVideoGenerator\Services\CreditPackagePurchaseService;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Payment\Models\Payment;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Theme\Facades\Theme;
use FriendsOfBotble\SePay\Services\BankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CreditPackagePurchaseController extends BaseController
{
    public function index()
    {
        $creditPackages = AiVideoCreditPackage::query()
            ->orderBy('price')
            ->get();
        $customer = auth('customer')->user();

        return Theme::scope('credit-packages', compact('creditPackages', 'customer'))->render();
    }

    public function start(Request $request, CreditPackagePurchaseService $purchaseService): RedirectResponse|JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'login_url' => route('ai-video-generator.login', ['redirect' => url()->current()]),
                ], 401);
            }

            return redirect()->route('ai-video-generator.login', ['redirect' => url()->current()]);
        }

        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'exists:ai_video_credit_packages,id'],
        ]);

        $payment = $purchaseService->start($customer, $validated['package_id']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'name' => data_get($payment->metadata, 'package_name') ?: 'Gói Credit',
                    'code' => data_get($payment->metadata, 'package_code'),
                    'credits' => data_get($payment->metadata, 'credits'),
                    'price' => number_format((float) $payment->amount, 0, ',', '.'),
                    'qr_url' => $this->paymentQrCodeUrl($payment),
                ],
            ]);
        }

        return redirect()->route('public.credit-package-purchases.payment', $payment->charge_id);
    }

    public function payment(Payment $payment)
    {
        $customer = auth('customer')->user();

        if (! $customer) {
            return redirect()->route('ai-video-generator.login', ['redirect' => url()->current()]);
        }

        if (! $this->isCustomerCreditPackagePayment($payment, $customer)) {
            abort(404);
        }

        $qrCodeUrl = $this->paymentQrCodeUrl($payment);
        $sepayIsReady = $qrCodeUrl !== null;

        $isCompleted = $payment->status?->getValue() === PaymentStatusEnum::COMPLETED;

        return Theme::scope('credit-payment', compact('payment', 'customer', 'qrCodeUrl', 'sepayIsReady', 'isCompleted'))->render();
    }

    public function status(Payment $payment): JsonResponse
    {
        $customer = auth('customer')->user();

        if (! $customer || ! $this->isCustomerCreditPackagePayment($payment, $customer)) {
            abort(404);
        }

        return response()->json([
            'completed' => $payment->status?->getValue() === PaymentStatusEnum::COMPLETED,
        ]);
    }

    protected function isCustomerCreditPackagePayment(Payment $payment, Customer $customer): bool
    {
        return (int) $payment->customer_id === (int) $customer->getKey()
            && $payment->customer_type === Customer::class
            && data_get($payment->metadata, 'type') === 'credit_package_purchase';
    }

    protected function paymentQrCodeUrl(Payment $payment): ?string
    {
        $accountNumber = setting('payment_sepay_bank_account_number');
        $bankShortName = setting('payment_sepay_bank_short_name');

        if (setting('payment_sepay_status') != 1 || blank($accountNumber) || blank($bankShortName)) {
            return null;
        }

        return app(BankService::class)->getQrCodeUrl(
            $accountNumber,
            $bankShortName,
            (float) $payment->amount,
            $payment->charge_id
        );
    }
}
