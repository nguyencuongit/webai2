<?php

namespace App\Jobs;

use App\Models\MotionJob;
use App\Services\RoboNeo\Contracts\RoboNeoGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class PollMotionJob implements ShouldQueue
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

        $attempt = $job->poll_attempts + 1;

        try {
            $result = $gateway->poll($job);
            $job->update([
                'poll_attempts' => $attempt,
                'session_data' => $result['session_data'] ?? $job->session_data,
                'raw_status' => $result['raw'] ?? $job->raw_status,
            ]);

            if ($result['state'] === 'completed') {
                $job->update([
                    'status' => MotionJob::STATUS_COMPLETED,
                    'result_url' => $result['result_url'] ?? null,
                    'result_cover_url' => $result['cover_url'] ?? null,
                    'completed_at' => now(),
                ]);

                return;
            }

            if ($result['state'] === 'failed') {
                $job->update([
                    'status' => MotionJob::STATUS_FAILED,
                    'error_message' => $result['message'] ?? 'RoboNeo task failed.',
                ]);

                return;
            }
        } catch (Throwable $exception) {
            report($exception);
            $job->update([
                'poll_attempts' => $attempt,
                'error_message' => $exception->getMessage(),
            ]);
        }

        if ($attempt >= config('roboneo.motion.max_poll_attempts')) {
            $job->update([
                'status' => MotionJob::STATUS_FAILED,
                'error_code' => 'poll_timeout',
                'error_message' => 'Đã hết thời gian chờ kết quả RoboNeo.',
            ]);

            return;
        }

        PollMotionJob::dispatch($job->id)
            ->delay(now()->addSeconds(config('roboneo.motion.poll_interval_seconds')));
    }
}
