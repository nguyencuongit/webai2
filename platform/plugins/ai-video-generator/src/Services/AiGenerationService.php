<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Jobs\PollRoboNeoTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoModelEndpointInterface;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AiGenerationService
{
    public function __construct(
        protected RoboNeoMotionApi $roboNeo,
        protected AiGenerationTaskInterface $taskRepository,
        protected AiVideoApiTokenInterface $apiTokenRepository,
        protected AiVideoModelEndpointInterface $endpointRepository,
        protected MotionVideoTrimmer $videoTrimmer,
        protected CustomerCreditService $customerCreditService,
    ) {}

    public function create(string $name, array|string $payload): array
    {
        if (! is_array($payload)) {
            throw new RoboNeoProtocolException('RoboNeo generation payload must be an array.', 'invalid_payload');
        }

        $model = $this->roboNeoModel($name);
        $customerId = (int) auth('customer')->id();
        $credits = (int) ceil((float) $model->price);
        $charged = false;

        if ($customerId <= 0) {
            throw new RoboNeoProtocolException('A logged-in customer is required.', 'missing_customer');
        }

        if ($credits > 0) {
            $this->customerCreditService->debit($customerId, $credits, $customerId);
            $charged = true;
        }

        try {
            return $this->createRoboNeoTask($payload, $customerId, $credits);
        } catch (\Throwable $exception) {
            if ($charged) {
                $this->customerCreditService->credit($customerId, $credits, $customerId);
            }

            throw $exception;
        }
    }

    private function createRoboNeoTask(array $payload, int $customerId, int $credits): array
    {
        $apiToken = $this->apiTokenRepository->getLatestActiveToken();
        $accessToken = trim((string) ($apiToken['token_api'] ?? ''));

        if ($accessToken === '') {
            throw new RoboNeoProtocolException('An active RoboNeo access token is required.', 'missing_access_token');
        }

        $sourceVideoPath = $this->localPublicPath((string) ($payload['video_url'] ?? ''));
        $videoPath = $this->videoTrimmer->trim($sourceVideoPath);

        try {
            $task = $this->roboNeo->generate(
                $this->localPublicPath((string) ($payload['image_url'] ?? '')),
                $videoPath,
                $accessToken,
                (int) ($payload['duration'] ?? 10),
            );
        } finally {
            if ($videoPath !== $sourceVideoPath) {
                File::delete($videoPath);
            }
        }
        $response = ['data' => [...$task, 'status' => 'PROCESSING', 'generated' => []]];

        $storedTask = $this->taskRepository->storeFromResponse($response, [
            ...$payload,
            'roboneo' => [
                'room_id' => $task['room_id'],
                'session_data' => $task['session_data'],
            ],
            'billing' => [
                'credits_debited' => $credits,
                'refunded_at' => null,
            ],
        ], $customerId);

        if (! $storedTask) {
            throw new RoboNeoProtocolException('Cannot persist RoboNeo task.', 'task_persistence_failed');
        }

        PollRoboNeoTask::dispatch($task['task_id'])
            ->delay(now()->addSeconds((int) config('plugins.ai-video-generator.general.roboneo.motion.poll_interval_seconds', 5)));

        return $response;
    }

    public function getTask(string $name, string $taskId): array
    {
        return $this->taskRepository->getFirstBy(['task_id' => $taskId])?->toArray() ?? [];
    }

    public function estimateCredits(string $name, array|string $payload): int
    {
        $model = $this->endpointRepository->getActiveByCode($name);

        return $model ? (int) ceil((float) $model->price) : 0;
    }

    private function roboNeoModel(string $code): \Botble\AiVideoGenerator\Models\AiVideoModelEndpoint
    {
        $model = $this->endpointRepository->getActiveByCode($code);

        if (! $model || strtolower((string) $model->code) !== 'roboneo') {
            throw new RoboNeoProtocolException('The selected model is unavailable or is not supported by RoboNeo.', 'unsupported_model');
        }

        return $model;
    }

    private function localPublicPath(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $publicUrl = rtrim((string) (parse_url(Storage::disk('public')->url(''), PHP_URL_PATH) ?: ''), '/');

        if ($publicUrl === '' || ! str_starts_with($path, $publicUrl.'/')) {
            throw new RoboNeoProtocolException('RoboNeo media must be uploaded through the video-lab media endpoint.', 'invalid_media_url');
        }

        $relativePath = ltrim(substr($path, strlen($publicUrl)), '/');
        $filePath = Storage::disk('public')->path($relativePath);

        if (! is_file($filePath)) {
            throw new RoboNeoProtocolException('RoboNeo uploaded media file was not found.', 'missing_media_file');
        }

        return $filePath;
    }
}
