<?php

namespace Botble\AiVideoGenerator\Jobs;

use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Services\AiGenerationTaskStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PollRoboNeoTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly string $taskId) {}

    public function handle(AiGenerationTaskStatusService $taskStatusService): void
    {
        $task = AiGenerationTask::query()->where('task_id', $this->taskId)->first();

        if (! $task || $this->isTerminal($task)) {
            return;
        }

        $payload = $task->payload ?? [];
        $attempt = (int) data_get($payload, 'roboneo.poll_attempts', 0) + 1;
        $payload['roboneo']['poll_attempts'] = $attempt;
        $task->update(['payload' => $payload]);

        try {
            $taskStatusService->pollRoboNeo($task);
            $task->refresh();
        } catch (Throwable $exception) {
            report($exception);
            $payload = $task->payload ?? [];
            $payload['roboneo']['last_poll_error'] = $exception->getMessage();
            $task->update(['payload' => $payload]);
        }

        if ($this->isTerminal($task)) {
            return;
        }

        if ($attempt >= $this->maxAttempts()) {
            $taskStatusService->markRoboNeoFailed($task);

            return;
        }

        self::dispatch($this->taskId)
            ->delay(now()->addSeconds($this->pollInterval()));
    }

    private function isTerminal(AiGenerationTask $task): bool
    {
        return $task->is_completed || in_array(strtoupper((string) $task->status), ['FAILED', 'CANCELLED', 'ERROR'], true);
    }

    private function pollInterval(): int
    {
        return max(1, (int) config('plugins.ai-video-generator.general.roboneo.motion.poll_interval_seconds', 5));
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('plugins.ai-video-generator.general.roboneo.motion.max_poll_attempts', 240));
    }
}
