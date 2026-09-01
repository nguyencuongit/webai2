<?php

namespace Botble\AiVideoGenerator\Api;

use Botble\AiVideoGenerator\Models\AiVideoModelEndpoint;
use Illuminate\Support\Facades\Cache;

class MagnificApiCatalog
{
    protected const CACHE_KEY = 'ai-video-generator.magnific-catalog';

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            return AiVideoModelEndpoint::query()
                ->with('model:id,name,code,status')
                ->where('status', true)
                ->whereHas('model', fn ($query) => $query->where('status', true))
                ->orderBy('name')
                ->get()
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->mapWithKeys(function (AiVideoModelEndpoint $endpoint): array {
                    return [$endpoint->code => [
                        'name' => $endpoint->code,
                        'label' => $endpoint->name,
                        'provider' => $endpoint->model->code,
                        'parent' => $endpoint->model->name,
                        'price' => (float) $endpoint->price,
                        'api' => null,
                        'endpoint' => $endpoint->endpoint,
                        'endpoint_field' => $endpoint->endpoint_field,
                        'endpoints' => $endpoint->endpoints ?? [],
                        'fields' => $endpoint->fields ?? [],
                        'required_fields' => $endpoint->required_fields ?? [],
                        'defaults' => $endpoint->defaults ?? [],
                        'durations' => $endpoint->durations ?? [],
                        'qualities' => $endpoint->qualities ?? [],
                        'aspect_ratios' => $endpoint->aspect_ratios ?? [],
                        'character_orientations' => $endpoint->character_orientations ?? [],
                        'shot_types' => $endpoint->shot_types ?? [],
                        'options' => $endpoint->options ?? [],
                    ]];
                })
                ->all();
        });
    }

    public function options(): array
    {
        return array_values($this->all());
    }

    public function get(string $name): ?array
    {
        return $this->all()[$this->normalizeName($name)] ?? null;
    }

    public function durations(string $name): array
    {
        return $this->get($name)['durations'] ?? [];
    }

    public function defaultDuration(string $name): ?string
    {
        return $this->durations($name)[0]['value'] ?? null;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }
}
