<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoProxyPool;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoProxyPoolSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoProxyPoolSettings.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoProxyPool.php';

class RoboNeoProxyPoolTest extends TestCase
{
    private array $proxyUrls = [
        'http://proxy-a-user:proxy-a-pass@127.0.0.1:3001',
        'http://proxy-b-user:proxy-b-pass@127.0.0.1:3002',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Carbon::setTestNow('2026-09-03 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();

        parent::tearDown();
    }

    public function test_cooled_down_proxies_are_skipped_until_they_recover(): void
    {
        $pool = new RoboNeoProxyPool($this->proxyUrls);
        $first = $pool->initialId(1);
        $second = $pool->nextId($first);

        $pool->cooldown($first, now()->addSeconds(120));
        $this->assertSame($second, $pool->initialId(1));

        $pool->cooldown($second, now()->addSeconds(60));
        $this->assertNull($pool->initialId(1));
        $this->assertSame(now()->addSeconds(60)->getTimestamp(), $pool->earliestAvailableAt());

        Carbon::setTestNow(now()->addSeconds(61));
        $this->assertSame($second, $pool->initialId(1));
    }

    public function test_admin_proxy_settings_are_normalized_and_encrypted_at_rest(): void
    {
        $settings = new InMemoryRoboNeoProxyPoolSettings;
        $proxyUrls = $settings->parse(implode(PHP_EOL, [
            '127.0.0.1:3001:legacy-user:legacy-pass',
            'socks5://socks-user:socks-pass@127.0.0.1:3002',
            '127.0.0.1:3001:legacy-user:legacy-pass',
        ]));

        $settings->replace($proxyUrls);

        $this->assertCount(2, $proxyUrls);
        $this->assertSame('http://legacy-user:legacy-pass@127.0.0.1:3001', $proxyUrls[0]);
        $this->assertNotNull($settings->storedValue);
        $this->assertStringNotContainsString('legacy-pass', (string) $settings->storedValue);
        $this->assertStringNotContainsString('socks-pass', (string) $settings->storedValue);
        $this->assertSame($proxyUrls, $settings->all());
    }

    public function test_default_pool_uses_admin_settings_and_ignores_the_old_config_value(): void
    {
        $settings = new InMemoryRoboNeoProxyPoolSettings;
        $settings->replace($this->proxyUrls);
        $this->app->instance(RoboNeoProxyPoolSettings::class, $settings);
        config()->set('plugins.ai-video-generator.general.roboneo.proxy_pool', [
            'http://old-env-user:old-env-pass@127.0.0.1:3999',
        ]);

        $pool = new RoboNeoProxyPool;
        $proxyId = $pool->initialId(1);

        $this->assertSame($this->proxyUrls[0], $pool->url($proxyId));
        $this->assertStringNotContainsString('old-env-pass', (string) $pool->url($proxyId));
    }

    public function test_invalid_proxy_reports_only_its_line_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Proxy ở dòng 2 không đúng định dạng');

        (new InMemoryRoboNeoProxyPoolSettings)->parse("http://127.0.0.1:3001\nsecret-invalid-proxy");
    }
}

class InMemoryRoboNeoProxyPoolSettings extends RoboNeoProxyPoolSettings
{
    public ?string $storedValue = null;

    protected function readStoredValue(): ?string
    {
        return $this->storedValue;
    }

    protected function writeStoredValue(string $value): void
    {
        $this->storedValue = $value;
    }
}
