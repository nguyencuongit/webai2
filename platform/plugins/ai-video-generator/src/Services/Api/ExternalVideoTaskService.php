<?php

namespace Botble\AiVideoGenerator\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ExternalVideoTaskService
{
    /**
     * Placeholder for forwarding a task to the third-party video API.
     */
    public function create(array $payload): string
    {
        $urlImage = $payload['url_image'];
        $urlVideo = $payload['url_video'];

        // The third-party request and task persistence will be added later.
        return (string) Str::uuid();
    }

    /**
     * Placeholder for processing a completed task sent by the third party.
     */
    public function receiveWebhook(array $payload): void
    {
        $webhookUrl = (string) config('ai-video-generator.general.external_webhook_url');

        if ($webhookUrl === '') {
            return;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('ai-video-generator.general.timeout', 60))
                ->post($webhookUrl, [
                    'status' => 'success',
                    'task_id' => $payload['task_id'],
                    'url_video' => $payload['url_video'],
                ]);

            Log::info('External video webhook response received.', [
                'task_id' => $payload['task_id'],
                'webhook_url' => $webhookUrl,
                'response_status' => $response->status(),
                'response_body' => $response->json() ?? $response->body(),
            ]);

            $response->throw();
        } catch (Throwable $exception) {
            Log::error('Cannot deliver external video webhook.', [
                'task_id' => $payload['task_id'],
                'webhook_url' => $webhookUrl,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
