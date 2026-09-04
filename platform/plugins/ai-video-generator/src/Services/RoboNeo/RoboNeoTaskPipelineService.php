<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoIdentity;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\AiVideoGenerator\Services\RoboNeo\Contracts\RoboNeoTaskSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RoboNeoTaskPipelineService
{
    public function __construct(
        protected RoboNeoMotionApi $roboNeo,
        protected AiVideoApiTokenInterface $apiTokenRepository,
        protected R2VideoStorageService $r2VideoStorage,
        protected ?RoboNeoAdmissionCoordinator $admissionCoordinator = null,
    ) {}

    public function submit(RoboNeoTaskSource $source, string $taskId): void
    {
        $taskLock = Cache::lock(
            sprintf('roboneo:admission:%s:%s', $source->key(), $taskId),
            max(60, (int) config('plugins.ai-video-generator.general.roboneo.motion.task_lock_seconds', 2400)),
        );

        if (! $taskLock->get()) {
            return;
        }

        try {
            $task = $source->find($taskId);

            if (! $task) {
                return;
            }

            $task = $task->fresh() ?: $task;

            if ($source->isTerminal($task) || data_get($task->payload, 'roboneo.task_id')) {
                return;
            }

            if ($source->resumePendingCompletion($task)) {
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
                $this->failProviderAdmission($source, $task);

                return;
            }

            try {
                $localInputs = $source->prepareInputs($task);
            } catch (Throwable $exception) {
                $this->failAdmissionException($source, $task, $exception);

                return;
            }

            $coordinator = $this->coordinator();
            $globalCooldownUntil = $coordinator->globalCooldownUntil();

            if ($globalCooldownUntil > now()->getTimestamp()) {
                $this->scheduleAdmission($source, $task, Carbon::createFromTimestamp($globalCooldownUntil));

                return;
            }

            $tokens = $this->apiTokenRepository->getActiveTokens();
            $lastBusyTokenId = (int) data_get($submission, 'last_busy_token_id', 0);
            $tokenLease = $coordinator->leaseToken($tokens, $lastBusyTokenId > 0 ? [$lastBusyTokenId] : []);

            if (! $tokenLease) {
                $this->scheduleAdmission(
                    $source,
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
                    $this->scheduleAdmission($source, $task, now()->addSeconds(2), $attempt);

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
                $source->cleanupInputs($task->fresh() ?: $task);
                $source->dispatchPolling($taskId, $this->pollInterval());
            } catch (Throwable $exception) {
                if ($this->isBusySubmissionError($exception)) {
                    $this->handleBusyAdmission($source, $task, $tokens, $attemptContext, $exception);

                    return;
                }

                if ($this->isTransientAdmissionError($exception)) {
                    $this->handleTransientAdmission($source, $task, $attemptContext, $exception);

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
                    $this->scheduleAdmission($source, $task, now()->addSecond(), $attempt);

                    return;
                }

                $this->failAdmissionException($source, $task, $exception, $attemptContext);
            } finally {
                $tokenLease->release();
            }
        } finally {
            $taskLock->release();
        }
    }

    public function poll(RoboNeoTaskSource $source, string $taskId): void
    {
        $task = $source->find($taskId);

        if (! $task || $source->isTerminal($task) || $source->resumePendingCompletion($task)) {
            return;
        }

        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $attempt = (int) data_get($payload, 'roboneo.poll_attempts', 0) + 1;
        $payload['roboneo']['poll_attempts'] = $attempt;
        $task->update(['payload' => $payload]);

        try {
            $this->pollProvider($source, $task->fresh() ?: $task);
        } catch (Throwable $exception) {
            report($exception);
            $payload = is_array($task->payload ?? null) ? $task->payload : [];
            $payload['roboneo']['last_poll_error'] = [
                'code' => $this->exceptionCode($exception),
                'at' => now()->toISOString(),
            ];
            $task->update(['payload' => $payload]);
        }

        $task = $source->find($taskId) ?: $task;

        if ($source->isTerminal($task)) {
            return;
        }

        if ($source->resumePendingCompletion($task)) {
            return;
        }

        if ($attempt >= $this->maxPollAttempts()) {
            $this->markPollingTimeout($source, $taskId);

            return;
        }

        $source->dispatchPolling($taskId, $this->pollInterval());
    }

    public function markPollingTimeout(RoboNeoTaskSource $source, string $taskId): void
    {
        $task = $source->find($taskId);

        if (! $task || $source->isTerminal($task)) {
            return;
        }

        $this->deactivateApiToken($task);
        $source->fail(
            $task->fresh() ?: $task,
            'POLLING_TIMEOUT',
            'RoboNeo did not return a completed result before the polling limit.',
        );
    }

    private function pollProvider(RoboNeoTaskSource $source, Model $task): void
    {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $roboNeo = is_array($payload['roboneo'] ?? null) ? $payload['roboneo'] : [];
        $roboNeoTaskId = (string) ($roboNeo['task_id'] ?? '');

        if ($roboNeoTaskId === '' && $source->key() === 'customer') {
            $roboNeoTaskId = (string) $task->task_id;
        }

        $roomId = (string) ($roboNeo['room_id'] ?? '');
        $sessionData = $roboNeo['session_data'] ?? [];
        $apiTokenId = (int) ($roboNeo['api_token_id'] ?? 0);
        $apiToken = $apiTokenId > 0
            ? $this->apiTokenRepository->findById($apiTokenId)
            : $this->apiTokenRepository->getLatestActiveToken();
        $apiTokenId = $apiTokenId > 0 ? $apiTokenId : (int) data_get($apiToken, 'id');
        $accessToken = trim((string) data_get($apiToken, 'token_api'));

        if ($roboNeoTaskId === '' || $roomId === '' || ! is_array($sessionData) || $accessToken === '') {
            throw new RoboNeoProtocolException('The RoboNeo task cannot be polled.', 'invalid_roboneo_task_state');
        }

        if ((int) data_get($payload, 'roboneo.api_token_id') <= 0 && $apiTokenId > 0) {
            $payload['roboneo']['api_token_id'] = $apiTokenId;
        }

        $result = $this->roboNeo->poll($roboNeoTaskId, $roomId, $accessToken, $sessionData);
        $payload['roboneo']['session_data'] = $result['session_data'];
        $task->update(['payload' => $payload]);

        if ($result['state'] === 'COMPLETED') {
            $storedVideo = $this->storeResultOnR2((string) $result['result_url'], (string) $task->task_id);
            $this->deactivateApiToken($task->fresh() ?: $task);
            $source->complete($task->fresh() ?: $task, $storedVideo);

            return;
        }

        if ($result['state'] === 'FAILED') {
            $failureCode = strtoupper((string) ($result['failure_code'] ?? 'ROBONEO_FAILED'));
            $this->deactivateApiToken($task->fresh() ?: $task);
            $source->fail(
                $task->fresh() ?: $task,
                $failureCode,
                (string) ($result['message'] ?? 'RoboNeo could not create the video.'),
            );
        }
    }

    private function handleBusyAdmission(
        RoboNeoTaskSource $source,
        Model $task,
        array $activeTokens,
        array $attemptContext,
        RoboNeoProtocolException $exception,
    ): void {
        $coordinator = $this->coordinator();
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
        $this->scheduleAdmission(
            $source,
            $task,
            $nextRetryAt,
            (int) $attemptContext['attempt'],
            $tokenId,
        );

        Log::warning('RoboNeo admission is busy; task retained for a fresh retry.', [
            'source' => $source->key(),
            'task_id' => $task->task_id,
            'attempt' => $attemptContext['attempt'],
            'api_token_id' => $tokenId,
            'next_retry_at' => $nextRetryAt->toISOString(),
            'provider_code' => (string) $exception->protocolCode,
        ]);
    }

    private function handleTransientAdmission(
        RoboNeoTaskSource $source,
        Model $task,
        array $attemptContext,
        Throwable $exception,
    ): void {
        $retryAt = now()->addSeconds($this->randomConfiguredDelay(
            'transient_retry_min_seconds',
            'transient_retry_max_seconds',
            30,
            90,
        ));
        $this->coordinator()->cooldownGlobal($retryAt);
        $this->appendSubmissionHistory($task, [
            ...$attemptContext,
            'status' => 'transient_provider_failure',
            'provider_code' => $this->exceptionCode($exception),
            'global_cooldown_until' => $retryAt->toISOString(),
            'at' => now()->toISOString(),
        ]);
        $this->scheduleAdmission($source, $task, $retryAt, (int) ($attemptContext['attempt'] ?? 0));

        Log::warning('Transient RoboNeo admission failure; task retained for a fresh retry.', [
            'source' => $source->key(),
            'task_id' => $task->task_id,
            'attempt' => $attemptContext['attempt'] ?? null,
            'provider_code' => $this->exceptionCode($exception),
            'next_retry_at' => $retryAt->toISOString(),
        ]);
    }

    private function scheduleAdmission(
        RoboNeoTaskSource $source,
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
        $source->dispatchSubmission((string) $task->task_id, $at);
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

    private function failProviderAdmission(RoboNeoTaskSource $source, Model $task): void
    {
        $this->failAdmissionTask(
            $source,
            $task,
            'ROBONEO_PROVIDER_UNAVAILABLE',
            'RoboNeo is temporarily unavailable after the maximum admission wait.',
        );
    }

    private function failAdmissionException(
        RoboNeoTaskSource $source,
        Model $task,
        Throwable $exception,
        array $attemptContext = [],
    ): void {
        $code = $this->exceptionCode($exception);
        $this->appendSubmissionHistory($task, [
            ...$attemptContext,
            'status' => 'failed',
            'provider_code' => $code,
            'at' => now()->toISOString(),
        ]);
        $this->failAdmissionTask($source, $task, $code, $exception->getMessage());
    }

    private function failAdmissionTask(
        RoboNeoTaskSource $source,
        Model $task,
        string $code,
        string $message,
    ): void {
        if ($source->isTerminal($task)) {
            return;
        }

        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $payload['roboneo']['submission'] = [
            ...(is_array(data_get($payload, 'roboneo.submission')) ? data_get($payload, 'roboneo.submission') : []),
            'state' => 'failed',
            'failed_at' => now()->toISOString(),
        ];
        $task->update(['payload' => $payload]);
        $source->cleanupInputs($task->fresh() ?: $task);
        $source->fail($task->fresh() ?: $task, $code, $message);
    }

    private function markSubmissionAccepted(
        Model $task,
        array $submittedTask,
        int $attempt,
        string $ipFamily,
        int $apiTokenId,
        array $quotedTask,
        array $attemptContext,
    ): void {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $history = data_get($payload, 'roboneo.submission.history', []);
        $history = is_array($history) ? $history : [];
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

    /** @return array{key: string, url: string} */
    private function storeResultOnR2(string $url, string $taskId): array
    {
        $temporaryBasePath = tempnam(storage_path('app'), 'ai-video-roboneo-');

        if ($temporaryBasePath === false) {
            throw new RoboNeoProtocolException('Cannot create a temporary RoboNeo result file.', 'temporary_file_failed');
        }

        $temporaryPath = $temporaryBasePath.'.mp4';
        File::move($temporaryBasePath, $temporaryPath);

        try {
            $response = Http::withOptions(['curl' => [CURLOPT_PROXY => '']])
                ->timeout(300)
                ->sink($temporaryPath)
                ->get($url);

            if ($response->failed() || ! is_file($temporaryPath) || filesize($temporaryPath) === 0) {
                throw new RoboNeoProtocolException(
                    'Cannot download the completed RoboNeo video.',
                    'result_video_download_failed',
                );
            }

            return $this->r2VideoStorage->store($temporaryPath, $taskId, 'video/mp4');
        } finally {
            File::delete($temporaryPath);
        }
    }

    private function deactivateApiToken(Model $task): void
    {
        $payload = is_array($task->payload ?? null) ? $task->payload : [];
        $tokenId = (int) data_get($payload, 'roboneo.api_token_id');

        if ($tokenId <= 0 || filled(data_get($payload, 'roboneo.api_token_deactivated_at'))) {
            return;
        }

        $this->apiTokenRepository->deactivate($tokenId);
        $payload['roboneo']['deactivated_api_token_id'] = $tokenId;
        $payload['roboneo']['api_token_deactivated_at'] = now()->toISOString();
        $task->update(['payload' => $payload]);
    }

    private function isBusySubmissionError(Throwable $exception): bool
    {
        return $exception instanceof RoboNeoProtocolException
            && (string) $exception->protocolCode === '6003';
    }

    private function isCredentialError(Throwable $exception): bool
    {
        $code = strtolower($this->exceptionCode($exception));

        return in_array($code, ['missing_uid', 'missing_access_token', 'assigned_access_token_unavailable'], true)
            || str_starts_with($code, 'http_401_')
            || str_starts_with($code, 'http_403_');
    }

    private function isTransientAdmissionError(Throwable $exception): bool
    {
        $code = strtolower($this->exceptionCode($exception));

        if (preg_match('/^http_(408|425|429|5\d\d)_/', $code) === 1) {
            return true;
        }

        return in_array($code, [
            'connection_failed',
            'connect_timeout',
            'request_timeout',
            'temporarily_unavailable',
        ], true);
    }

    private function exceptionCode(Throwable $exception): string
    {
        return $exception instanceof RoboNeoProtocolException
            ? (string) ($exception->protocolCode ?: 'ROBONEO_PROTOCOL_FAILED')
            : 'ROBONEO_PIPELINE_FAILED';
    }

    private function coordinator(): RoboNeoAdmissionCoordinator
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

    private function fingerprint(string $value): ?string
    {
        return $value === '' ? null : substr(hash('sha256', $value), 0, 12);
    }

    public function pollInterval(): int
    {
        return max(1, (int) config(
            'plugins.ai-video-generator.general.roboneo.motion.poll_interval_seconds',
            5,
        ));
    }

    public function maxPollAttempts(): int
    {
        return max(1, (int) config(
            'plugins.ai-video-generator.general.roboneo.motion.max_poll_attempts',
            240,
        ));
    }
}
