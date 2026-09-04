<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo;

use Botble\AiVideoGenerator\Models\ExternalVideoTask;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\ExternalRoboNeoTaskSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalRoboNeoWatchdog
{
    public function __construct(protected ExternalRoboNeoTaskSource $source) {}

    public function recover(int $limit = 500): int
    {
        $staleSeconds = max(
            15,
            (int) config('plugins.ai-video-generator.general.roboneo.recovery_stale_seconds', 60),
        );
        $tasks = ExternalVideoTask::query()
            ->whereIn('status', ['PROCESSING', 'CALLBACK_PENDING'])
            ->where('updated_at', '<=', now()->subSeconds($staleSeconds))
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        foreach ($tasks as $task) {
            $payload = is_array($task->payload) ? $task->payload : [];
            $hasProviderTask = filled(data_get($payload, 'roboneo.task_id'));

            if ($hasProviderTask && $this->deadlineHasPassed(data_get($payload, 'roboneo.processing_deadline_at'))) {
                try {
                    $this->source->fail(
                        $task,
                        'POLLING_TIMEOUT',
                        'RoboNeo did not return a completed result before the external task deadline.',
                    );
                } catch (Throwable $exception) {
                    Log::warning('RoboNeo watchdog recorded a timeout but its callback is still pending.', [
                        'task_id' => $task->task_id,
                        'exception' => $exception::class,
                    ]);
                }

                continue;
            }

            if (strtoupper((string) $task->status) === 'CALLBACK_PENDING' || ! $hasProviderTask) {
                $this->source->dispatchSubmission((string) $task->task_id);

                continue;
            }

            $this->source->dispatchPolling((string) $task->task_id, 0);
        }

        return $tasks->count();
    }

    private function deadlineHasPassed(mixed $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            return Carbon::parse($value)->isPast();
        } catch (Throwable) {
            return false;
        }
    }
}
