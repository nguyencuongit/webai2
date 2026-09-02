<?php

namespace Botble\AiVideoGenerator\Jobs;

use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\Api\ExternalVideoTaskService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PollExternalRoboNeoTask implements ShouldQueue
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

        $payload = $task->payload ?? [];
        $attempt = (int) data_get($payload, 'roboneo.poll_attempts', 0) + 1;
        $payload['roboneo']['poll_attempts'] = $attempt;
        $task->update(['payload' => $payload]);

        try {
            $taskService->pollRoboNeo($task);
            $task->refresh();
        } catch (Throwable $exception) {
            report($exception);
            $payload = $task->payload ?? [];
            $payload['roboneo']['last_poll_error'] = $exception->getMessage();
            $task->update(['payload' => $payload]);
            $task->refresh();
        }

        if ($taskService->isTerminal($task)) {
            return;
        }

        if ($attempt >= $taskService->maxPollAttempts()) {
            $taskService->markPollingTimeout($task);

            return;
        }

        self::dispatch($this->taskId)
            ->delay(now()->addSeconds($taskService->pollInterval()));
    }
}
