<?php

namespace Tests\Feature;

use App\Models\MotionJob;
use App\Models\RoboNeoAccount;
use App\Services\RoboNeo\LiveRoboNeoGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveRoboNeoGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_task_does_not_treat_an_input_mp4_as_output(): void
    {
        config()->set('roboneo.credentials.app_token', 'authorized-app-token');
        Http::preventStrayRequests();
        Http::fake([
            '*nodeexecutequery' => Http::response([
                'error_code' => 0,
                'parameter' => [
                    'tasks' => [
                        'task-123' => [
                            'state' => 'PROCESSING',
                            'steps' => [[
                                'state' => 'PROCESSING',
                                'params' => ['video_url' => 'https://assets.test/input.mp4'],
                                'output' => null,
                            ]],
                        ],
                    ],
                ],
            ]),
            '*historymodify' => Http::response(['error_code' => 0, 'parameter' => []]),
            '*roomsubmit' => Http::response(['error_code' => 0, 'parameter' => []]),
        ]);
        $job = $this->motionJob();

        $result = app(LiveRoboNeoGateway::class)->poll($job);

        $this->assertSame('processing', $result['state']);
        $this->assertArrayNotHasKey('result_url', $result);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/nodeexecutequery')
            && $request->header('access-token')[0] === 'account-specific-token');
    }

    public function test_failed_task_is_not_completed_by_an_input_mp4_url(): void
    {
        config()->set('roboneo.credentials.app_token', 'authorized-app-token');
        Http::preventStrayRequests();
        Http::fake([
            '*nodeexecutequery' => Http::response([
                'error_code' => 0,
                'parameter' => [
                    'tasks' => [
                        'task-123' => [
                            'state' => 'FAIL',
                            'steps' => [[
                                'state' => 'FAIL',
                                'params' => ['video_url' => 'https://assets.test/input.mp4'],
                                'output' => null,
                                'error_message' => '账户余额不足',
                            ]],
                            'error_message' => '账户余额不足',
                        ],
                    ],
                ],
            ]),
            '*historymodify' => Http::response(['error_code' => 0, 'parameter' => []]),
            '*roomsubmit' => Http::response(['error_code' => 0, 'parameter' => []]),
        ]);
        $job = $this->motionJob();

        $result = app(LiveRoboNeoGateway::class)->poll($job);

        $this->assertSame('failed', $result['state']);
        $this->assertSame('账户余额不足', $result['message']);
        $this->assertArrayNotHasKey('result_url', $result);
    }

    private function motionJob(): MotionJob
    {
        $account = RoboNeoAccount::factory()->create([
            'access_token' => 'account-specific-token',
            'uid' => 'uid-123',
        ]);
        $job = new MotionJob;
        $job->forceFill([
            'roboneo_account_id' => $account->id,
            'room_id' => 'room-123',
            'task_id' => 'task-123',
            'session_data' => [
                'gid' => 'gid-123',
                'mt_g' => 'mt-g',
                'sid' => 'sid',
                'uid' => 'uid-123',
                'cookies' => [],
            ],
        ]);
        $job->setRelation('roboneoAccount', $account);

        return $job;
    }
}
