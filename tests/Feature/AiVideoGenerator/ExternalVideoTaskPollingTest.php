<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\Api\ExternalVideoTaskService;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoProtocolException.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoIdentity.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoMotionApi.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/AiVideoApiTokenInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/ExternalVideoTaskInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/MotionVideoTrimmer.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoTokenLease.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoAdmissionCoordinator.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/R2/R2VideoStorageService.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/PollExternalRoboNeoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/RetryExternalRoboNeoSubmission.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/SubmitExternalRoboNeoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php';

class ExternalVideoTaskPollingTest extends TestCase
{
    public function test_polling_keeps_using_the_token_that_created_the_provider_task(): void
    {
        $task = new PollingInMemoryExternalVideoTask;
        $task->forceFill([
            'task_id' => 'external-task-4',
            'status' => 'PROCESSING',
            'payload' => [
                'roboneo' => [
                    'task_id' => 'provider-task-4',
                    'room_id' => 'room-4',
                    'session_data' => ['gid' => 'gid-4', 'uid' => 'uid-4', 'cookies' => []],
                    'api_token_id' => 10,
                ],
            ],
        ]);

        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('poll')->willReturnCallback(function (
            string $taskId,
            string $roomId,
            string $accessToken,
        ): array {
            if ($accessToken !== 'assigned-access-token') {
                throw new RoboNeoProtocolException('Polling switched to another account.', 'wrong_poll_token');
            }

            return [
                'state' => 'PROCESSING',
                'session_data' => [
                    'gid' => 'gid-4',
                    'uid' => 'uid-4',
                    'cookies' => [],
                    'poll_marker' => 'updated',
                ],
            ];
        });

        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $assignedToken = new PollingInMemoryApiToken;
        $assignedToken->forceFill(['id' => 10, 'token_api' => 'assigned-access-token', 'status' => true]);
        $tokens->method('findById')->willReturn($assignedToken);
        $tokens->method('getLatestActiveToken')->willReturn([
            'id' => 11,
            'token_api' => 'different-latest-token',
        ]);

        $service = new ExternalVideoTaskService(
            $roboNeo,
            $tokens,
            $this->createMock(ExternalVideoTaskInterface::class),
            $this->createMock(MotionVideoTrimmer::class),
            $this->createMock(R2VideoStorageService::class),
        );

        $service->pollRoboNeo($task);

        $this->assertSame('updated', data_get($task->payload, 'roboneo.session_data.poll_marker'));
    }

    public function test_a_successful_provider_task_does_not_deactivate_a_still_usable_token(): void
    {
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response('completed-video-bytes'));
        config()->set('plugins.ai-video-generator.general.external_webhook_url', '');
        $task = new PollingInMemoryExternalVideoTask;
        $task->forceFill([
            'task_id' => 'external-completed-task',
            'status' => 'PROCESSING',
            'payload' => [
                'roboneo' => [
                    'task_id' => 'provider-completed-task',
                    'room_id' => 'completed-room',
                    'session_data' => ['gid' => 'completed-gid', 'uid' => 'completed-uid', 'cookies' => []],
                    'api_token_id' => 10,
                ],
            ],
        ]);

        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('poll')->willReturn([
            'state' => 'COMPLETED',
            'result_url' => 'https://provider.example.com/result.mp4',
            'session_data' => ['gid' => 'completed-gid', 'uid' => 'completed-uid', 'cookies' => []],
        ]);

        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $assignedToken = new PollingInMemoryApiToken;
        $assignedToken->forceFill(['id' => 10, 'token_api' => 'assigned-access-token', 'status' => true]);
        $tokens->method('findById')->willReturn($assignedToken);
        $tokens->expects($this->never())->method('deactivate');

        $tasks = $this->createMock(ExternalVideoTaskInterface::class);
        $tasks->method('findByTaskId')->willReturn($task);
        $storage = $this->createMock(R2VideoStorageService::class);
        $storage->method('store')->willReturn([
            'key' => 'completed/result.mp4',
            'url' => 'https://r2.example.com/completed/result.mp4',
        ]);

        $service = new ExternalVideoTaskService(
            $roboNeo,
            $tokens,
            $tasks,
            $this->createMock(MotionVideoTrimmer::class),
            $storage,
        );

        $service->pollRoboNeo($task);

        $this->assertSame('COMPLETED', $task->status);
        $this->assertArrayNotHasKey('deactivated_api_token_id', data_get($task->payload, 'roboneo', []));
    }

    public function test_charge_failed_deactivates_the_token_before_finishing_the_task(): void
    {
        config()->set('plugins.ai-video-generator.general.external_webhook_url', '');
        $task = new PollingInMemoryExternalVideoTask;
        $task->forceFill([
            'task_id' => 'external-charge-failed-task',
            'status' => 'PROCESSING',
            'payload' => [
                'roboneo' => [
                    'task_id' => 'provider-charge-failed-task',
                    'room_id' => 'charge-failed-room',
                    'session_data' => ['gid' => 'charge-failed-gid', 'uid' => 'charge-failed-uid', 'cookies' => []],
                    'api_token_id' => 10,
                ],
            ],
        ]);

        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('poll')->willReturn([
            'state' => 'FAILED',
            'failure_code' => 'CHARGE_FAILED',
            'message' => 'The account cannot pay for this task.',
            'session_data' => ['gid' => 'charge-failed-gid', 'uid' => 'charge-failed-uid', 'cookies' => []],
        ]);

        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $assignedToken = new PollingInMemoryApiToken;
        $assignedToken->forceFill(['id' => 10, 'token_api' => 'assigned-access-token', 'status' => true]);
        $tokens->method('findById')->willReturn($assignedToken);
        $tokens->method('deactivate')->willReturnCallback(
            static fn (int $tokenId): bool => $tokenId === 10 && $task->status === 'PROCESSING',
        );

        $tasks = $this->createMock(ExternalVideoTaskInterface::class);
        $tasks->method('findByTaskId')->willReturn($task);
        $service = new ExternalVideoTaskService(
            $roboNeo,
            $tokens,
            $tasks,
            $this->createMock(MotionVideoTrimmer::class),
            $this->createMock(R2VideoStorageService::class),
        );

        $service->pollRoboNeo($task);

        $this->assertSame('FAILED', $task->status);
        $this->assertSame(10, data_get($task->payload, 'roboneo.deactivated_api_token_id'));
    }
}

class PollingInMemoryExternalVideoTask extends Model
{
    protected $guarded = [];

    protected $casts = ['payload' => 'array'];

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->forceFill($attributes);

        return true;
    }

    public function fresh($with = []): static
    {
        return $this;
    }
}

class PollingInMemoryApiToken extends Model
{
    protected $guarded = [];
}
