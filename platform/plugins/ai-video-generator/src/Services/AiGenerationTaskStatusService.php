<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;

class AiGenerationTaskStatusService
{
    public function __construct(protected AiGenerationTaskInterface $taskRepository)
    {
    }

    public function find(string $taskId): ?array
    {
        $task = $this->taskRepository->getFirstBy(['task_id' => $taskId]);

        if (! $task instanceof AiGenerationTask) {
            return null;
        }

        $customerId = auth('customer')->id();

        if ($task->customer_id && (int) $task->customer_id !== (int) $customerId) {
            return null;
        }

        return [
            'task_id' => $task->task_id,
            'status' => $task->status,
            'is_completed' => (bool) $task->is_completed,
            'generated' => $task->generated ?: [],
            'has_nsfw' => $task->has_nsfw ?: [],
            'completed_at' => $task->completed_at?->toDateTimeString(),
        ];
    }
}
