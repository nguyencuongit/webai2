<?php

namespace Botble\AiVideoGenerator\Http\Controllers\API;

use Botble\AiVideoGenerator\Api\MagnificWebhook;
use Botble\AiVideoGenerator\Services\AiGenerationWebhookService;
use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends BaseController
{
    public function __invoke(
        Request $request,
        MagnificWebhook $webhook,
        AiGenerationWebhookService $webhookService,
        ?string $provider = null
    ): JsonResponse
    {
        $data = $webhook->handle($request, $provider);

        Log::info('AI Video webhook received', $data);

        if ($data['signature_verified'] === false) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 401);
        }

        $task = $webhookService->handle($data);

        return response()->json([
            'success' => true,
            'message' => 'Webhook received',
            'signature_verified' => $data['signature_verified'],
            'task_id' => $task?->task_id,
            'status' => $task?->status,
        ]);
    }
}
