<?php

use Botble\AiVideoGenerator\Api\MagnificApi;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$api = new MagnificApi();
$client = new ReflectionMethod($api, 'client');
$client->setAccessible(true);

try {
    $response = $client->invoke($api)->post('ai/image-to-video/kling-v2-5-pro', [
        'prompt' => 'API credit availability check',
        'duration' => '5',
    ]);

    echo json_encode([
        'status' => $response->status(),
        'body' => $response->json() ?? $response->body(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $exception) {
    echo json_encode([
        'exception' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
