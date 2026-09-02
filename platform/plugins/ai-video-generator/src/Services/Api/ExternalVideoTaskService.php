<?php

namespace Botble\AiVideoGenerator\Services\Api;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Jobs\PollExternalRoboNeoTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ExternalVideoTaskService
{
    public function __construct(
        protected RoboNeoMotionApi $roboNeo,
        protected AiVideoApiTokenInterface $apiTokenRepository,
        protected ExternalVideoTaskInterface $taskRepository,
        protected MotionVideoTrimmer $videoTrimmer,
        protected R2VideoStorageService $r2VideoStorage,
    ) {}

    public function create(array $payload): string
    {
        $taskId = (string) Str::uuid();

        $externalTask = $this->taskRepository->create([
            'task_id' => $taskId,
            'url_image' => $payload['url_image'],
            'url_video' => $payload['url_video'],
            'status' => 'PROCESSING',
            'payload' => $payload,
        ]);

        $imagePath = null;
        $sourceVideoPath = null;
        $videoPath = null;

        try {
            $apiToken = $this->apiTokenRepository->getLatestActiveToken();
            $accessToken = trim((string) ($apiToken['token_api'] ?? ''));

            if ($accessToken === '') {
                throw new RoboNeoProtocolException('An active RoboNeo access token is required.', 'missing_access_token');
            }

            $imagePath = $this->downloadMedia($payload['url_image'], 'image');
            $sourceVideoPath = $this->downloadMedia($payload['url_video'], 'video');
            $videoPath = $this->videoTrimmer->trim($sourceVideoPath);
            $roboNeoTask = $this->roboNeo->generate($imagePath, $videoPath, $accessToken);

            $externalTask->update([
                'payload' => [
                    ...$payload,
                    'roboneo' => [
                        'task_id' => $roboNeoTask['task_id'],
                        'room_id' => $roboNeoTask['room_id'],
                        'session_data' => $roboNeoTask['session_data'],
                        'api_token_id' => $apiToken['id'],
                    ],
                ],
            ]);

            PollExternalRoboNeoTask::dispatch($taskId)
                ->delay(now()->addSeconds($this->pollInterval()));
        } catch (Throwable $exception) {
            $externalTask->update([
                'status' => 'FAILED',
                'payload' => [
                    ...$payload,
                    'roboneo' => [
                        'error' => [
                            'type' => 'roboneo_error',
                            'code' => $exception instanceof RoboNeoProtocolException ? $exception->protocolCode : 'EXTERNAL_CREATE_FAILED',
                            'message' => $exception->getMessage(),
                        ],
                    ],
                ],
            ]);

            throw $exception;
        } finally {
            if ($videoPath !== null && $videoPath !== $sourceVideoPath) {
                File::delete($videoPath);
            }

            File::delete(array_filter([$imagePath, $sourceVideoPath]));
        }

        return $taskId;
    }

    public function pollRoboNeo(Model $task): void
    {
        $payload = $task->payload ?? [];
        $roboNeo = $payload['roboneo'] ?? [];
        $roboNeoTaskId = (string) ($roboNeo['task_id'] ?? '');
        $roomId = (string) ($roboNeo['room_id'] ?? '');
        $sessionData = $roboNeo['session_data'] ?? [];
        $apiToken = $this->apiTokenRepository->getLatestActiveToken();
        $accessToken = trim((string) ($apiToken['token_api'] ?? ''));

        if ($roboNeoTaskId === '' || $roomId === '' || ! is_array($sessionData) || $accessToken === '') {
            throw new RoboNeoProtocolException('The external RoboNeo task cannot be polled.', 'invalid_external_task_state');
        }

        $result = $this->roboNeo->poll($roboNeoTaskId, $roomId, $accessToken, $sessionData);
        $payload['roboneo']['session_data'] = $result['session_data'];
        $task->update(['payload' => $payload]);

        if ($result['state'] === 'COMPLETED') {
            $storedVideo = $this->storeResultOnR2($result['result_url'], $task->task_id);

            $this->receiveWebhook([
                'status' => 'success',
                'task_id' => $task->task_id,
                'url_video' => $storedVideo['url'],
                'r2_key' => $storedVideo['key'],
            ]);
            $this->deactivateApiToken($task->fresh());

            return;
        }

        if ($result['state'] === 'FAILED') {
            $failureCode = strtoupper((string) ($result['failure_code'] ?? 'ROBONEO_FAILED'));

            $this->receiveWebhook([
                'status' => false,
                'task_id' => $task->task_id,
                'error' => [
                    'code' => $failureCode,
                    'message' => $result['message'] ?? 'RoboNeo could not create the video.',
                ],
            ]);

            if ($failureCode === 'CHARGE_FAILED') {
                $this->deactivateApiToken($task->fresh());
            }
        }
    }

    public function markPollingTimeout(Model $task): void
    {
        if ($this->isTerminal($task)) {
            return;
        }

        $this->receiveWebhook([
            'status' => false,
            'task_id' => $task->task_id,
            'error' => [
                'code' => 'POLLING_TIMEOUT',
                'message' => 'RoboNeo did not return a completed result before the polling limit.',
            ],
        ]);
    }

