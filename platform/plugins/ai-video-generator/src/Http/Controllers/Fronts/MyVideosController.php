<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Fronts;

use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyVideosController extends BaseController
{
    public function index()
    {
        return Theme::scope('my-videos')->render();
    }

    public function tasks(Request $request): JsonResponse
    {
        $customerId = (int) auth('customer')->id();

        if ($customerId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to view your videos.',
            ], 401);
        }

        $filter = (string) $request->input('filter', 'all');
        $search = trim((string) $request->input('search', ''));
        $query = AiGenerationTask::query()
            ->where('customer_id', $customerId)
            ->latest('id');

        match ($filter) {
            'queue' => $query->whereIn('status', ['PENDING', 'QUEUED']),
            'processing' => $query->whereIn('status', ['PROCESSING', 'RUNNING']),
            'completed' => $query->where('status', 'COMPLETED'),
            'failed' => $query->whereIn('status', ['FAILED', 'CANCELLED', 'ERROR']),
            default => null,
        };

        if ($search !== '') {
            $query->where('task_id', 'like', "%{$search}%");
        }

        $tasks = $query->paginate(12);

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks->getCollection()
                    ->map(fn (AiGenerationTask $task) => $this->serializeTask($task))
                    ->values(),
                'meta' => [
                    'total' => $tasks->total(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                ],
            ],
        ]);
    }

    private function serializeTask(AiGenerationTask $task): array
    {
        $generated = array_values(array_filter($task->generated ?? []));
        $firstMedia = $generated[0] ?? null;
        $payload = $task->payload ?? [];
        $error = is_array($firstMedia) && isset($firstMedia['message'])
            ? $firstMedia
            : data_get($payload, 'roboneo.error');

        return [
            'task_id' => $task->task_id,
            'status' => strtoupper((string) $task->status),
            'created_at' => $task->created_at?->toISOString(),
            'media_url' => is_array($firstMedia) ? ($firstMedia['url'] ?? null) : $firstMedia,
            'thumbnail_url' => is_array($firstMedia) ? ($firstMedia['thumbnail'] ?? null) : null,
            'error' => is_array($error) ? [
                'code' => $error['code'] ?? null,
                'message' => $error['message'] ?? null,
            ] : null,
        ];
    }
}
