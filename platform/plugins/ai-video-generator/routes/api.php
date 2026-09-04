<?php

use Botble\AiVideoGenerator\Http\Controllers\API\ExternalVideoTaskController;
use Botble\AiVideoGenerator\Http\Middleware\ExternalApiToken;
use Botble\Api\Http\Middleware\ApiEnabledMiddleware;
use Botble\Api\Http\Middleware\ForceJsonResponseMiddleware;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'api/v1',
    'namespace' => 'Botble\AiVideoGenerator\Http\Controllers\API',
], function (): void {
    Route::post('ai-video-generator/videos', 'VideoController@store')->middleware('throttle:20,1');
    Route::get('ai-video-generator/videos/{id}', 'VideoController@show')->middleware('throttle:60,1');
});

// External clients authenticate with their dedicated token header. Keep these
// routes out of Botble's `api` group so a configured global X-API-KEY does not
// become an undocumented second credential.
Route::group([
    'prefix' => 'api/v1',
    'namespace' => 'Botble\AiVideoGenerator\Http\Controllers\API',
], function (): void {
    Route::prefix('ai-video-generator/external')
        ->middleware([
            ApiEnabledMiddleware::class,
            ForceJsonResponseMiddleware::class,
            ExternalApiToken::class,
            'throttle:20,1',
        ])
        ->group(function (): void {
            Route::post('tasks', [ExternalVideoTaskController::class, 'store'])
                ->name('ai-video-generator.external.tasks.store');
            Route::post('webhook', [ExternalVideoTaskController::class, 'webhook'])
                ->name('ai-video-generator.external.webhook');
        });

    Route::post('ai-video-generator/webhook/{provider?}', 'WebhookController')
        ->name('ai-video-generator.webhook');
});
