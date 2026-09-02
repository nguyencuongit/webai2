<?php

namespace Botble\AiVideoGenerator\Http\Controllers\API;

use Botble\AiVideoGenerator\Services\Api\ExternalVideoTaskService;
use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExternalVideoTaskController extends BaseController
{
    public function store(Request $request, ExternalVideoTaskService $taskService): JsonResponse
    {
        try {
            $payload = $request->validate([
                'url_image' => ['required', 'url'],
                'url_video' => ['required', 'url'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'task_id' => $taskService->create($payload),
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request data',
                'errors' => $exception->errors(),
            ], 422);
        } catch (Throwable $exception) {
            Log::error('Cannot create external video task.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to create task',
            ], 500);
        }
    }

    public function webhook(Request $request, ExternalVideoTaskService $taskService): JsonResponse
    {
        try {
            $payload = $request->validate([
                'status' => ['required'],
                'task_id' => ['required', 'string'],
                'url_video' => ['nullable', 'url'],
                'error' => ['nullable'],
            ]);

            $taskService->receiveWebhook($payload);

            return response()->json([
                'success' => true,
                'message' => 'Webhook received',
                'task_id' => $payload['task_id'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook data',
                'errors' => $exception->errors(),
            ], 422);
        } catch (Throwable $exception) {
            Log::error('Cannot process external video webhook.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process webhook',
            ], 500);
        }
    }
}
