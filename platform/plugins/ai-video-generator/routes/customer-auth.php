<?php

use Botble\AiVideoGenerator\Http\Controllers\Auth\LoginController;
use Botble\AiVideoGenerator\Http\Controllers\Auth\RegisterController;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

Theme::registerRoutes(function (): void {
    Route::prefix('ai-video')
        ->name('ai-video-generator.')
        ->group(function (): void {
            Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
            Route::post('login', [LoginController::class, 'login'])->name('login.post');
            Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
            Route::post('register', [RegisterController::class, 'register'])->name('register.post');
            Route::get('logout', [LoginController::class, 'logout'])->name('logout');
        });

    if (! function_exists('is_plugin_active') || ! is_plugin_active('ecommerce')) {
        Route::name('customer.')->group(function (): void {
            Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
            Route::post('login', [LoginController::class, 'login'])->name('login.post');
            Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
            Route::post('register', [RegisterController::class, 'register'])->name('register.post');
            Route::get('logout', [LoginController::class, 'logout'])->name('logout');
        });
    }
});
