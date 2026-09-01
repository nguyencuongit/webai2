<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use FriendsOfBotble\SePay\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function __invoke(WebhookRequest $request): JsonResponse
    {
        Log::info('SePay webhook received.', [
            'transaction_id' => $request->input('id'),
            'content' => $request->input('content'),
            'transfer_amount' => $request->input('transferAmount'),
            'transfer_type' => $request->input('transferType'),
        ]);


        do_action('payment_before_making_api_request', SEPAY_PAYMENT_METHOD_NAME, []);

        $content = (string) $request->input('content');
        $normalizedContent = preg_replace('/[^A-Z0-9]/', '', strtoupper($content));

        $payment = Payment::query()
            ->where('payment_channel', SEPAY_PAYMENT_METHOD_NAME)
            ->where('amount', $request->input('transferAmount'))
            ->whereIn('status', [PaymentStatusEnum::PENDING, PaymentStatusEnum::COMPLETED])
            ->get()
            ->first(function (Payment $payment) use ($normalizedContent): bool {
                $normalizedChargeId = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $payment->charge_id));

                return filled($normalizedChargeId) && str_contains($normalizedContent, $normalizedChargeId);
            });

        if (! $payment) {
            Log::warning('SePay webhook payment not found.', [
                'content' => $request->input('content'),
                'transfer_amount' => $request->input('transferAmount'),
            ]);

            return response()->json(['success' => false]);
        }

        do_action('payment_before_making_api_request', SEPAY_PAYMENT_METHOD_NAME, []);

        if ($payment->status == PaymentStatusEnum::COMPLETED) {
            return response()->json(['success' => true]);
        }

        $payment->update([
            'status' => PaymentStatusEnum::COMPLETED,
            'metadata' => array_merge($payment->metadata ?? [], [
                'sepay_webhook' => $request->input(),
            ]),
        ]);

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'charge_id' => $payment->charge_id,
            // Credit-package payments do not belong to an Ecommerce order. Passing
            // an empty string makes Ecommerce try to process an invalid order ID.
            'order_id' => filled($payment->order_id) ? $payment->order_id : null,
            'customer_id' => $payment->customer_id,
            'customer_type' => $payment->customer_type,
            'payment_channel' => $payment->payment_channel?->getValue(),
            'status' => PaymentStatusEnum::COMPLETED,
            'amount' => $payment->amount,
        ], $request);

        do_action('payment_after_api_response', SEPAY_PAYMENT_METHOD_NAME, [], $request->all());

        Log::info('SePay webhook payment completed.', [
            'charge_id' => $payment->charge_id,
            'payment_id' => $payment->getKey(),
        ]);

        return response()->json(['success' => true]);
    }
}
