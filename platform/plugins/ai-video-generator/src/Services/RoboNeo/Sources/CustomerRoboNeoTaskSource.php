<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo\Sources;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Jobs\PollRoboNeoTask;
use Botble\AiVideoGenerator\Jobs\SubmitCustomerRoboNeoTask;
use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\AiVideoGenerator\Services\CustomerCreditService;
use Botble\AiVideoGenerator\Services\RoboNeo\Contracts\RoboNeoTaskSource;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CustomerRoboNeoTaskSource implements RoboNeoTaskSource
{
    public function __construct(
        protected AiGenerationTaskInterface $taskRepository,
        protected MotionVideoTrimmer $videoTrimmer,
        protected CustomerCreditService $customerCreditService,
    ) {}

    public function key(): string
    {
        return 'customer';
    }

    public function find(string $taskId): ?Model
    {
        $task = $this->taskRepository->getFirstBy(['task_id' => $taskId]);

        return $task instanceof AiGenerationTask ? $task : null;
    }

    public function prepareInputs(Model $task): array
    {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $existingImage = (string) data_get($payload, 'roboneo.local_inputs.image');
        $existingVideo = (string) data_get($payload, 'roboneo.local_inputs.video');

        if ($this->isNonEmptyFile($existingImage) && $this->isNonEmptyFile($existingVideo)) {
            return ['image' => $existingImage, 'video' => $existingVideo];
        }

        $imagePath = $this->localPublicPath((string) ($payload['image_url'] ?? ''));
        $sourceVideoPath = $this->localPublicPath((string) ($payload['video_url'] ?? ''));
        $videoPath = $this->videoTrimmer->trim($sourceVideoPath);
        $payload['roboneo']['local_inputs'] = [
            'image' => $imagePath,
            'source_video' => $sourceVideoPath,
            'video' => $videoPath,
            'prepared_at' => now()->toISOString(),
        ];
        $task->update(['payload' => $payload]);

        return ['image' => $imagePath, 'video' => $videoPath];
    }

    public function cleanupInputs(Model $task): void
    {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $sourceVideo = (string) data_get($payload, 'roboneo.local_inputs.source_video');
        $trimmedVideo = (string) data_get($payload, 'roboneo.local_inputs.video');

        if ($trimmedVideo !== '' && $trimmedVideo !== $sourceVideo) {
            File::delete($trimmedVideo);
        }

        unset($payload['roboneo']['local_inputs']);
        $task->update(['payload' => $payload]);
    }

    public function dispatchSubmission(string $taskId, ?Carbon $at = null): void
    {
        $job = SubmitCustomerRoboNeoTask::dispatch($taskId);

        if ($at) {
            $job->delay($at);
        }
    }

    public function dispatchPolling(string $taskId, int $delaySeconds): void
    {
        PollRoboNeoTask::dispatch($taskId)->delay(now()->addSeconds($delaySeconds));
    }

    public function complete(Model $task, array $storedVideo): void
    {
        if ($this->isTerminal($task)) {
            return;
        }

        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $payload['roboneo']['completed_at'] = now()->toISOString();
        $task->update([
            'status' => 'COMPLETED',
            'is_completed' => true,
            'generated' => [[
                'url' => $storedVideo['url'],
                'r2_key' => $storedVideo['key'],
            ]],
            'has_nsfw' => [],
            'payload' => $payload,
            'completed_at' => now(),
        ]);
    }

    public function fail(Model $task, string $code, string $message): void
    {
        if ($task->exists && $task->getKey()) {
            DB::transaction(function () use ($task, $code, $message): void {
                $lockedTask = AiGenerationTask::query()->lockForUpdate()->find($task->getKey());

                if ($lockedTask) {
                    $this->failAndRefund($lockedTask, $code, $message);
                }
            });

            return;
        }

        $this->failAndRefund($task, $code, $message);
    }

    public function resumePendingCompletion(Model $task): bool
    {
        return false;
    }

    public function isTerminal(Model $task): bool
    {
        return (bool) ($task->is_completed ?? false)
            || in_array(strtoupper((string) $task->status), ['COMPLETED', 'FAILED', 'CANCELLED', 'ERROR'], true);
    }

    private function failAndRefund(Model $task, string $code, string $message): void
    {
        if ($this->isTerminal($task)) {
            return;
        }

        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $billing = is_array($payload['billing'] ?? null) ? $payload['billing'] : [];
        $credits = (int) ($billing['credits_debited'] ?? 0);
        $customerId = (int) ($task->customer_id ?? 0);

        if ($credits > 0 && $customerId > 0 && blank($billing['refunded_at'] ?? null)) {
            $this->customerCreditService->credit($customerId, $credits, $customerId);
            $payload['billing']['refunded_at'] = now()->toISOString();
        }

        $failure = [
            'type' => 'roboneo_error',
            'code' => strtoupper($code),
            'message' => $this->failureMessage($code, $message),
        ];
        $payload['roboneo']['error'] = $failure;
        $payload['roboneo']['failed_at'] = now()->toISOString();
        $task->update([
            'status' => 'FAILED',
            'is_completed' => false,
            'generated' => [$failure],
            'payload' => $payload,
        ]);
    }

    private function localPublicPath(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $publicUrl = rtrim((string) (parse_url(Storage::disk('public')->url(''), PHP_URL_PATH) ?: ''), '/');

        if ($publicUrl === '' || ! str_starts_with($path, $publicUrl.'/')) {
            throw new RoboNeoProtocolException(
                'RoboNeo media must be uploaded through the video-lab media endpoint.',
                'invalid_media_url',
            );
        }

        $relativePath = ltrim(substr($path, strlen($publicUrl)), '/');
        $filePath = Storage::disk('public')->path($relativePath);

        if (! $this->isNonEmptyFile($filePath)) {
            throw new RoboNeoProtocolException('RoboNeo uploaded media file was not found.', 'missing_media_file');
        }

        return $filePath;
    }

    private function isNonEmptyFile(string $path): bool
    {
        return $path !== '' && is_file($path) && filesize($path) > 0;
    }

    private function failureMessage(string $code, string $message): string
    {
        if (strtoupper($code) === 'CHARGE_FAILED') {
            return 'Tài khoản RoboNeo không đủ Carrot để tạo video.';
        }

        return $message !== '' ? $message : 'RoboNeo không thể tạo video này.';
    }
}
