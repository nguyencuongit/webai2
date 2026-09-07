<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy\KiotProxyApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoProtocolException.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/KiotProxy/KiotProxyEndpoint.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/KiotProxy/KiotProxyApiClient.php';

class KiotProxyApiClientTest extends TestCase
{
    public function test_it_maps_a_dynamic_http_proxy_without_exposing_the_key(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'data' => [
                    'realIpAddress' => '203.0.113.12',
                    'http' => '198.51.100.5:3128',
                    'socks5' => '198.51.100.5:1080',
                    'location' => 'VN',
                    'expirationAt' => 1798794000000,
                    'nextRequestAt' => 1798790400000,
                    'ttl' => 3600,
                    'ttc' => 0,
                ],
            ]),
        ]);

        $endpoint = (new KiotProxyApiClient)->rotate('top-secret-key', 'nam');

        $this->assertSame('http://198.51.100.5:3128', $endpoint->httpProxy);
        $this->assertSame('socks5://198.51.100.5:1080', $endpoint->socks5Proxy);
        $this->assertSame(substr(hash('sha256', '203.0.113.12'), 0, 16), $endpoint->fingerprint);
        Http::assertSent(static fn ($request): bool => $request->url() === 'https://api.kiotproxy.com/api/v1/proxies/new?key=top-secret-key&region=nam');
    }

    public function test_it_maps_missing_current_proxy_and_redacts_invalid_key_errors(): void
    {
        Http::fakeSequence()
            ->push(['success' => false, 'error' => 'PROXY_NOT_FOUND_BY_KEY'])
            ->push(['success' => false, 'error' => 'KEY_NOT_FOUND']);

        $client = new KiotProxyApiClient;
        $this->assertNull($client->current('not-assigned-secret'));

        try {
            $client->rotate('invalid-secret', 'random');
            $this->fail('Expected an invalid key exception.');
        } catch (RoboNeoProtocolException $exception) {
            $this->assertSame('kiot_proxy_key_not_found', $exception->protocolCode);
            $this->assertStringNotContainsString('invalid-secret', $exception->getMessage());
            $this->assertStringNotContainsString('not-assigned-secret', $exception->getMessage());
        }
    }

    public function test_it_accepts_the_boolean_release_payload_from_kiotproxy(): void
    {
        Http::fake(['*' => Http::response(['success' => true, 'data' => true])]);

        (new KiotProxyApiClient)->release('release-secret');

        Http::assertSentCount(1);
    }
}
