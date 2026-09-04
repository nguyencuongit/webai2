<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo\Sources;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Jobs\PollExternalRoboNeoTask;
use Botble\AiVideoGenerator\Jobs\SubmitExternalRoboNeoTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\RoboNeo\Contracts\RoboNeoTaskSource;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ExternalRoboNeoTaskSource implements RoboNeoTaskSource
{
    public function __construct(
        protected ExternalVideoTaskInterface $taskRepository,
        protected MotionVideoTrimmer $videoTrimmer,
    ) {}

    public function key(): string
    {
        return 'external';
    }

    public function find(string $taskId): ?Model
    {
        return $this->taskRepository->findByTaskId($taskId);
    }

    public function prepareInputs(Model $task): array
    {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $existingImage = (string) data_get($payload, 'roboneo.local_inputs.image');
        $existingVideo = (string) data_get($payload, 'roboneo.local_inputs.video');

        if ($this->isNonEmptyFile($existingImage) && $this->isNonEmptyFile($existingVideo)) {
            return ['image' => $existingImage, 'video' => $existingVideo];
        }

        $directory = $this->inputDirectory((string) $task->task_id);
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);
        $imagePath = $this->downloadMedia((string) $payload['url_image'], 'image', $directory);
        $sourceVideoPath = $this->downloadMedia((string) $payload['url_video'], 'video', $directory);
        $videoPath = $this->videoTrimmer->trim($sourceVideoPath);
        $payload['roboneo']['local_inputs'] = [
            'directory' => $directory,
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
        $trimmedVideo = (string) data_get($payload, 'roboneo.local_inputs.video');
        $directory = $this->inputDirectory((string) $task->task_id);
        $trimmedDirectory = storage_path('app/ai-video-generator/roboneo-trimmed').DIRECTORY_SEPARATOR;

        if ($trimmedVideo !== '' && str_starts_with($trimmedVideo, $trimmedDirectory)) {
            File::delete($trimmedVideo);
        }

        File::deleteDirectory($directory);
        unset($payload['roboneo']['local_inputs']);
        $task->update(['payload' => $payload]);
    }

    public function dispatchSubmission(string $taskId, ?Carbon $at = null): void
    {
        $job = SubmitExternalRoboNeoTask::dispatch($taskId);

        if ($at) {
            $job->delay($at);
        }
    }

    public function dispatchPolling(string $taskId, int $delaySeconds): void
    {
        PollExternalRoboNeoTask::dispatch($taskId)->delay(now()->addSeconds($delaySeconds));
    }

    public function complete(Model $task, array $storedVideo): void
    {
        $this->recordResult($task, true, $storedVideo['url'], $storedVideo['key']);
    }

    public function fail(Model $task, string $code, string $message): void
    {
        $this->recordResult($task, false, null, null, [
            'code' => $code,
            'message' => $message,
        ]);
    }

    public function resumePendingCompletion(Model $task): bool
    {
        if (strtoupper((string) $task->status) !== 'CALLBACK_PENDING') {
            return false;
        }

        $this->deliverPendingWebhook($task);

        return true;
    }

    public function isTerminal(Model $task): bool
    {
        return in_array(strtoupper((string) $task->status), ['COMPLETED', 'FAILED', 'CANCELLED', 'ERROR'], true);
    }

    public function receiveWebhook(array $payload): void
    {
        $isSuccessful = $this->isSuccessfulStatus($payload['status'] ?? null);
        $task = $this->find((string) ($payload['task_id'] ?? ''));

        if ($isSuccessful && empty($payload['url_video'])) {
            throw new RoboNeoProtocolException('A completed external task must include url_video.', 'missing_result_video');
        }

        if (! $task) {
            return;
        }

        $this->recordResult(
            $task,
            $isSuccessful,
            $payload['url_video'] ?? null,
            $payload['r2_key'] ?? null,
            $isSuccessful ? null : $this->webhookError($payload),
        );
    }

    public function deliverPendingWebhook(Model $task): void
    {
        $taskPayload = is_array($task->payload ?? null) ? $task->payload : [];
        $result = is_array($taskPayload['result'] ?? null) ? $taskPayload['result'] : [];
        $isSuccessful = (bool) ($result['success'] ?? false);
        $webhookUrl = (string) config('plugins.ai-video-generator.general.external_webhook_url');

        if ($webhookUrl === '') {
            $task->update(['status' => $isSuccessful ? 'COMPLETED' : 'FAILED']);

            return;
        }

        try {
            $webhookPayload = $isSuccessful
                ? [
                    'status' => 'success',
                    'task_id' => $task->task_id,
                    'url_video' => $result['url_video'],
                ]
                : [
                    'status' => 'error',
                    'task_id' => $task->task_id,
                    'message' => (string) data_get($result, 'error.message', 'RoboNeo could not create the video.'),
                ];
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'token' => (string) config('plugins.ai-video-generator.general.external_webhook_token'),
                ])
                ->timeout((int) config('plugins.ai-video-generator.general.timeout', 60))
                ->retry([500, 1500, 3500], throw: false)
                ->post($webhookUrl, $webhookPayload);

            Log::info('External video webhook response received.', [
                'task_id' => $task->task_id,
                'webhook_url' => $webhookUrl,
                'response_status' => $response->status(),
            ]);

            $response->throw();
            $taskPayload['result']['callback_delivered_at'] = now()->toISOString();
            $taskPayload['result']['callback_attempts'] = (int) data_get(
                $taskPayload,
                'result.callback_attempts',
                0,
            ) + 1;
            $task->update([
                'status' => $isSuccessful ? 'COMPLETED' : 'FAILED',
                'payload' => $taskPayload,
            ]);
        } catch (Throwable $exception) {
            $taskPayload['result']['callback_attempts'] = (int) data_get(
                $taskPayload,
                'result.callback_attempts',
                0,
            ) + 1;
            $taskPayload['result']['callback_last_failed_at'] = now()->toISOString();
            $task->update([
                'status' => 'CALLBACK_PENDING',
                'payload' => $taskPayload,
            ]);

            Log::error('Cannot deliver external video webhook.', [
                'task_id' => $task->task_id,
                'webhook_url' => $webhookUrl,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function recordResult(
        Model $task,
        bool $successful,
        ?string $urlVideo = null,
        ?string $r2Key = null,
        ?array $error = null,
    ): void {
        if ($this->isTerminal($task)) {
            return;
        }

        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $payload['result'] = [
            'success' => $successful,
            'url_video' => $urlVideo,
            'r2_key' => $r2Key,
            'error' => $successful ? null : $error,
            'received_at' => now()->toISOString(),
        ];
        $task->update([
            'status' => 'CALLBACK_PENDING',
            'payload' => $payload,
        ]);
        $this->deliverPendingWebhook($task->fresh() ?: $task);
    }

    private function downloadMedia(string $url, string $type, string $directory): string
    {
        File::ensureDirectoryExists($directory);
        $extension = pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $extension = preg_replace('/[^a-z0-9]/i', '', $extension) ?: ($type === 'image' ? 'jpg' : 'mp4');
        $path = $directory.'/'.$type.'-'.Str::uuid().'.'.$extension;

        try {
            Http::timeout(300)->sink($path)->get($url)->throw();

            if (! $this->isNonEmptyFile($path)) {
                throw new RoboNeoProtocolException("The external {$type} download is empty.", 'empty_external_media');
            }

            return $path;
        } catch (Throwable $exception) {
            File::delete($path);

            if ($exception instanceof RoboNeoProtocolException) {
                throw $exception;
            }

            throw new RoboNeoProtocolException("Cannot download the external {$type}.", 'external_media_download_failed', [
                'previous_message' => $exception->getMessage(),
            ]);
        }
    }

    private function inputDirectory(string $taskId): string
    {
        $safeTaskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $taskId) ?: hash('sha256', $taskId);

        return storage_path('app/ai-video-generator/external-inputs/'.$safeTaskId);
    }

    private function isNonEmptyFile(string $path): bool
    {
        return $path !== '' && is_file($path) && filesize($path) > 0;
    }

    private function isSuccessfulStatus(mixed $status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['1', 'true', 'success', 'completed'], true);
    }

    private function webhookError(array $payload): array
    {
        $error = $payload['error'] ?? null;

        if (is_array($error)) {
            return [
                'code' => (string) ($error['code'] ?? 'EXTERNAL_TASK_FAILED'),
                'message' => (string) ($error['message'] ?? 'Video creation failed.'),
            ];
        }

        return [
            'code' => 'EXTERNAL_TASK_FAILED',
            'message' => is_scalar($error) && (string) $error !== ''
                ? (string) $error
                : 'Video creation failed.',
        ];
    }
}
