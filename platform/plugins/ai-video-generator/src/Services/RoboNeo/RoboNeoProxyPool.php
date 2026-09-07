<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo;

use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

class RoboNeoProxyPool
{
    private const COOLDOWN_KEY = 'roboneo:proxy:%s:cooldown-until';

    /** @var array<string, string> */
    private array $proxies = [];

    public function __construct(?array $proxyUrls = null)
    {
        $proxyUrls ??= app(RoboNeoProxyPoolSettings::class)->all();

        foreach ($proxyUrls as $proxyUrl) {
            $proxyUrl = trim((string) $proxyUrl);

            if (! $this->isValid($proxyUrl)) {
                continue;
            }

            $this->proxies[$this->id($proxyUrl)] = $proxyUrl;
        }
    }

    public function initialId(int $tokenId): ?string
    {
        $ids = array_keys($this->proxies);

        if ($ids === []) {
            return null;
        }

        return $this->firstAvailableId($ids, max(0, $tokenId - 1) % count($ids));
    }

    public function nextId(?string $currentId): ?string
    {
        $ids = array_keys($this->proxies);

        if ($ids === []) {
            return null;
        }

        $currentIndex = array_search($currentId, $ids, true);
        $startAt = $currentIndex === false ? 0 : ($currentIndex + 1) % count($ids);

        return $this->firstAvailableId($ids, $startAt);
    }

    public function cooldown(?string $proxyId, DateTimeInterface $until): void
    {
        if (! is_string($proxyId) || ! isset($this->proxies[$proxyId])) {
            return;
        }

        Cache::put($this->cooldownKey($proxyId), $until->getTimestamp(), $until);
    }

    public function isAvailable(?string $proxyId): bool
    {
        return is_string($proxyId)
            && isset($this->proxies[$proxyId])
            && $this->cooldownUntil($proxyId) <= now()->getTimestamp();
    }

    public function hasConfiguredProxies(): bool
    {
        return $this->proxies !== [];
    }

    public function earliestAvailableAt(): int
    {
        $cooldowns = array_filter(array_map($this->cooldownUntil(...), array_keys($this->proxies)));

        return $cooldowns === [] ? 0 : min($cooldowns);
    }

    public function url(?string $proxyId): ?string
    {
        return is_string($proxyId) ? ($this->proxies[$proxyId] ?? null) : null;
    }

    private function id(string $proxyUrl): string
    {
        return 'proxy-'.substr(hash('sha256', $proxyUrl), 0, 12);
    }

    private function isValid(string $proxyUrl): bool
    {
        $parts = parse_url($proxyUrl);

        return is_array($parts)
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https', 'socks5', 'socks5h'], true)
            && filled($parts['host'] ?? null)
            && (int) ($parts['port'] ?? 0) > 0;
    }

    /** @param list<string> $ids */
    private function firstAvailableId(array $ids, int $startAt): ?string
    {
        for ($offset = 0, $count = count($ids); $offset < $count; $offset++) {
            $proxyId = $ids[($startAt + $offset) % $count];

            if ($this->isAvailable($proxyId)) {
                return $proxyId;
            }
        }

        return null;
    }

    private function cooldownUntil(string $proxyId): int
    {
        return (int) Cache::get($this->cooldownKey($proxyId), 0);
    }

    private function cooldownKey(string $proxyId): string
    {
        return sprintf(self::COOLDOWN_KEY, $proxyId);
    }
}
