<?php

namespace Botble\AiVideoGenerator\Api;

use Botble\AiVideoGenerator\Exceptions\MagnificInsufficientCreditsException;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class MagnificApi
{
    public function __construct(protected AiVideoApiTokenInterface $apiTokenRepository)
    {
    }

    protected function client(?array $apiToken = null): PendingRequest
    {
        $apiToken ??= $this->activeApiToken();

        return Http::withHeaders([
            'x-magnific-api-key' => $apiToken['token_api'],
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl());
    }

    protected function baseUrl(): string
    {
        return rtrim(config('services.magnific.base_url', 'https://api.magnific.com/v1'), '/');
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->client()
            ->get($this->endpoint($endpoint), $query)
            ->throw()
            ->json();
    }

    public function post(string $endpoint, array $payload = []): array
    {
        $apiToken = $this->activeApiToken();

        try {
            return $this->client($apiToken)
                ->post($this->endpoint($endpoint), $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            if ($this->hasInsufficientCreditsResponse($exception)) {
                throw new MagnificInsufficientCreditsException($apiToken['id'], $exception);
            }

            throw $exception;
        }
    }

    public function webhookUrl(?string $provider = null): string
    {
        if (Route::has('ai-video-generator.webhook')) {
            return route('ai-video-generator.webhook', array_filter([
                'provider' => $provider,
            ]));
        }

        return url('api/v1/ai-video-generator/webhook' . ($provider ? '/' . $provider : ''));
    }

    public function getMagnificData(): array
    {
        return $this->get('resources');
    }

    protected function endpoint(string $endpoint): string
    {
        return ltrim($endpoint, '/');
    }

    /**
     * @return array{id: int, token_api: string}
     */
    protected function activeApiToken(): array
    {
        $apiToken = $this->apiTokenRepository->getLatestActiveToken();

        if (! $apiToken || ! filled($apiToken['token_api'])) {
            throw new RuntimeException('No active Magnific API token is configured.');
        }

        return $apiToken;
    }

    protected function hasInsufficientCreditsResponse(RequestException $exception): bool
    {
        return $exception->response?->status() === 402
            && strcasecmp((string) $exception->response?->json('message'), 'Insufficient credits') === 0;
    }
}
