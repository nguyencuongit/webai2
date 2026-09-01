<?php

use Botble\Theme\Facades\Theme;
use Botble\AiVideoGenerator\Http\Controllers\Fronts\CreditPackagePurchaseController;
use Botble\AiVideoGenerator\Http\Controllers\Fronts\MyVideosController;
use Botble\AiVideoGenerator\Http\Controllers\Fronts\VideoLabController;
use Botble\AiVideoGenerator\Models\AiContentPost;
use Illuminate\Support\Facades\Route;

// Change true to false to restore the normal theme routes below.
if (false) {
Route::get('/', function () {
    return response(<<<'HTML'
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Hệ thống đang bảo trì</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #0f172a; font-family: Arial, sans-serif; }
        main { max-width: 520px; margin: 24px; padding: 40px; text-align: center; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, .1); }
        h1 { margin: 0 0 16px; font-size: 28px; }
        p { margin: 0; color: #475569; font-size: 16px; line-height: 1.6; }
    </style>
</head>
<body>
    <main>
        <h1>Hệ thống đang bảo trì</h1>
        <p>Chúng tôi đang nâng cấp hệ thống. Vui lòng quay lại sau ít phút.</p>
    </main>
</body>
</html>
HTML
    );
})->name('public.home');

Route::any('/{path}', fn () => redirect('/'))->where('path', '.*');

return;
}

Theme::registerRoutes(function (): void {
    Route::get('test', function () {
        return Theme::scope('test.home')->render();
    })->name('public.test');

    Route::get('image-studio', fn () => Theme::scope('image')->render())->name('public.studio-image');
    Route::get('video-lab', [VideoLabController::class, 'index'])->name('public.video-lab');
    Route::get('my-videos', [MyVideosController::class, 'index'])->name('public.my-videos');
    Route::post('video-lab/media', [VideoLabController::class, 'uploadMedia'])->name('public.video-lab.media');
    Route::post('video-lab/generate', [VideoLabController::class, 'generate'])->name('public.video-lab.generate');
    Route::get('video-lab/tasks', [VideoLabController::class, 'history'])->name('public.video-lab.tasks');
    Route::get('video-lab/tasks/{taskId}/download', [VideoLabController::class, 'download'])->name('public.video-lab.task-download');
    Route::get('video-lab/tasks/{taskId}', [VideoLabController::class, 'status'])->name('public.video-lab.task-status');
    Route::delete('video-lab/tasks/{taskId}', [VideoLabController::class, 'destroy'])->name('public.video-lab.task-destroy');
    Route::get('credit-packages', [CreditPackagePurchaseController::class, 'index'])->name('public.credit-packages.index');
    Route::post('credit-package-purchases', [CreditPackagePurchaseController::class, 'start'])->name('public.credit-package-purchases.start');
    Route::get('credit-package-purchases/{payment:charge_id}/status', [CreditPackagePurchaseController::class, 'status'])->name('public.credit-package-purchases.status');
    Route::get('credit-package-purchases/{payment:charge_id}', [CreditPackagePurchaseController::class, 'payment'])->name('public.credit-package-purchases.payment');
    Route::redirect('top-up-credit', 'credit-packages')->name('public.top-up-credit');
});

Theme::routes();

Theme::registerRoutes(function (): void {
    Route::get('/', function () {
        $homePost = AiContentPost::query()
            ->where('status', true)
            ->where('display_location', 'home')
            ->latest('id')
            ->first();

        $introModalPost = AiContentPost::query()
            ->where('status', true)
            ->where('display_location', 'intro_modal')
            ->latest('id')
            ->first();

        return Theme::scope('home', compact('homePost', 'introModalPost'))->render();
    })->name('public.home');
});
