<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\AiVideoGenerator\Services\RoboNeo\Contracts\RoboNeoTaskSource;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoAdmissionCoordinator;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoTaskPipelineService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoProtocolException.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoIdentity.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoMotionApi.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/AiVideoApiTokenInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/R2/R2VideoStorageService.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoTokenLease.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoAdmissionCoordinator.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/Contracts/RoboNeoTaskSource.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoTaskPipelineService.php';

class RoboNeoTaskPipelineTest extends TestCase
{
    public function test_the_shared_pipeline_contract_exists_for_all_task_sources(): void
    {
        $this->assertTrue(interface_exists(RoboNeoTaskSource::class));
        $this->assertTrue(class_exists(RoboNeoTaskPipelineService::class));
    }

    public function test_both_submission_and_polling_use_one_pipeline_and_deactivate_the_exact_token_on_success(): void
    {
        Cache::flush();
        Http::fake(fn () => Http::response('completed-video'));
        Carbon::setTestNow('2026-09-04 09:00:00');

        $task = new PipelineInMemoryTask;
        $task->forceFill([
            'task_id' => 'shared-task',
            'status' => 'PROCESSING',
            'payload' => [
                'duration' => 5,
                'roboneo' => [
                    'submission' => [
                        'attempt' => 0,
                        'state' => 'queued',
                        'deadline_at' => now()->addMinutes(50)->toISOString(),
                        'history' => [],
                    ],
                ],
            ],
        ]);
        $source = new PipelineInMemorySource($task);
        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('quote')->willReturnCallback(function (
            string $imagePath,
            string $videoPath,
            string $accessToken,
            int $duration,
        ): array {
            $this->assertSame(5, $duration);

            return [
                'room_id' => 'shared-room',
                'motion_node_id' => 'shared-node',
                'quoted_cost' => 72,
                'image_asset' => ['url' => 'https://assets.test/image.jpg'],
                'video_asset' => ['url' => 'https://assets.test/video.mp4'],
                'session_data' => ['gid' => 'shared-gid', 'uid' => 'shared-uid', 'cookies' => []],
                'submission_trace_id' => 'shared-trace',
                'submission_seed' => 'shared-seed',
            ];
        });
        $roboNeo->method('submit')->willReturn([
            'task_id' => 'provider-shared-task',
            'session_data' => ['gid' => 'shared-gid', 'uid' => 'shared-uid', 'cookies' => []],
        ]);
        $roboNeo->method('poll')->willReturn([
            'state' => 'COMPLETED',
            'result_url' => 'https://assets.test/result.mp4',
            'session_data' => ['gid' => 'shared-gid', 'uid' => 'shared-uid', 'cookies' => []],
        ]);

        $tokenWasDeactivated = false;
        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $tokens->method('getActiveTokens')->willReturn([
            ['id' => 10, 'token_api' => 'shared-access-token'],
        ]);
        $tokens->method('findById')->with(10)->willReturn([
            'id' => 10,
            'token_api' => 'shared-access-token',
        ]);
        $tokens->expects($this->once())->method('deactivate')->with(10)->willReturnCallback(
            function () use (&$tokenWasDeactivated): bool {
                $tokenWasDeactivated = true;

                return true;
            },
        );

        $storage = $this->createMock(R2VideoStorageService::class);
        $storage->method('store')->willReturn([
            'key' => 'ai-videos/shared-task.mp4',
            'url' => 'https://r2.test/shared-task.mp4',
        ]);
        $pipeline = new RoboNeoTaskPipelineService(
            $roboNeo,
            $tokens,
            $storage,
            new RoboNeoAdmissionCoordinator,
        );

        $pipeline->submit($source, 'shared-task');

        $this->assertSame('submitted', data_get($task->payload, 'roboneo.submission.state'));
        $this->assertSame(10, data_get($task->payload, 'roboneo.api_token_id'));
        $this->assertSame(1, $source->pollDispatches);

        $source->beforeComplete = function () use (&$tokenWasDeactivated): void {
            $this->assertTrue($tokenWasDeactivated);
        };
        $pipeline->poll($source, 'shared-task');

        $this->assertSame('COMPLETED', $task->status);
        $this->assertSame(1, $source->completions);
        $this->assertSame('ai-videos/shared-task.mp4', data_get($source->storedVideo, 'key'));
    }

