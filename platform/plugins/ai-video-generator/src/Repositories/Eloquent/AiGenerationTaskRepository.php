<?php

namespace Botble\AiVideoGenerator\Repositories\Eloquent;

use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Support\Carbon;

class AiGenerationTaskRepository extends RepositoriesAbstract implements AiGenerationTaskInterface
{
    public function storeFromResponse(array $response, array|string $payload, ?int $customerId = null)
    {
        $data = $response['data'] ?? $response;
        $taskId = $data['task_id'] ?? null;

        if (! $taskId) {
            return null;
        }

        $status = $data['status'] ?? 'CREATED';
        $isCompleted = $this->isCompletedStatus($status);

        return $this->createOrUpdate([
            'customer_id' => $customerId,
            'task_id' => $taskId,
            'status' => $status,
            'is_completed' => $isCompleted,
            'generated' => $data['generated'] ?? null,
            'has_nsfw' => $data['has_nsfw'] ?? null,
            'payload' => is_array($payload) ? $payload : ['prompt' => $payload],
            'completed_at' => $isCompleted ? Carbon::now() : null,
        ], [
            'task_id' => $taskId,
        ]);
    }

    protected function isCompletedStatus(?string $status): bool
    {
        return strtoupper((string) $status) === 'COMPLETED';
    }

    public function updateFromWebhook(array $webhookData)
    {
        $task = $webhookData['task'] ?? $webhookData;
        $taskId = $task['task_id'] ?? null;

        if (! $taskId) {
            return null;
        }

        $status = $task['status'] ?? null;
        $isCompleted = $this->isCompletedStatus($status);

        $record = $this->getFirstBy(['task_id' => $taskId]);

        $data = [
            'task_id' => $taskId,
            'status' => $status,
            'is_completed' => $isCompleted,
            'generated' => $task['generated'] ?? [],
            'has_nsfw' => $task['has_nsfw'] ?? [],
            'completed_at' => $isCompleted ? Carbon::now() : null,
        ];

        if (! $record) {
            $data['payload'] = $webhookData['payload'] ?? $task;
        }

        return $this->createOrUpdate($data, [
            'task_id' => $taskId,
        ]);
    }
}
