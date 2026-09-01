<?php

use App\Http\Controllers\MotionController;
use App\Http\Controllers\RoboNeoAccountController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/motion');

Route::get('/motion', [MotionController::class, 'index'])->name('motion.index');
Route::post('/motion/quote', [MotionController::class, 'store'])->name('motion.store');
Route::get('/motion/{motionJob}', [MotionController::class, 'show'])->name('motion.show');
Route::post('/motion/{motionJob}/confirm', [MotionController::class, 'confirm'])->name('motion.confirm');
Route::post('/motion/{motionJob}/cancel', [MotionController::class, 'cancel'])->name('motion.cancel');
Route::get('/motion/{motionJob}/status', [MotionController::class, 'status'])->name('motion.status');
Route::get('/motion/{motionJob}/manifest', [MotionController::class, 'manifest'])->name('motion.manifest');

Route::resource('roboneo-accounts', RoboNeoAccountController::class)
    ->parameters(['roboneo-accounts' => 'roboNeoAccount'])
    ->only(['index', 'store', 'edit', 'update', 'destroy']);
Route::post('/roboneo-accounts/{roboNeoAccount}/default', [RoboNeoAccountController::class, 'setDefault'])
    ->name('roboneo-accounts.default');
Route::post('/roboneo-accounts/{roboNeoAccount}/verify', [RoboNeoAccountController::class, 'verify'])
    ->name('roboneo-accounts.verify');
