<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoAdmissionCoordinator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoTokenLease.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoAdmissionCoordinator.php';

class RoboNeoAdmissionCoordinatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('plugins.ai-video-generator.general.roboneo.motion.token_lease_seconds', 600);
        config()->set('plugins.ai-video-generator.general.roboneo.motion.global_submit_lock_seconds', 180);
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_it_does_not_lease_the_same_token_twice(): void
    {
        $coordinator = new RoboNeoAdmissionCoordinator;

        $first = $coordinator->leaseToken($this->tokens());
        $second = $coordinator->leaseToken($this->tokens());

        $this->assertSame(9, $first?->tokenId);
        $this->assertSame(10, $second?->tokenId);

        $first?->release();
        $second?->release();
    }

    public function test_it_skips_cooldown_and_prefers_the_least_recently_used_token(): void
    {
        $coordinator = new RoboNeoAdmissionCoordinator;
        $coordinator->cooldownToken(9, now()->addMinutes(8));
        $coordinator->markTokenUsed(10, now());

        $lease = $coordinator->leaseToken($this->tokens());

        $this->assertSame(11, $lease?->tokenId);
        $lease?->release();
    }

    public function test_an_excluded_busy_token_is_only_used_when_no_other_token_is_available(): void
    {
        $coordinator = new RoboNeoAdmissionCoordinator;

        $alternative = $coordinator->leaseToken($this->tokens(), [9]);
        $this->assertSame(10, $alternative?->tokenId);
        $alternative?->release();

        $fallback = $coordinator->leaseToken([$this->tokens()[0]], [9]);
        $this->assertSame(9, $fallback?->tokenId);
        $fallback?->release();
    }

    public function test_global_cooldown_blocks_submit_gate_until_it_expires(): void
    {
        $coordinator = new RoboNeoAdmissionCoordinator;
        $coordinator->cooldownGlobal(now()->addMinute());

        $this->assertNull($coordinator->acquireSubmitGate());

        $coordinator->cooldownGlobal(now()->subSecond());
        $gate = $coordinator->acquireSubmitGate();

        $this->assertNotNull($gate);
        $gate?->release();
    }

    /** @return list<array{id: int, token_api: string}> */
    private function tokens(): array
    {
        return [
            ['id' => 9, 'token_api' => 'token-nine'],
            ['id' => 10, 'token_api' => 'token-ten'],
            ['id' => 11, 'token_api' => 'token-eleven'],
        ];
    }
}
