<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoModelEndpointInterface;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\CustomerRoboNeoTaskSource;
use Illuminate\Support\Str;

class AiGenerationService
{
    protected CustomerRoboNeoTaskSource $customerSource;

    public function __construct(
        protected RoboNeoMotionApi $roboNeo,
        protected AiGenerationTaskInterface $taskRepository,
        protected AiVideoApiTokenInterface $apiTokenRepository,
        protected AiVideoModelEndpointInterface $endpointRepository,
        protected MotionVideoTrimmer $videoTrimmer,
        protected CustomerCreditService $customerCreditService,
        ?CustomerRoboNeoTaskSource $customerSource = null,
    ) {
        $this->customerSource = $customerSource ?? new CustomerRoboNeoTaskSource(
            $taskRepository,
            $videoTrimmer,
            $customerCreditService,
        );
    }

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
        $taskId = (string) Str::ulid();
        $response = ['data' => ['task_id' => $taskId, 'status' => 'PROCESSING', 'generated' => []]];

        $storedTask = $this->taskRepository->storeFromResponse($response, [
            ...$payload,
            'roboneo' => [
                'source' => $this->customerSource->key(),
                'submission' => [
                    'attempt' => 0,
                    'state' => 'queued',
                    'queued_at' => now()->toISOString(),
                    'deadline_at' => now()->addMinutes((int) config(
                        'plugins.ai-video-generator.general.roboneo.motion.admission_deadline_minutes',
                        50,
                    ))->toISOString(),
                    'history' => [],
                ],
            ],
            'billing' => [
                'customer_id' => $customerId,
                'credits_debited' => $credits,
                'debited_at' => now()->toISOString(),
                'refunded_at' => null,
            ],
        ], $customerId);

        if (! $storedTask) {
            throw new RoboNeoProtocolException('Cannot persist RoboNeo task.', 'task_persistence_failed');
        }

        try {
            $this->customerSource->dispatchSubmission($taskId);
        } catch (\Throwable $exception) {
            $storedPayload = is_array($storedTask->payload ?? null) ? $storedTask->payload : [];
            $storedPayload['billing']['refunded_at'] = now()->toISOString();
            $storedPayload['roboneo']['error'] = [
                'type' => 'roboneo_error',
                'code' => 'QUEUE_DISPATCH_FAILED',
                'message' => 'Không thể đưa yêu cầu RoboNeo vào hàng chờ.',
            ];
            $storedTask->update([
                'status' => 'FAILED',
                'generated' => [$storedPayload['roboneo']['error']],
                'payload' => $storedPayload,
            ]);

            throw $exception;
        }

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
}
