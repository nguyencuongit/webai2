<?php

namespace Botble\AiVideoGenerator\Services\Api;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoAdmissionCoordinator;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoTaskPipelineService;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\ExternalRoboNeoTaskSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExternalVideoTaskService
{
    protected RoboNeoTaskPipelineService $pipeline;

    protected ExternalRoboNeoTaskSource $source;

    public function __construct(
        RoboNeoMotionApi $roboNeo,
        AiVideoApiTokenInterface $apiTokenRepository,
        protected ExternalVideoTaskInterface $taskRepository,
        MotionVideoTrimmer $videoTrimmer,
        R2VideoStorageService $r2VideoStorage,
        ?RoboNeoAdmissionCoordinator $admissionCoordinator = null,
        ?RoboNeoTaskPipelineService $pipeline = null,
        ?ExternalRoboNeoTaskSource $source = null,
    ) {
        $this->source = $source ?? new ExternalRoboNeoTaskSource($taskRepository, $videoTrimmer);
        $this->pipeline = $pipeline ?? new RoboNeoTaskPipelineService(
            $roboNeo,
            $apiTokenRepository,
            $r2VideoStorage,
            $admissionCoordinator,
        );
    }

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
                    'source' => $this->source->key(),
                    'submission' => [
                        'attempt' => 0,
                        'state' => 'queued',
                        'queued_at' => now()->toISOString(),
                        'deadline_at' => now()->addMinutes(
                            (int) config(
                                'plugins.ai-video-generator.general.roboneo.motion.admission_deadline_minutes',
                                50,
                            ),
                        )->toISOString(),
                        'history' => [],
                    ],
                ],
            ],
        ]);

        $this->source->dispatchSubmission($taskId);

        return $taskId;
    }

    public function submitPendingRoboNeoTask(Model $task): void
    {
        $this->pipeline->submit($this->source, (string) $task->task_id);
    }

    public function pollRoboNeo(Model $task): void
    {
        $this->pipeline->poll($this->source, (string) $task->task_id);
    }

    public function markPollingTimeout(Model $task): void
    {
        $this->pipeline->markPollingTimeout($this->source, (string) $task->task_id);
    }

    public function receiveWebhook(array $payload): void
    {
        $this->source->receiveWebhook($payload);
    }

    public function deliverPendingWebhook(Model $task): void
    {
        $this->source->deliverPendingWebhook($task);
    }

    public function pollInterval(): int
    {
        return $this->pipeline->pollInterval();
    }

    public function maxPollAttempts(): int
    {
        return $this->pipeline->maxPollAttempts();
    }

    public function isTerminal(Model $task): bool
    {
        return $this->source->isTerminal($task);
    }
}
