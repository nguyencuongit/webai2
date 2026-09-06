<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo;

use Botble\Setting\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use JsonException;
use Throwable;

class RoboNeoProxyPoolSettings
{
    public const SETTING_KEY = 'ai_video_generator_roboneo_proxy_pool';

    private const ALLOWED_SCHEMES = ['http', 'https', 'socks5', 'socks5h'];

    private const MAX_PROXIES = 100;

    /** @return list<string> */
    public function all(): array
    {
        $encrypted = $this->readStoredValue();

        if (! is_string($encrypted) || $encrypted === '') {
            return [];
        }

        try {
            $proxyUrls = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        if (! is_array($proxyUrls)) {
            return [];
        }

        $normalized = [];

        foreach ($proxyUrls as $proxyUrl) {
            if (! is_string($proxyUrl)) {
                continue;
            }

            try {
                $proxyUrl = $this->normalize($proxyUrl);
            } catch (InvalidArgumentException) {
                continue;
            }

            $normalized[$proxyUrl] = $proxyUrl;
        }

        return array_values($normalized);
    }

    /** @return list<string> */
    public function parse(string $value): array
    {
        $lines = preg_split('/\R/u', trim($value)) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));

        if (count($lines) > self::MAX_PROXIES) {
            throw new InvalidArgumentException(sprintf('Chỉ được cấu hình tối đa %d proxy.', self::MAX_PROXIES));
        }

        $proxyUrls = [];

        foreach ($lines as $index => $line) {
            try {
                $proxyUrl = $this->normalize($line);
            } catch (InvalidArgumentException) {
                throw new InvalidArgumentException(sprintf(
                    'Proxy ở dòng %d không đúng định dạng. Dùng URL proxy hoặc host:port:user:password.',
                    $index + 1,
                ));
            }

            $proxyUrls[$proxyUrl] = $proxyUrl;
        }

        return array_values($proxyUrls);
    }

    /** @param list<string> $proxyUrls */
    public function replace(array $proxyUrls): void
    {
        $normalized = [];

        foreach ($proxyUrls as $proxyUrl) {
            $proxyUrl = $this->normalize($proxyUrl);
            $normalized[$proxyUrl] = $proxyUrl;
        }

        if (count($normalized) > self::MAX_PROXIES) {
            throw new InvalidArgumentException(sprintf('Chỉ được cấu hình tối đa %d proxy.', self::MAX_PROXIES));
        }

        try {
            $payload = json_encode(array_values($normalized), JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Không thể mã hóa danh sách proxy.', previous: $exception);
        }

        $this->writeStoredValue(Crypt::encryptString($payload));
    }

    public function asText(): string
    {
        return implode(PHP_EOL, $this->all());
    }

    private function normalize(string $proxyUrl): string
    {
        $proxyUrl = trim($proxyUrl);

        if ($proxyUrl === '' || strlen($proxyUrl) > 2048) {
            throw new InvalidArgumentException('Invalid proxy URL.');
        }

        if (! str_contains($proxyUrl, '://')) {
            if (! preg_match('/^([^:\s\/]+):(\d{1,5}):([^:\s]+):(.+)$/', $proxyUrl, $matches)) {
                throw new InvalidArgumentException('Invalid legacy proxy format.');
            }

            $proxyUrl = sprintf(
                'http://%s:%s@%s:%d',
                rawurlencode($matches[3]),
                rawurlencode($matches[4]),
                $matches[1],
                (int) $matches[2],
            );
        }

        try {
            $parts = parse_url($proxyUrl);
        } catch (Throwable) {
            $parts = false;
        }

        $port = (int) ($parts['port'] ?? 0);
        $path = (string) ($parts['path'] ?? '');

        if (
            ! is_array($parts)
            || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), self::ALLOWED_SCHEMES, true)
            || ! filled($parts['host'] ?? null)
            || $port < 1
            || $port > 65535
            || ($path !== '' && $path !== '/')
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException('Invalid proxy URL.');
        }

        return rtrim($proxyUrl, '/');
    }

    protected function readStoredValue(): ?string
    {
        try {
            return Setting::query()
                ->where('key', self::SETTING_KEY)
                ->value('value');
        } catch (Throwable) {
            return null;
        }
    }

    protected function writeStoredValue(string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => $value],
        );
    }
}
