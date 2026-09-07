<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class KiotProxyApiClient
{
    public function current(string $proxyKey): ?KiotProxyEndpoint
    {
        try {
            $result = $this->request('proxies/current', ['key' => $proxyKey]);
        } catch (RoboNeoProtocolException $exception) {
            if ($exception->protocolCode === 'kiot_proxy_not_assigned') {
                return null;
            }

            throw $exception;
        }

        return $this->endpoint($result);
    }

    public function rotate(string $proxyKey, string $region): KiotProxyEndpoint
    {
        return $this->endpoint($this->request('proxies/new', [
            'key' => $proxyKey,
            'region' => $region,
        ]));
    }

    public function release(string $proxyKey): void
    {
        $this->request('proxies/out', ['key' => $proxyKey], false);
    }

    /** @return array<string, mixed>|bool */
    private function request(string $endpoint, array $query, bool $expectsProxyPayload = true): array|bool
    {
        $baseUrl = rtrim((string) config(
            'plugins.ai-video-generator.general.roboneo.kiot_proxy.base_url',
            'https://api.kiotproxy.com/api/v1',
        ), '/');

        try {
            $response = Http::acceptJson()
                ->timeout(max(3, (int) config(
                    'plugins.ai-video-generator.general.roboneo.kiot_proxy.timeout_seconds',
                    15,
                )))
                ->retry([500, 1500], throw: false)
                ->get($baseUrl.'/'.$endpoint, $query);
        } catch (ConnectionException $exception) {
            throw new RoboNeoProtocolException(
                'KiotProxy API is temporarily unreachable.',
                'kiot_proxy_connection_failed',
                ['exception' => $exception::class],
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RoboNeoProtocolException(
                'KiotProxy returned an invalid response.',
                'kiot_proxy_invalid_response',
            );
        }

        if (! ($payload['success'] ?? false)) {
            $error = strtoupper(trim((string) ($payload['error'] ?? 'KIOT_PROXY_API_FAILED')));
            $code = match ($error) {
                'KEY_NOT_FOUND' => 'kiot_proxy_key_not_found',
                'PROXY_NOT_FOUND_BY_KEY' => 'kiot_proxy_not_assigned',
                default => 'kiot_proxy_api_failed',
            };

            throw new RoboNeoProtocolException(
                match ($code) {
                    'kiot_proxy_key_not_found' => 'The configured KiotProxy key is invalid.',
                    'kiot_proxy_not_assigned' => 'The KiotProxy key has no assigned proxy.',
                    default => 'KiotProxy could not provide a proxy.',
                },
                $code,
                ['http_status' => $response->status(), 'provider_error' => $error],
            );
        }

        $data = $payload['data'] ?? null;

        if ($expectsProxyPayload && ! is_array($data)) {
            throw new RoboNeoProtocolException(
                'KiotProxy returned an invalid proxy payload.',
                'kiot_proxy_invalid_response',
            );
        }

        if (! $expectsProxyPayload && ! is_bool($data) && ! is_array($data)) {
            throw new RoboNeoProtocolException(
                'KiotProxy returned an invalid release payload.',
                'kiot_proxy_invalid_response',
            );
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function endpoint(array $data): KiotProxyEndpoint
    {
        $http = trim((string) ($data['http'] ?? ''));

        if (preg_match('/^[A-Za-z0-9.-]+:\d{1,5}$/', $http) !== 1) {
            throw new RoboNeoProtocolException(
                'KiotProxy returned an invalid HTTP endpoint.',
                'kiot_proxy_invalid_endpoint',
            );
        }

        $socks5 = trim((string) ($data['socks5'] ?? ''));
        $realIp = trim((string) ($data['realIpAddress'] ?? ''));

        return new KiotProxyEndpoint(
            httpProxy: 'http://'.$http,
            socks5Proxy: preg_match('/^[A-Za-z0-9.-]+:\d{1,5}$/', $socks5) === 1
                ? 'socks5://'.$socks5
                : null,
            fingerprint: substr(hash('sha256', $realIp !== '' ? $realIp : $http), 0, 16),
            location: filled($data['location'] ?? null) ? mb_substr((string) $data['location'], 0, 255) : null,
            expirationAt: $this->milliseconds((int) ($data['expirationAt'] ?? 0)),
            nextRequestAt: $this->milliseconds((int) ($data['nextRequestAt'] ?? 0)),
            ttl: max(0, (int) ($data['ttl'] ?? 0)),
            ttc: max(0, (int) ($data['ttc'] ?? 0)),
        );
    }

    private function milliseconds(int $timestamp): ?Carbon
    {
        if ($timestamp <= 0) {
            return null;
        }

        try {
            return Carbon::createFromTimestampMs($timestamp);
        } catch (Throwable) {
            return null;
        }
    }
}
