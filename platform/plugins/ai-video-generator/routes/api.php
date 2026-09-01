<?php

use Botble\AiVideoGenerator\Http\Controllers\API\ExternalVideoTaskController;
use Botble\AiVideoGenerator\Http\Middleware\ExternalApiToken;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'api/v1',
    'namespace' => 'Botble\AiVideoGenerator\Http\Controllers\API',
], function (): void {
    Route::post('ai-video-generator/videos', 'VideoController@store')->middleware('throttle:20,1');
    Route::get('ai-video-generator/videos/{id}', 'VideoController@show')->middleware('throttle:60,1');

    Route::prefix('ai-video-generator/external')
        ->middleware([ExternalApiToken::class, 'throttle:20,1'])
        ->group(function (): void {
            Route::post('tasks', [ExternalVideoTaskController::class, 'store'])
                ->name('ai-video-generator.external.tasks.store');
            Route::post('webhook', [ExternalVideoTaskController::class, 'webhook'])
                ->name('ai-video-generator.external.webhook');
        });
});

// External providers cannot send Botble's global X-API-KEY. Keep the webhook
// out of the `api` middleware group; its provider signature is verified by the
// webhook controller instead.
Route::group([
    'prefix' => 'api/v1',
    'namespace' => 'Botble\AiVideoGenerator\Http\Controllers\API',
], function (): void {
    Route::post('ai-video-generator/webhook/{provider?}', 'WebhookController')
        ->name('ai-video-generator.webhook');
});
