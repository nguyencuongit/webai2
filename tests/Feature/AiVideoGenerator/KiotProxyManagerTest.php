<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Models\KiotProxyKey;
use Botble\AiVideoGenerator\Models\KiotProxyTaskLease;
use Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy\KiotProxyApiClient;
use Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy\KiotProxyEndpoint;
use Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy\KiotProxyManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoProtocolException.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/AiVideoApiToken.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/KiotProxyKey.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/KiotProxyTaskLease.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/RoboNeoNetworkIncident.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/KiotProxy/KiotProxyEndpoint.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/KiotProxy/KiotProxyLease.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/KiotProxy/KiotProxyApiClient.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/KiotProxy/KiotProxyManager.php';

class KiotProxyManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'kiot_testing');
        config()->set('database.connections.kiot_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('kiot_testing');
        Carbon::setTestNow('2026-09-07 12:00:00');
        $this->createTables();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('kiot_testing');
        parent::tearDown();
    }

    public function test_one_proxy_accepts_configured_number_of_tasks_and_releases_after_the_last_one(): void
    {
        $client = Mockery::mock(KiotProxyApiClient::class);
        $client->shouldReceive('current')->once()->andReturnNull();
        $client->shouldReceive('rotate')->once()->andReturn(new KiotProxyEndpoint(
            'http://198.51.100.20:3128',
            null,
            'proxy-fingerprint',
            'VN',
            now()->addHour(),
            now(),
            3600,
            0,
        ));
        $client->shouldReceive('release')->once()->with('secret-kiot-key');

        $key = KiotProxyKey::query()->create([
            'name' => 'Key 1',
            'proxy_key' => 'secret-kiot-key',
            'region' => 'random',
            'is_active' => true,
            'max_concurrent_tasks' => 2,
            'health_status' => 'healthy',
        ]);
        $manager = new KiotProxyManager($client);

        $first = $manager->acquire('external:100', now()->addMinutes(50));
        $second = $manager->acquire('external:101', now()->addMinutes(50), (int) $key->getKey());

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($key->getKey(), $first->keyId);
        $this->assertSame($first->fingerprint, $second->fingerprint);
        $this->assertNull($manager->acquire('external:102', now()->addMinutes(50), (int) $key->getKey()));
        $this->assertSame(2, KiotProxyTaskLease::query()->count());
        $this->assertTrue($key->fresh()->batch_sealed);
        $this->assertTrue($manager->earliestAvailableAt()->equalTo(now()->addSeconds(30)));
        $this->assertStringNotContainsString(
            'secret-kiot-key',
            (string) DB::table('ai_video_kiot_proxy_keys')->value('proxy_key'),
        );

        $manager->release($first);
        $this->assertSame(1, KiotProxyTaskLease::query()->count());
        $this->assertNotNull($key->fresh()->current_http_proxy);
        $this->assertTrue($key->fresh()->batch_sealed);
        $this->assertNull($manager->acquire('external:102', now()->addMinutes(50), (int) $key->getKey()));

        $manager->release($second);
        $this->assertSame(0, KiotProxyTaskLease::query()->count());
        $this->assertNull($key->fresh()->current_http_proxy);
        $this->assertFalse($key->fresh()->batch_sealed);
    }

    public function test_6003_drains_shared_proxy_and_rotates_only_after_last_task_finishes(): void
    {
        $client = Mockery::mock(KiotProxyApiClient::class);
        $client->shouldReceive('current')->once()->andReturnNull();
        $client->shouldReceive('rotate')->once()->andReturn(new KiotProxyEndpoint(
            'http://198.51.100.20:3128',
            null,
            'proxy-fingerprint',
            'VN',
            now()->addHour(),
            now(),
            3600,
            0,
        ));
        $client->shouldReceive('release')->once()->with('secret-kiot-key');
        $key = KiotProxyKey::query()->create([
            'name' => 'Key 1',
            'proxy_key' => 'secret-kiot-key',
            'region' => 'random',
            'is_active' => true,
            'max_concurrent_tasks' => 2,
            'health_status' => 'healthy',
        ]);
        $token = AiVideoApiToken::query()->create([
            'name' => 'Token 1',
            'token_api' => 'provider-token',
            'status' => true,
            'health_status' => 'healthy',
        ]);
        $manager = new KiotProxyManager($client);
        $failedLease = $manager->acquire('external:100', now()->addMinutes(50));
        $runningLease = $manager->acquire('external:101', now()->addMinutes(50));

        $manager->mark6003($failedLease, (int) $token->getKey(), 'external', '100');

        $this->assertDatabaseHas('ai_video_roboneo_network_incidents', [
            'task_reference' => '100',
            'api_token_id' => $token->getKey(),
            'kiot_proxy_key_id' => $key->getKey(),
            'proxy_fingerprint' => 'proxy-fingerprint',
            'code' => '6003',
        ], 'kiot_testing');
        $this->assertSame('blocked_6003', $token->fresh()->health_status);
        $this->assertTrue($token->fresh()->blocked_until->isFuture());
        $this->assertSame('draining_6003', $key->fresh()->health_status);
        $this->assertTrue($key->fresh()->rotate_required);
        $this->assertSame('shared:1', $key->fresh()->leased_by);
        $this->assertNotNull($key->fresh()->current_http_proxy);
        $this->assertSame($runningLease->proxyUrl, $manager->forTask(
            (int) $key->getKey(),
            'external:101',
        )->proxyUrl);

        $manager->release($runningLease);
        $this->assertNull($key->fresh()->leased_by);
        $this->assertNull($key->fresh()->current_http_proxy);
    }

    private function createTables(): void
    {
        Schema::create('ai_video_api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('token_api');
            $table->string('webhook_secret')->nullable();
            $table->boolean('status')->default(true);
            $table->string('health_status')->default('healthy');
            $table->timestamp('blocked_until')->nullable();
            $table->unsignedSmallInteger('busy_strikes')->default(0);
            $table->string('last_failure_code')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_video_kiot_proxy_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('proxy_key');
            $table->string('region')->default('random');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('max_concurrent_tasks')->default(5);
            $table->boolean('batch_sealed')->default(false);
            $table->string('health_status')->default('healthy');
            $table->string('leased_by')->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->text('current_http_proxy')->nullable();
            $table->text('current_socks5_proxy')->nullable();
            $table->string('proxy_fingerprint')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('expiration_at')->nullable();
            $table->timestamp('next_request_at')->nullable();
            $table->boolean('rotate_required')->default(false);
            $table->unsignedSmallInteger('failure_count')->default(0);
            $table->string('last_failure_code')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_video_kiot_proxy_leases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kiot_proxy_key_id')->index();
            $table->string('owner')->unique();
            $table->timestamp('lease_until')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_video_roboneo_network_incidents', function (Blueprint $table): void {
            $table->id();
            $table->string('source');
            $table->string('task_reference');
            $table->unsignedBigInteger('api_token_id')->nullable();
            $table->unsignedBigInteger('kiot_proxy_key_id')->nullable();
            $table->string('proxy_fingerprint')->nullable();
            $table->string('code');
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }
}
