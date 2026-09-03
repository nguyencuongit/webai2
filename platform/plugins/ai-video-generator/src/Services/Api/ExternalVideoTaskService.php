<?php

namespace Botble\AiVideoGenerator\Services\Api;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoIdentity;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Jobs\PollExternalRoboNeoTask;
use Botble\AiVideoGenerator\Jobs\SubmitExternalRoboNeoTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoAdmissionCoordinator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
        protected ?RoboNeoAdmissionCoordinator $admissionCoordinator = null,
    ) {}

    public function create(array $payload): string
    {
        $taskId = (string) Str::uuid();

        $this->taskRepository->create([
            'task_id' => $taskId,
            'url_image' => $payload['url_image'],
            'url_video' => $payload['url_video'],
            'status' => 'PROCESSING',
            'payload' => [
                ...$payload,
                'roboneo' => [
                    'submission' => [
                        'attempt' => 0,
                        'state' => 'queued',
                        'queued_at' => now()->toISOString(),
                        'deadline_at' => now()->addMinutes(
                            (int) config('plugins.ai-video-generator.general.roboneo.motion.admission_deadline_minutes', 50),
                        )->toISOString(),
                        'history' => [],
                    ],
                ],
            ],
        ]);

        SubmitExternalRoboNeoTask::dispatch($taskId);

        return $taskId;
    }

    public function submitPendingRoboNeoTask(Model $task): void
    {
        $taskLock = Cache::lock(
            'roboneo:admission:task:'.$task->task_id,
            max(60, (int) config('plugins.ai-video-generator.general.roboneo.motion.task_lock_seconds', 2400)),
        );

        if (! $taskLock->get()) {
            return;
        }

        try {
            $task = $task->fresh() ?: $task;

            if ($this->isTerminal($task) || data_get($task->payload, 'roboneo.task_id')) {
                return;
            }

            if (strtoupper((string) $task->status) === 'CALLBACK_PENDING') {
                $this->deliverPendingWebhook($task);

                return;
            }

            $payload = is_array($task->payload ?? null) ? $task->payload : [];
            $submission = is_array(data_get($payload, 'roboneo.submission'))
                ? data_get($payload, 'roboneo.submission')
                : [];
            $nextRetryAt = $this->carbon(data_get($submission, 'next_retry_at'));

            if ($nextRetryAt?->isFuture()) {
                return;
            }

            $deadline = $this->carbon(data_get($submission, 'deadline_at'))
                ?: now()->addMinutes($this->admissionDeadlineMinutes());

            if (now()->greaterThanOrEqualTo($deadline)) {
                $this->failProviderAdmission($task);

                return;
            }

            try {
                $localInputs = $this->ensureAdmissionInputs($task);
            } catch (Throwable $exception) {
                $this->failAdmissionException($task, $exception);

                return;
            }

            $coordinator = $this->admissionCoordinator();
            $globalCooldownUntil = $coordinator->globalCooldownUntil();

            if ($globalCooldownUntil > now()->getTimestamp()) {
                $this->scheduleAdmission($task, Carbon::createFromTimestamp($globalCooldownUntil));

                return;
            }

            $tokens = $this->apiTokenRepository->getActiveTokens();
            $lastBusyTokenId = (int) data_get($submission, 'last_busy_token_id', 0);
            $tokenLease = $coordinator->leaseToken($tokens, $lastBusyTokenId > 0 ? [$lastBusyTokenId] : []);

            if (! $tokenLease) {
                $this->scheduleAdmission(
                    $task,
                    now()->addSeconds(max(1, (int) config(
                        'plugins.ai-video-generator.general.roboneo.motion.no_token_retry_seconds',
                        30,
                    ))),
                );

                return;
            }

            $attempt = (int) data_get($submission, 'attempt', 0) + 1;
            $ipFamily = $attempt % 2 === 0 ? 'ipv6' : 'ipv4';
            $attemptGid = RoboNeoIdentity::gid();
            $attemptContext = [
                'attempt' => $attempt,
                'api_token_id' => $tokenLease->tokenId,
                'token_hash' => $this->fingerprint($tokenLease->accessToken),
                'gid_hash' => $this->fingerprint($attemptGid),
                'ip_family' => $ipFamily,
            ];

            try {
                $quotedTask = $this->roboNeo->quote(
                    $localInputs['image'],
                    $localInputs['video'],
                    $tokenLease->accessToken,
                    10,
                    ['credentials' => ['gid' => $attemptGid, 'uid' => null]],
                );
                $quotedTask['submission_trace_id'] ??= RoboNeoIdentity::traceId();
                $quotedTask['submission_seed'] ??= RoboNeoIdentity::seed();
                $attemptContext = [
                    ...$attemptContext,
                    'uid_hash' => $this->fingerprint((string) data_get($quotedTask, 'session_data.uid')),
                    'room_hash' => $this->fingerprint((string) ($quotedTask['room_id'] ?? '')),
                    'trace_hash' => $this->fingerprint((string) $quotedTask['submission_trace_id']),
                ];

                $submitGate = $coordinator->acquireSubmitGate();

                if (! $submitGate) {
                    $this->appendSubmissionHistory($task, [
                        ...$attemptContext,
                        'status' => 'submit_gate_busy',
                        'at' => now()->toISOString(),
                    ]);
                    $this->scheduleAdmission($task, now()->addSeconds(2), $attempt);

                    return;
                }

                try {
                    $submittedTask = $this->roboNeo->submit($quotedTask, $tokenLease->accessToken, [
                        'http' => ['ip_family' => $ipFamily],
                    ]);
                } finally {
                    $submitGate->release();
                }

                $coordinator->markTokenUsed($tokenLease->tokenId, now());
                $this->markSubmissionAccepted(
                    $task,
                    $submittedTask,
                    $attempt,
                    $ipFamily,
                    $tokenLease->tokenId,
                    $quotedTask,
                    $attemptContext,
                );
                $this->cleanupAdmissionInputs($task);
                $this->dispatchPolling((string) $task->task_id);
            } catch (Throwable $exception) {
                if ($this->isBusySubmissionError($exception)) {
                    $this->handleBusyAdmission($task, $tokens, $attemptContext, $exception);

                    return;
                }

                if ($this->isCredentialError($exception)) {
                    $this->apiTokenRepository->deactivate($tokenLease->tokenId);
                    $this->appendSubmissionHistory($task, [
                        ...$attemptContext,
                        'status' => 'credential_invalid',
                        'provider_code' => $this->exceptionCode($exception),
                        'at' => now()->toISOString(),
                    ]);
                    $this->scheduleAdmission($task, now()->addSecond(), $attempt);

                    return;
                }

                $this->failAdmissionException($task, $exception, $attemptContext);
            } finally {
                $tokenLease->release();
            }
        } finally {
            $taskLock->release();
        }
    }

    private function handleBusyAdmission(
        Model $task,
        array $activeTokens,
        array $attemptContext,
        RoboNeoProtocolException $exception,
    ): void {
        $coordinator = $this->admissionCoordinator();
        $tokenCooldownUntil = now()->addSeconds($this->randomConfiguredDelay(
            'token_cooldown_min_seconds',
            'token_cooldown_max_seconds',
            300,
            600,
        ));
        $globalCooldownUntil = now()->addSeconds($this->randomConfiguredDelay(
            'global_cooldown_min_seconds',
            'global_cooldown_max_seconds',
            45,
            90,
        ));
        $tokenId = (int) $attemptContext['api_token_id'];

        $coordinator->cooldownToken($tokenId, $tokenCooldownUntil);
        $coordinator->cooldownGlobal($globalCooldownUntil);
        $this->appendSubmissionHistory($task, [
            ...$attemptContext,
            'status' => 'busy',
            'provider_code' => (string) $exception->protocolCode,
            'token_cooldown_until' => $tokenCooldownUntil->toISOString(),
            'global_cooldown_until' => $globalCooldownUntil->toISOString(),
            'at' => now()->toISOString(),
        ]);

        $hasAlternative = collect($activeTokens)->contains(
            static fn (array $token): bool => (int) $token['id'] !== $tokenId,
        );
        $nextRetryAt = $hasAlternative ? $globalCooldownUntil : $tokenCooldownUntil;
        $this->scheduleAdmission($task, $nextRetryAt, (int) $attemptContext['attempt'], $tokenId);

        Log::warning('RoboNeo admission is busy; task retained for a fresh retry.', [
            'task_id' => $task->task_id,
            'attempt' => $attemptContext['attempt'],
            'api_token_id' => $tokenId,
            'next_retry_at' => $nextRetryAt->toISOString(),
            'provider_code' => (string) $exception->protocolCode,
        ]);
    }

    private function scheduleAdmission(
        Model $task,
        Carbon $at,
        ?int $attempt = null,
        ?int $lastBusyTokenId = null,
    ): void {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $submission = is_array(data_get($payload, 'roboneo.submission'))
            ? data_get($payload, 'roboneo.submission')
            : [];
        $payload['roboneo']['submission'] = [
            ...$submission,
            'attempt' => $attempt ?? (int) ($submission['attempt'] ?? 0),
            'state' => 'retry_scheduled',
            'next_retry_at' => $at->toISOString(),
            'last_busy_token_id' => $lastBusyTokenId ?? ($submission['last_busy_token_id'] ?? null),
        ];
        $task->update(['payload' => $payload]);

        SubmitExternalRoboNeoTask::dispatch((string) $task->task_id)->delay($at);
    }

    private function appendSubmissionHistory(Model $task, array $entry): void
    {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $history = data_get($payload, 'roboneo.submission.history', []);
        $history = is_array($history) ? $history : [];
        $history[] = array_filter($entry, static fn (mixed $value): bool => $value !== null && $value !== '');
        $payload['roboneo']['submission']['history'] = $history;
        $task->update(['payload' => $payload]);
    }

    private function failProviderAdmission(Model $task): void
    {
        $this->failAdmissionTask(
            $task,
            'ROBONEO_PROVIDER_UNAVAILABLE',
            'RoboNeo is temporarily unavailable after the maximum admission wait.',
        );
    }

    private function failAdmissionException(Model $task, Throwable $exception, array $attemptContext = []): void
    {
        $code = $this->exceptionCode($exception);
        $this->appendSubmissionHistory($task, [
            ...$attemptContext,
            'status' => 'failed',
            'provider_code' => $code,
            'at' => now()->toISOString(),
        ]);
        $this->failAdmissionTask($task, $code, $exception->getMessage());
    }

    private function failAdmissionTask(Model $task, string $code, string $message): void
    {
        if ($this->isTerminal($task)) {
            return;
        }

        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $payload['roboneo']['submission'] = [
            ...(is_array(data_get($payload, 'roboneo.submission')) ? data_get($payload, 'roboneo.submission') : []),
            'state' => 'failed',
            'failed_at' => now()->toISOString(),
        ];
        $task->update(['payload' => $payload]);
        $this->cleanupAdmissionInputs($task);
        $this->receiveWebhook([
            'status' => 'error',
            'task_id' => (string) $task->task_id,
            'error' => ['code' => $code, 'message' => $message],
        ]);
    }

    /** @return array{image: string, video: string} */
    private function ensureAdmissionInputs(Model $task): array
    {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $existingImage = (string) data_get($payload, 'roboneo.local_inputs.image');
        $existingVideo = (string) data_get($payload, 'roboneo.local_inputs.video');

        if ($this->isNonEmptyFile($existingImage) && $this->isNonEmptyFile($existingVideo)) {
            return ['image' => $existingImage, 'video' => $existingVideo];
        }

        $directory = $this->admissionInputDirectory((string) $task->task_id);
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

    private function cleanupAdmissionInputs(Model $task): void
    {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $trimmedVideo = (string) data_get($payload, 'roboneo.local_inputs.video');
        $directory = $this->admissionInputDirectory((string) $task->task_id);
        $trimmedDirectory = storage_path('app/ai-video-generator/roboneo-trimmed').DIRECTORY_SEPARATOR;

        if ($trimmedVideo !== '' && str_starts_with($trimmedVideo, $trimmedDirectory)) {
            File::delete($trimmedVideo);
        }

        File::deleteDirectory($directory);
        unset($payload['roboneo']['local_inputs']);
        $task->update(['payload' => $payload]);
    }

    private function admissionInputDirectory(string $taskId): string
    {
        $safeTaskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $taskId) ?: hash('sha256', $taskId);

        return storage_path('app/ai-video-generator/external-inputs/'.$safeTaskId);
    }

    private function isNonEmptyFile(string $path): bool
    {
        return $path !== '' && is_file($path) && filesize($path) > 0;
    }

    private function admissionCoordinator(): RoboNeoAdmissionCoordinator
    {
        return $this->admissionCoordinator ??= new RoboNeoAdmissionCoordinator;
    }

    private function admissionDeadlineMinutes(): int
    {
        return max(1, (int) config(
            'plugins.ai-video-generator.general.roboneo.motion.admission_deadline_minutes',
            50,
        ));
    }

    private function randomConfiguredDelay(
        string $minimumKey,
        string $maximumKey,
        int $defaultMinimum,
        int $defaultMaximum,
    ): int {
        $minimum = max(1, (int) config(
            'plugins.ai-video-generator.general.roboneo.motion.'.$minimumKey,
            $defaultMinimum,
        ));
        $maximum = max($minimum, (int) config(
            'plugins.ai-video-generator.general.roboneo.motion.'.$maximumKey,
            $defaultMaximum,
        ));

        return random_int($minimum, $maximum);
    }

    private function fingerprint(string $value): ?string
    {
        return $value === '' ? null : substr(hash('sha256', $value), 0, 12);
    }

    private function exceptionCode(Throwable $exception): string
    {
        return $exception instanceof RoboNeoProtocolException
            ? (string) ($exception->protocolCode ?: 'ROBONEO_PROTOCOL_FAILED')
            : 'EXTERNAL_CREATE_FAILED';
    }

    private function isCredentialError(Throwable $exception): bool
    {
        $code = strtolower($this->exceptionCode($exception));

        return in_array($code, ['missing_uid', 'missing_access_token', 'assigned_access_token_unavailable'], true)
            || str_starts_with($code, 'http_401_')
            || str_starts_with($code, 'http_403_');
    }

    private function carbon(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function markSubmissionAccepted(
        Model $task,
        array $submittedTask,
        int $attempt,
        string $ipFamily,
        int $apiTokenId,
        array $quotedTask = [],
        array $attemptContext = [],
    ): void {
        $payload = $task->payload ?? [];
        $history = data_get($payload, 'roboneo.submission.history', []);
        $history[] = [
            ...$attemptContext,
            'attempt' => $attempt,
            'stage' => 'nodeexecute',
            'api_token_id' => $apiTokenId,
            'ip_family' => $ipFamily,
            'status' => 'accepted',
            'at' => now()->toISOString(),
        ];
        foreach (['room_id', 'motion_node_id', 'quoted_cost', 'image_asset', 'video_asset'] as $key) {
            if (array_key_exists($key, $quotedTask)) {
                $payload['roboneo'][$key] = $quotedTask[$key];
            }
        }
        $payload['roboneo']['task_id'] = $submittedTask['task_id'];
        $payload['roboneo']['session_data'] = $submittedTask['session_data'];
        $payload['roboneo']['api_token_id'] = $apiTokenId;
        $payload['roboneo']['submission'] = [
            ...($payload['roboneo']['submission'] ?? []),
            'attempt' => $attempt,
            'state' => 'submitted',
            'submitted_at' => now()->toISOString(),
            'history' => $history,
        ];
        unset($payload['roboneo']['submission']['next_retry_at']);
        $task->update(['payload' => $payload]);
    }

    private function isBusySubmissionError(Throwable $exception): bool
    {
        return $exception instanceof RoboNeoProtocolException
            && (string) $exception->protocolCode === '6003';
    }

    private function dispatchPolling(string $taskId): void
    {
        PollExternalRoboNeoTask::dispatch($taskId)
            ->delay(now()->addSeconds($this->pollInterval()));
    }

    public function pollRoboNeo(Model $task): void
    {
        if (strtoupper((string) $task->status) === 'CALLBACK_PENDING') {
            $this->deliverPendingWebhook($task);

            return;
        }

        $payload = $task->payload ?? [];
        $roboNeo = $payload['roboneo'] ?? [];
        $roboNeoTaskId = (string) ($roboNeo['task_id'] ?? '');
        $roomId = (string) ($roboNeo['room_id'] ?? '');
        $sessionData = $roboNeo['session_data'] ?? [];
        $apiTokenId = (int) ($roboNeo['api_token_id'] ?? 0);
        $apiToken = $apiTokenId > 0
            ? $this->apiTokenRepository->findById($apiTokenId)
            : $this->apiTokenRepository->getLatestActiveToken();
        $accessToken = trim((string) data_get($apiToken, 'token_api'));

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

            return;
        }

        if ($result['state'] === 'FAILED') {
            $failureCode = strtoupper((string) ($result['failure_code'] ?? 'ROBONEO_FAILED'));

            if ($failureCode === 'CHARGE_FAILED') {
                $this->deactivateApiToken($task->fresh());
            }

            $this->receiveWebhook([
                'status' => false,
                'task_id' => $task->task_id,
                'error' => [
                    'code' => $failureCode,
                    'message' => $result['message'] ?? 'RoboNeo could not create the video.',
                ],
            ]);
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

    private function downloadMedia(string $url, string $type, ?string $directory = null): string
    {
        $directory ??= storage_path('app/ai-video-generator/external-inputs');
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
                'status' => 'CALLBACK_PENDING',
                'payload' => $taskPayload,
            ]);

            $this->deliverPendingWebhook($task->fresh());
        }
    }

    public function deliverPendingWebhook(Model $task): void
    {
        $taskPayload = $task->payload ?? [];
        $result = $taskPayload['result'] ?? [];
        $isSuccessful = (bool) ($result['success'] ?? false);

        $webhookUrl = (string) config('plugins.ai-video-generator.general.external_webhook_url');

        if ($webhookUrl === '') {
            $task->update([
                'status' => $isSuccessful ? 'COMPLETED' : 'FAILED',
            ]);

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
            $taskPayload['result']['callback_attempts'] = (int) data_get($taskPayload, 'result.callback_attempts', 0) + 1;
            $task->update([
                'status' => $isSuccessful ? 'COMPLETED' : 'FAILED',
                'payload' => $taskPayload,
            ]);
        } catch (Throwable $exception) {
            $taskPayload['result']['callback_attempts'] = (int) data_get($taskPayload, 'result.callback_attempts', 0) + 1;
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