    private function downloadMedia(string $url, string $type): string
    {
        $directory = storage_path('app/ai-video-generator/external-inputs');
        File::ensureDirectoryExists($directory);

        $extension = pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $extension = preg_replace('/[^a-z0-9]/i', '', $extension) ?: ($type === 'image' ? 'jpg' : 'mp4');
        $path = $directory.'/'.$type.'-'.Str::uuid().'.'.$extension;

        try {
            Http::timeout(300)->sink($path)->get($url)->throw();

            if (! is_file($path) || filesize($path) === 0) {
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

    /**
     * RoboNeo's result link is transient. Store it on R2 before giving the
     * external API a URL, so no video bytes are sent through the webhook.
     *
     * @return array{key: string, url: string}
     */
    private function storeResultOnR2(string $url, string $taskId): array
    {
        $temporaryBasePath = tempnam(storage_path('app'), 'ai-video-external-');

        if ($temporaryBasePath === false) {
            throw new RoboNeoProtocolException('Cannot create a temporary file for the RoboNeo result.', 'temporary_file_failed');
        }

        $temporaryPath = $temporaryBasePath.'.mp4';
        File::move($temporaryBasePath, $temporaryPath);

        try {
            $response = Http::withOptions([
                'curl' => [CURLOPT_PROXY => ''],
            ])->timeout(300)
                ->sink($temporaryPath)
                ->get($url);

            if ($response->failed() || ! is_file($temporaryPath) || filesize($temporaryPath) === 0) {
                throw new RoboNeoProtocolException('Cannot download the completed RoboNeo video.', 'result_video_download_failed');
            }

            return $this->r2VideoStorage->store($temporaryPath, $taskId, 'video/mp4');
        } finally {
            File::delete($temporaryPath);
        }
    }

    /**
     * Placeholder for processing a completed task sent by the third party.
     */
    public function receiveWebhook(array $payload): void
    {
        $isSuccessful = $this->isSuccessfulStatus($payload['status'] ?? null);
        $task = $this->taskRepository->findByTaskId($payload['task_id']);
        $error = $this->webhookError($payload);

        if ($isSuccessful && empty($payload['url_video'])) {
            throw new RoboNeoProtocolException('A completed external task must include url_video.', 'missing_result_video');
        }

        if ($task) {
            $taskPayload = $task->payload ?? [];
            $taskPayload['result'] = [
                'success' => $isSuccessful,
                'url_video' => $payload['url_video'] ?? null,
                'r2_key' => $payload['r2_key'] ?? null,
                'error' => $isSuccessful ? null : $error,
                'received_at' => now()->toISOString(),
            ];
            $task->update([
                'status' => $isSuccessful ? 'COMPLETED' : 'FAILED',
                'payload' => $taskPayload,
            ]);
        }

        $webhookUrl = (string) config('ai-video-generator.general.external_webhook_url');

        if ($webhookUrl === '') {
            return;
        }

        try {
            $webhookPayload = $isSuccessful
                ? [
                    'status' => 'success',
                    'task_id' => $payload['task_id'],
                    'url_video' => $payload['url_video'],
                ]
                : [
                    'status' => false,
                    'task_id' => $payload['task_id'],
                    'error' => $error,
                ];

            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('ai-video-generator.general.timeout', 60))
                ->post($webhookUrl, $webhookPayload);

            Log::info('External video webhook response received.', [
                'task_id' => $payload['task_id'],
                'webhook_url' => $webhookUrl,
                'response_status' => $response->status(),
                'response_body' => $response->json() ?? $response->body(),
            ]);

            $response->throw();
        } catch (Throwable $exception) {
            Log::error('Cannot deliver external video webhook.', [
                'task_id' => $payload['task_id'],
                'webhook_url' => $webhookUrl,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
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
            'message' => is_scalar($error) && (string) $error !== '' ? (string) $error : 'Video creation failed.',
        ];
    }

    public function pollInterval(): int
    {
        return max(1, (int) config('plugins.ai-video-generator.general.roboneo.motion.poll_interval_seconds', 5));
    }

    public function maxPollAttempts(): int
    {
        return max(1, (int) config('plugins.ai-video-generator.general.roboneo.motion.max_poll_attempts', 240));
    }

    public function isTerminal(Model $task): bool
    {
        return in_array(strtoupper((string) $task->status), ['COMPLETED', 'FAILED', 'CANCELLED', 'ERROR'], true);
    }

    private function deactivateApiToken(?Model $task): void
    {
        if (! $task) {
            return;
        }

        $payload = $task->payload ?? [];
        $tokenId = (int) data_get($payload, 'roboneo.api_token_id');

        if ($tokenId <= 0 || ! $this->apiTokenRepository->deactivate($tokenId)) {
            return;
        }

        $payload['roboneo']['deactivated_api_token_id'] = $tokenId;
        $payload['roboneo']['api_token_deactivated_at'] = now()->toISOString();
        $task->update(['payload' => $payload]);
    }
}
