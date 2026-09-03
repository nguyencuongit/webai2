<?php

namespace Botble\AiVideoGenerator\Jobs;

use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\Api\ExternalVideoTaskService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SubmitExternalRoboNeoTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly string $taskId) {}

    public function handle(ExternalVideoTaskService $taskService, ExternalVideoTaskInterface $taskRepository): void
    {
        $task = $taskRepository->findByTaskId($this->taskId);

        if (! $task || $taskService->isTerminal($task)) {
            return;
        }

        $taskService->submitPendingRoboNeoTask($task);
    }
}
