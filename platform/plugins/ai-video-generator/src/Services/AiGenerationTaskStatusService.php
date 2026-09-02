<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;

class AiGenerationTaskStatusService
{
    public function __construct(
        protected AiGenerationTaskInterface $taskRepository,
        protected AiVideoApiTokenInterface $apiTokenRepository,
        protected RoboNeoMotionApi $roboNeo,
        protected AiGenerationWebhookService $webhookService,
        protected CustomerCreditService $customerCreditService,
    ) {}

    public function find(string $taskId): ?array
    {
        $task = $this->taskRepository->getFirstBy(['task_id' => $taskId]);

        if (! $task instanceof AiGenerationTask) {
            return null;
        }

        $customerId = auth('customer')->id();

        if ($task->customer_id && (int) $task->customer_id !== (int) $customerId) {
            return null;
        }

        return [
            'task_id' => $task->task_id,
            'status' => $task->status,
            'is_completed' => (bool) $task->is_completed,
            'generated' => $task->generated ?: [],
            'has_nsfw' => $task->has_nsfw ?: [],
            'completed_at' => $task->completed_at?->toDateTimeString(),
        ];
    }

    public function pollRoboNeo(AiGenerationTask $task): void
    {
        $roboNeo = $task->payload['roboneo'] ?? [];
        $roomId = (string) ($roboNeo['room_id'] ?? '');
        $sessionData = $roboNeo['session_data'] ?? [];
        $apiToken = $this->apiTokenRepository->getLatestActiveToken();
        $accessToken = trim((string) ($apiToken['token_api'] ?? ''));

        if ($roomId === '' || ! is_array($sessionData) || $accessToken === '') {
            return;
        }

        $result = $this->roboNeo->poll($task->task_id, $roomId, $accessToken, $sessionData);
        $payload = $task->payload ?? [];
        $payload['roboneo']['session_data'] = $result['session_data'];

        if ($result['state'] === 'COMPLETED') {
            $task->update(['payload' => $payload]);

            $this->webhookService->handle([
                'task' => [
                    'task_id' => $task->task_id,
                    'status' => 'COMPLETED',
                    'generated' => [$result['result_url']],
                ],
            ]);
            $this->deactivateApiToken($apiToken, $task->refresh());

            return;
        }

        if ($result['state'] === 'FAILED') {
            $failureCode = (string) ($result['failure_code'] ?? 'ROBONEO_FAILED');
            $failure = [
                'type' => 'roboneo_error',
                'code' => $failureCode,
                'message' => $this->failureMessage($failureCode, (string) ($result['message'] ?? '')),
            ];
            $payload['roboneo']['error'] = $failure;
            $task->update([
                'status' => 'FAILED',
                'generated' => [$failure],
                'payload' => $payload,
            ]);
            $task->refresh();
            $this->refundRoboNeoCredits($task);
            $this->deactivateApiToken($apiToken, $task);

            return;
        }

        $task->update([
            'status' => $result['state'],
            'payload' => $payload,
        ]);
    }

    public function markRoboNeoFailed(AiGenerationTask $task): void
    {
        if ($task->is_completed || strtoupper((string) $task->status) === 'FAILED') {
            return;
        }

        $failure = [
            'type' => 'roboneo_error',
            'code' => 'POLLING_TIMEOUT',
            'message' => 'RoboNeo did not return a completed result before the polling limit.',
        ];
        $payload = $task->payload ?? [];
        $payload['roboneo']['error'] = $failure;
        $task->update([
            'status' => 'FAILED',
            'generated' => [$failure],
            'payload' => $payload,
        ]);
        $task->refresh();
        $this->refundRoboNeoCredits($task);
        $this->deactivateApiToken($this->apiTokenRepository->getLatestActiveToken(), $task);
    }

    private function refundRoboNeoCredits(AiGenerationTask $task): void
    {
        $payload = $task->payload ?? [];
        $billing = $payload['billing'] ?? [];
        $credits = (int) ($billing['credits_debited'] ?? 0);
        $customerId = (int) $task->customer_id;

        if ($credits <= 0 || $customerId <= 0 || filled($billing['refunded_at'] ?? null)) {
            return;
        }

        $this->customerCreditService->credit($customerId, $credits, $customerId);
        $payload['billing']['refunded_at'] = now()->toISOString();
        $task->update(['payload' => $payload]);
    }

    private function deactivateApiToken(?array $apiToken, AiGenerationTask $task): void
    {
        $tokenId = (int) ($apiToken['id'] ?? 0);

        if ($tokenId <= 0 || ! $this->apiTokenRepository->deactivate($tokenId)) {
            return;
        }

        $payload = $task->payload ?? [];
        $payload['roboneo']['deactivated_api_token_id'] = $tokenId;
        $payload['roboneo']['api_token_deactivated_at'] = now()->toISOString();
        $task->update(['payload' => $payload]);
    }

    private function failureMessage(string $code, string $message): string
    {
        if (strtoupper($code) === 'CHARGE_FAILED') {
            return 'Tài khoản RoboNeo không đủ Carrot để tạo video.';
        }

        return $message !== '' ? $message : 'RoboNeo không thể tạo video này.';
    }
}
