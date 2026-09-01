<?php

use Botble\Base\Facades\AdminHelper;
use FriendsOfBotble\SePay\Http\Controllers\ApiTokenController;
use FriendsOfBotble\SePay\Http\Controllers\OAuthController;
use FriendsOfBotble\SePay\Http\Controllers\SePayController;
use FriendsOfBotble\SePay\Http\Controllers\WebhookController;
use FriendsOfBotble\SePay\Http\Middleware\SePayProtector;
use Illuminate\Support\Facades\Route;

Route::prefix('sepay')->name('sepay.')->group(function () {
    Route::post('oauth/disconnect', [OAuthController::class, 'disconnect'])
        ->name('oauth.disconnect');

    Route::post('webhook', [WebhookController::class, '__invoke'])
        ->name('webhook')
        ->middleware(SePayProtector::class);

    Route::post('transactions/check', [SePayController::class, 'checkTransaction'])
        ->name('transactions.check');

    AdminHelper::registerRoutes(function () {
        Route::post('api-token/connect', [ApiTokenController::class, 'connect'])
            ->name('api-token.connect');

        Route::get('bank-sub-accounts', [SePayController::class, 'bankSubAccounts'])
            ->name('bank-sub-accounts');

        Route::get('payment-codes', [SePayController::class, 'paymentCodes'])
            ->name('payment-codes');
    });
});