    public function test_customer_tasks_created_before_the_unified_pipeline_keep_polling_by_their_task_id(): void
    {
        Http::fake(fn () => Http::response('completed-video'));
        $task = new PipelineInMemoryTask;
        $task->forceFill([
            'task_id' => 'legacy-provider-task',
            'status' => 'PROCESSING',
            'payload' => [
                'roboneo' => [
                    'room_id' => 'legacy-room',
                    'session_data' => ['gid' => 'legacy-gid', 'uid' => 'legacy-uid', 'cookies' => []],
                ],
            ],
        ]);
        $source = new PipelineInMemorySource($task, 'customer');
        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('poll')->willReturnCallback(function (string $taskId): array {
            $this->assertSame('legacy-provider-task', $taskId);

            return [
                'state' => 'COMPLETED',
                'result_url' => 'https://assets.test/legacy-result.mp4',
                'session_data' => ['gid' => 'legacy-gid', 'uid' => 'legacy-uid', 'cookies' => []],
            ];
        });
        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $tokens->method('getLatestActiveToken')->willReturn([
            'id' => 11,
            'token_api' => 'legacy-access-token',
        ]);
        $tokens->method('deactivate')->willReturn(true);
        $storage = $this->createMock(R2VideoStorageService::class);
        $storage->method('store')->willReturn([
            'key' => 'ai-videos/legacy-provider-task.mp4',
            'url' => 'https://r2.test/legacy-provider-task.mp4',
        ]);

        (new RoboNeoTaskPipelineService($roboNeo, $tokens, $storage))->poll($source, 'legacy-provider-task');

        $this->assertSame('COMPLETED', $task->status);
        $this->assertSame(11, data_get($task->payload, 'roboneo.deactivated_api_token_id'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();

        parent::tearDown();
    }
}

class PipelineInMemoryTask extends Model
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

class PipelineInMemorySource implements RoboNeoTaskSource
{
    public int $submissionDispatches = 0;

    public int $pollDispatches = 0;

    public int $completions = 0;

    public int $failures = 0;

    public array $storedVideo = [];

    public ?\Closure $beforeComplete = null;

    public function __construct(
        private readonly PipelineInMemoryTask $task,
        private readonly string $sourceKey = 'test',
    ) {}

    public function key(): string
    {
        return $this->sourceKey;
    }

    public function find(string $taskId): ?Model
    {
        return $taskId === $this->task->task_id ? $this->task : null;
    }

    public function prepareInputs(Model $task): array
    {
        return ['image' => __FILE__, 'video' => __FILE__];
    }

    public function cleanupInputs(Model $task): void {}

    public function dispatchSubmission(string $taskId, ?Carbon $at = null): void
    {
        $this->submissionDispatches++;
    }

    public function dispatchPolling(string $taskId, int $delaySeconds): void
    {
        $this->pollDispatches++;
    }

    public function complete(Model $task, array $storedVideo): void
    {
        ($this->beforeComplete) && ($this->beforeComplete)();
        $this->completions++;
        $this->storedVideo = $storedVideo;
        $task->update(['status' => 'COMPLETED']);
    }

    public function fail(Model $task, string $code, string $message): void
    {
        $this->failures++;
        $task->update(['status' => 'FAILED']);
    }

    public function resumePendingCompletion(Model $task): bool
    {
        return false;
    }

    public function isTerminal(Model $task): bool
    {
        return in_array(strtoupper((string) $task->status), ['COMPLETED', 'FAILED'], true);
    }
}
