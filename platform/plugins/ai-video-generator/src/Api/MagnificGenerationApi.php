<?php

namespace Botble\AiVideoGenerator\Api;

use Botble\AiVideoGenerator\Api\Contracts\AiGenerationApiInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class MagnificGenerationApi extends MagnificApi implements AiGenerationApiInterface
{
    public function __construct(protected array $model, AiVideoApiTokenInterface $apiTokenRepository)
    {
        parent::__construct($apiTokenRepository);
    }

    public function create(array|string $payload): array
    {
        if (is_string($payload)) {
            $payload = ['prompt' => $payload];
        }

        $payload = array_merge($this->model['defaults'] ?? [], $payload);

        $endpoint = $this->resolveEndpoint($payload);
        $payload = $this->payload($payload);

        Log::info('Sending AI video request to Magnific.', [
            'endpoint' => $endpoint,
            'payload' => $payload,
        ]);

        return $this->post($endpoint, $payload);
    }

    public function getTask(string $taskId): array
    {
        return $this->get($this->resolveEndpoint() . '/' . $taskId);
    }

    protected function payload(array $payload): array
    {
        if (! empty($this->model['fields'])) {
            $payload = array_intersect_key($payload, array_flip($this->model['fields']));
        }

        return array_filter($payload, static function ($value) {
            if ($value === null || $value === []) {
                return false;
            }

            if (is_string($value) && trim($value) === '') {
                return false;
            }

            return true;
        });
    }

    protected function resolveEndpoint(array $payload = []): string
    {
        $endpointField = $this->model['endpoint_field'] ?? null;

        if ($endpointField && ! empty($this->model['endpoints'])) {
            $endpointKey = $payload[$endpointField] ?? $this->model['defaults'][$endpointField] ?? null;

            if ($endpointKey && isset($this->model['endpoints'][$endpointKey])) {
                return $this->model['endpoints'][$endpointKey];
            }
        }

        if (empty($this->model['endpoint'])) {
            throw new InvalidArgumentException('Magnific API endpoint is missing.');
        }

        return $this->model['endpoint'];
    }
}
