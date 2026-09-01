<?php

namespace App\Jobs;

use App\Models\MotionJob;
use App\Services\RoboNeo\Contracts\RoboNeoGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SubmitMotionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly string $motionJobId) {}

    public function handle(RoboNeoGateway $gateway): void
    {
        $job = MotionJob::query()->findOrFail($this->motionJobId);

        if ($job->isTerminal()) {
            return;
        }

        try {
            $submission = $gateway->submit($job);
            $job->update([
                ...$submission,
                'status' => MotionJob::STATUS_PROCESSING,
                'submitted_at' => $job->submitted_at ?? now(),
                'error_code' => null,
                'error_message' => null,
            ]);
            PollMotionJob::dispatch($job->id)
                ->delay(now()->addSeconds(config('roboneo.motion.poll_interval_seconds')));
        } catch (Throwable $exception) {
            report($exception);
            $job->update([
                'status' => MotionJob::STATUS_FAILED,
                'error_code' => (string) $exception->getCode(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
