<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Api\MagnificApiFactory;
use Botble\AiVideoGenerator\Api\MagnificApiCatalog;
use Botble\AiVideoGenerator\Exceptions\MagnificInsufficientCreditsException;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class AiGenerationService
{
    public function __construct(
        protected MagnificApiFactory $apiFactory,
        protected AiGenerationTaskInterface $taskRepository,
        protected MagnificApiCatalog $catalog,
        protected CustomerCreditService $customerCreditService,
        protected AiVideoApiTokenInterface $apiTokenRepository
    )
    {
    }

    public function create(string $name, array|string $payload): array
    {
        if (is_array($payload) && empty($payload['webhook_url'])) {
            $payload['webhook_url'] = Route::has('ai-video-generator.webhook')
                ? route('ai-video-generator.webhook', ['provider' => 'magnific'])
                : url('/api/v1/ai-video-generator/webhook/magnific');
        }

        $response = $this->createWithAvailableToken($name, $payload);

        $task = $response['data'] ?? $response;
        $customerId = auth('customer')->id();

        if (! empty($task['task_id']) && $customerId) {
            $creditsCharged = $this->estimateCredits($name, $payload);

            if ($creditsCharged > 0) {
                $this->customerCreditService->debit($customerId, $creditsCharged, $customerId);
                $payload['credits_charged'] = $creditsCharged;
            }
        }

        $this->taskRepository->storeFromResponse(
            $response,
            $payload,
            $customerId
        );

        return $response;
    }

    protected function createWithAvailableToken(string $name, array|string $payload): array
    {
        while (true) {
            try {
                return $this->apiFactory
                    ->make($name)
                    ->create($payload);
            } catch (MagnificInsufficientCreditsException $exception) {
                $wasDeactivated = $this->apiTokenRepository->deactivate($exception->apiTokenId);

                Log::warning('Magnific API token was deactivated due to insufficient credits.', [
                    'api_token_id' => $exception->apiTokenId,
                    'deactivated' => $wasDeactivated,
                ]);

                if (! $wasDeactivated || ! $this->apiTokenRepository->getLatestActiveToken()) {
                    throw $exception->requestException;
                }
            }
        }
    }

    public function getTask(string $name, string $taskId): array
    {
        return $this->apiFactory
            ->make($name)
            ->getTask($taskId);
    }

    public function estimateCredits(string $name, array|string $payload): int
    {
        $model = $this->catalog->get($name);
        $price = (float) ($model['price'] ?? 0);

        if ($price <= 0) {
            return 0;
        }

        $duration = is_array($payload)
            ? (float) ($payload['duration'] ?? data_get($model, 'defaults.duration', 1))
            : 1;

        return (int) ceil($price * max(1, $duration));
    }
}
