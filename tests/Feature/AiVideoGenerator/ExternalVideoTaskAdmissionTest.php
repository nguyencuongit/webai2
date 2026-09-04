<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoMotionApi;
use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Jobs\PollExternalRoboNeoTask;
use Botble\AiVideoGenerator\Jobs\PollRoboNeoTask;
use Botble\AiVideoGenerator\Jobs\RetryExternalRoboNeoSubmission;
use Botble\AiVideoGenerator\Jobs\SubmitCustomerRoboNeoTask;
use Botble\AiVideoGenerator\Jobs\SubmitExternalRoboNeoTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\AiVideoGenerator\Services\Api\ExternalVideoTaskService;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoTaskPipelineService;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\ExternalRoboNeoTaskSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoProtocolException.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoIdentity.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoMotionApi.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/AiVideoApiTokenInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/ExternalVideoTaskInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/MotionVideoTrimmer.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoTokenLease.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoAdmissionCoordinator.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/Contracts/RoboNeoTaskSource.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/RoboNeoTaskPipelineService.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/Sources/ExternalRoboNeoTaskSource.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/R2/R2VideoStorageService.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/PollExternalRoboNeoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/RetryExternalRoboNeoSubmission.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/SubmitExternalRoboNeoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/SubmitCustomerRoboNeoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Jobs/PollRoboNeoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/Api/ExternalVideoTaskService.php';

class ExternalVideoTaskAdmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('plugins.ai-video-generator.general.roboneo.motion.token_cooldown_min_seconds', 300);
        config()->set('plugins.ai-video-generator.general.roboneo.motion.token_cooldown_max_seconds', 300);
        config()->set('plugins.ai-video-generator.general.roboneo.motion.global_cooldown_min_seconds', 1);
        config()->set('plugins.ai-video-generator.general.roboneo.motion.global_cooldown_max_seconds', 1);
        config()->set('plugins.ai-video-generator.general.roboneo.motion.no_token_retry_seconds', 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();
        File::deleteDirectory(storage_path('app/ai-video-generator/external-inputs/admission-test'));
        File::deleteDirectory(storage_path('app/ai-video-generator/external-inputs/deadline-test'));
        File::deleteDirectory(storage_path('app/ai-video-generator/external-inputs/accepted-test'));

        parent::tearDown();
    }

    public function test_create_returns_immediately_and_dispatches_submission_job(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response('media-bytes'));
        Carbon::setTestNow('2026-09-03 12:00:00');

        $task = new AdmissionInMemoryExternalVideoTask;
        $tasks = $this->createMock(ExternalVideoTaskInterface::class);
        $tasks->method('create')->willReturnCallback(function (array $attributes) use ($task): Model {
            $task->forceFill($attributes);

            return $task;
        });

        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $tokens->method('getLatestActiveToken')->willReturn([
            'id' => 10,
            'token_api' => 'access-token-10',
        ]);

        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('quote')->willReturn([
            'room_id' => 'room-current-flow',
            'motion_node_id' => 'node-current-flow',
            'quoted_cost' => 72,
            'image_asset' => ['url' => 'https://assets.example.com/image.jpg'],
            'video_asset' => ['url' => 'https://assets.example.com/video.mp4'],
            'session_data' => ['gid' => 'gid-current-flow', 'uid' => 'uid-current-flow', 'cookies' => []],
        ]);
        $roboNeo->method('submit')->willReturn([
            'task_id' => 'provider-task-current-flow',
            'session_data' => ['gid' => 'gid-current-flow', 'uid' => 'uid-current-flow', 'cookies' => []],
        ]);

        $trimmer = $this->createMock(MotionVideoTrimmer::class);
        $trimmer->method('trim')->willReturnCallback(static fn (string $path): string => $path);

        $service = new ExternalVideoTaskService(
            $roboNeo,
            $tokens,
            $tasks,
            $trimmer,
            $this->createMock(R2VideoStorageService::class),
        );

        $taskId = $service->create([
            'url_image' => 'https://z-test.test/image.jpg',
            'url_video' => 'https://z-test.test/video.mp4',
        ]);

        $this->assertSame($task->task_id, $taskId);
        $this->assertSame('PROCESSING', $task->status);
        $this->assertSame('queued', data_get($task->payload, 'roboneo.submission.state'));
        $this->assertSame(0, data_get($task->payload, 'roboneo.submission.attempt'));
        $this->assertSame(
            '2026-09-03T12:50:00.000000Z',
            data_get($task->payload, 'roboneo.submission.deadline_at'),
        );
        Queue::assertPushed('Botble\AiVideoGenerator\Jobs\SubmitExternalRoboNeoTask');
    }

    public function test_submit_and_poll_jobs_are_routed_to_separate_lightweight_queues(): void
    {
        config()->set('plugins.ai-video-generator.general.roboneo.submit_queue', 'roboneo-submit');
        config()->set('plugins.ai-video-generator.general.roboneo.poll_queue', 'roboneo-poll');

        $this->assertSame('roboneo-submit', (new SubmitExternalRoboNeoTask('external'))->queue);
        $this->assertSame('roboneo-submit', (new RetryExternalRoboNeoSubmission('external', 1))->queue);
        $this->assertSame('roboneo-submit', (new SubmitCustomerRoboNeoTask('customer'))->queue);
        $this->assertSame('roboneo-poll', (new PollExternalRoboNeoTask('external'))->queue);
        $this->assertSame('roboneo-poll', (new PollRoboNeoTask('customer'))->queue);
    }

    public function test_6003_keeps_the_task_processing_and_retries_with_a_fresh_context_and_token(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response('media-bytes'));
        Carbon::setTestNow('2026-09-03 12:00:00');

        $task = $this->queuedTask('admission-test');
        $quoteCall = 0;
        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('quote')->willReturnCallback(function (
            string $imagePath,
            string $videoPath,
            string $accessToken,
            int $duration,
            array $settings,
        ) use (&$quoteCall): array {
            $quoteCall++;

            return [
                'room_id' => 'room-'.$quoteCall,
                'motion_node_id' => 'node-'.$quoteCall,
                'quoted_cost' => 72,
                'image_asset' => ['url' => 'https://assets.example.com/image-'.$quoteCall.'.jpg'],
                'video_asset' => ['url' => 'https://assets.example.com/video-'.$quoteCall.'.mp4'],
                'session_data' => [
                    'gid' => data_get($settings, 'credentials.gid'),
                    'uid' => 'uid-for-'.$accessToken,
                    'cookies' => [],
                ],
                'submission_trace_id' => 'trace-'.$quoteCall,
                'submission_seed' => 'seed-'.$quoteCall,
            ];
        });
        $roboNeo->method('submit')->willThrowException(
            new RoboNeoProtocolException('The system is busy. Please try again later.', '6003'),
        );

        $service = $this->admissionService($task, $roboNeo);

        $service->submitPendingRoboNeoTask($task);
        $firstNextRetry = Carbon::parse((string) data_get($task->payload, 'roboneo.submission.next_retry_at'));
        Carbon::setTestNow($firstNextRetry->addSecond());
        $service->submitPendingRoboNeoTask($task);

        $history = data_get($task->payload, 'roboneo.submission.history', []);
        $this->assertSame('PROCESSING', $task->status);
        $this->assertSame('retry_scheduled', data_get($task->payload, 'roboneo.submission.state'));
        $this->assertSame([9, 10], array_column($history, 'api_token_id'));
        $this->assertSame(['6003', '6003'], array_column($history, 'provider_code'));
        $this->assertNotSame($history[0]['gid_hash'], $history[1]['gid_hash']);
        $this->assertNotSame($history[0]['room_hash'], $history[1]['room_hash']);
        $this->assertNotSame($history[0]['trace_hash'], $history[1]['trace_hash']);
        $this->assertArrayNotHasKey('result', $task->payload);
        $this->assertFileExists((string) data_get($task->payload, 'roboneo.local_inputs.image'));
        $this->assertFileExists((string) data_get($task->payload, 'roboneo.local_inputs.video'));
        Http::assertSentCount(2);
        Queue::assertPushed('Botble\AiVideoGenerator\Jobs\SubmitExternalRoboNeoTask', 2);
    }

    public function test_transient_provider_gateway_failure_keeps_the_task_processing_until_admission_deadline(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response('media-bytes'));
        Carbon::setTestNow('2026-09-03 12:00:00');

        $task = $this->queuedTask('admission-test');
        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('quote')->willThrowException(
            new RoboNeoProtocolException(
                'RoboNeo gateway timed out while initializing the workflow.',
                'http_504_web_workflow_canvas_init_json',
            ),
        );

        $service = $this->admissionService($task, $roboNeo);
        $service->submitPendingRoboNeoTask($task);

        $this->assertSame('PROCESSING', $task->status);
        $this->assertSame('retry_scheduled', data_get($task->payload, 'roboneo.submission.state'));
        $this->assertSame(1, data_get($task->payload, 'roboneo.submission.attempt'));
        $this->assertSame(
            'transient_provider_failure',
            data_get($task->payload, 'roboneo.submission.history.0.status'),
        );
        $this->assertSame(
            'http_504_web_workflow_canvas_init_json',
            data_get($task->payload, 'roboneo.submission.history.0.provider_code'),
        );
        $this->assertArrayNotHasKey('result', $task->payload);
        Queue::assertPushed('Botble\AiVideoGenerator\Jobs\SubmitExternalRoboNeoTask');
    }

    public function test_deadline_emits_one_normalized_failure_without_exposing_6003(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response(['success' => true]));
        config()->set('plugins.ai-video-generator.general.external_webhook_url', 'https://z-test.test/hook');
        Carbon::setTestNow('2026-09-03 12:00:00');

        $task = $this->queuedTask('deadline-test', now()->subSecond());
        $service = $this->admissionService($task, $this->createMock(RoboNeoMotionApi::class));

        $service->submitPendingRoboNeoTask($task);
        $service->submitPendingRoboNeoTask($task);

        $this->assertSame('FAILED', $task->status);
        $this->assertSame('ROBONEO_PROVIDER_UNAVAILABLE', data_get($task->payload, 'result.error.code'));
        $this->assertStringNotContainsString('6003', (string) data_get($task->payload, 'result.error.message'));
        Http::assertSentCount(1);
    }

    public function test_an_accepted_task_keeps_its_token_and_duplicate_jobs_never_resubmit_it(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response('media-bytes'));
        Carbon::setTestNow('2026-09-03 12:00:00');

        $task = $this->queuedTask('accepted-test');
        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->expects($this->once())->method('quote')->willReturn([
            'room_id' => 'accepted-room',
            'motion_node_id' => 'accepted-node',
            'quoted_cost' => 72,
            'image_asset' => ['url' => 'https://assets.example.com/image.jpg'],
            'video_asset' => ['url' => 'https://assets.example.com/video.mp4'],
            'session_data' => ['gid' => 'accepted-gid', 'uid' => 'accepted-uid', 'cookies' => []],
            'submission_trace_id' => 'accepted-trace',
            'submission_seed' => 'accepted-seed',
        ]);
        $roboNeo->expects($this->once())->method('submit')->willReturn([
            'task_id' => 'provider-accepted-task',
            'session_data' => ['gid' => 'accepted-gid', 'uid' => 'accepted-uid', 'cookies' => []],
        ]);

        $service = $this->admissionService($task, $roboNeo);
        $service->submitPendingRoboNeoTask($task);
        $service->submitPendingRoboNeoTask($task);

        $this->assertSame('provider-accepted-task', data_get($task->payload, 'roboneo.task_id'));
        $this->assertSame(9, data_get($task->payload, 'roboneo.api_token_id'));
        $this->assertSame('submitted', data_get($task->payload, 'roboneo.submission.state'));
        $this->assertDirectoryDoesNotExist(storage_path('app/ai-video-generator/external-inputs/accepted-test'));
        $this->assertArrayNotHasKey('local_inputs', data_get($task->payload, 'roboneo', []));
        Queue::assertPushed(PollExternalRoboNeoTask::class, 1);
    }

    public function test_a_legacy_retry_job_uses_the_fresh_admission_state_machine(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response('media-bytes'));
        Carbon::setTestNow('2026-09-03 12:00:00');

        $task = $this->queuedTask('admission-test');
        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('quote')->willReturn([
            'room_id' => 'legacy-fresh-room',
            'motion_node_id' => 'legacy-fresh-node',
            'quoted_cost' => 72,
            'image_asset' => ['url' => 'https://assets.example.com/image.jpg'],
            'video_asset' => ['url' => 'https://assets.example.com/video.mp4'],
            'session_data' => ['gid' => 'legacy-fresh-gid', 'uid' => 'legacy-fresh-uid', 'cookies' => []],
            'submission_trace_id' => 'legacy-fresh-trace',
            'submission_seed' => 'legacy-fresh-seed',
        ]);
        $roboNeo->method('submit')->willThrowException(
            new RoboNeoProtocolException('The system is busy. Please try again later.', '6003'),
        );
        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $tokens->method('getActiveTokens')->willReturn([
            ['id' => 9, 'token_api' => 'token-nine'],
            ['id' => 10, 'token_api' => 'token-ten'],
        ]);
        $tasks = $this->createMock(ExternalVideoTaskInterface::class);
        $tasks->method('findByTaskId')->willReturn($task);
        $trimmer = $this->createMock(MotionVideoTrimmer::class);
        $trimmer->method('trim')->willReturnCallback(static fn (string $path): string => $path);
        $pipeline = new RoboNeoTaskPipelineService(
            $roboNeo,
            $tokens,
            $this->createMock(R2VideoStorageService::class),
        );
        $source = new ExternalRoboNeoTaskSource($tasks, $trimmer);

        (new RetryExternalRoboNeoSubmission('admission-test', 4))->handle($pipeline, $source);

        $this->assertSame('PROCESSING', $task->status);
        $this->assertSame(1, data_get($task->payload, 'roboneo.submission.attempt'));
        $this->assertSame('6003', data_get($task->payload, 'roboneo.submission.history.0.provider_code'));
        Queue::assertPushed('Botble\AiVideoGenerator\Jobs\SubmitExternalRoboNeoTask');
    }

    public function test_an_expired_token_is_deactivated_then_the_next_token_is_used(): void
    {
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response('media-bytes'));
        Carbon::setTestNow('2026-09-03 12:00:00');

        $task = $this->queuedTask('admission-test');
        $roboNeo = $this->createMock(RoboNeoMotionApi::class);
        $roboNeo->method('quote')->willReturnCallback(function (
            string $imagePath,
            string $videoPath,
            string $accessToken,
            int $duration,
            array $settings,
        ): array {
            if ($accessToken === 'token-nine') {
                throw new RoboNeoProtocolException('Could not resolve RoboNeo uid.', 'missing_uid');
            }

            return [
                'room_id' => 'room-token-ten',
                'motion_node_id' => 'node-token-ten',
                'quoted_cost' => 72,
                'image_asset' => ['url' => 'https://assets.example.com/image.jpg'],
                'video_asset' => ['url' => 'https://assets.example.com/video.mp4'],
                'session_data' => [
                    'gid' => data_get($settings, 'credentials.gid'),
                    'uid' => 'uid-token-ten',
                    'cookies' => [],
                ],
                'submission_trace_id' => 'trace-token-ten',
                'submission_seed' => 'seed-token-ten',
            ];
        });
        $roboNeo->method('submit')->willReturn([
            'task_id' => 'provider-token-ten',
            'session_data' => ['gid' => 'gid-token-ten', 'uid' => 'uid-token-ten', 'cookies' => []],
        ]);

        $tokenNineDeactivated = false;
        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $tokens->method('getActiveTokens')->willReturnCallback(
            static function () use (&$tokenNineDeactivated): array {
                return array_values(array_filter([
                    ['id' => 9, 'token_api' => 'token-nine'],
                    ['id' => 10, 'token_api' => 'token-ten'],
                ], static fn (array $token): bool => ! ($tokenNineDeactivated && $token['id'] === 9)));
            },
        );
        $tokens->method('deactivate')->willReturnCallback(
            static function (int $tokenId) use (&$tokenNineDeactivated): bool {
                if ($tokenId === 9) {
                    $tokenNineDeactivated = true;
                }

                return true;
            },
        );
        $tasks = $this->createMock(ExternalVideoTaskInterface::class);
        $tasks->method('findByTaskId')->willReturn($task);
        $trimmer = $this->createMock(MotionVideoTrimmer::class);
        $trimmer->method('trim')->willReturnCallback(static fn (string $path): string => $path);
        $service = new ExternalVideoTaskService(
            $roboNeo,
            $tokens,
            $tasks,
            $trimmer,
            $this->createMock(R2VideoStorageService::class),
        );

        $service->submitPendingRoboNeoTask($task);
        Carbon::setTestNow(Carbon::parse((string) data_get($task->payload, 'roboneo.submission.next_retry_at'))->addSecond());
        $service->submitPendingRoboNeoTask($task);

        $this->assertTrue($tokenNineDeactivated);
        $this->assertSame('provider-token-ten', data_get($task->payload, 'roboneo.task_id'));
        $this->assertSame(10, data_get($task->payload, 'roboneo.api_token_id'));
        $this->assertSame(
            ['credential_invalid', 'accepted'],
            array_column(data_get($task->payload, 'roboneo.submission.history', []), 'status'),
        );
        Http::assertSentCount(2);
    }

    private function queuedTask(string $taskId, ?Carbon $deadline = null): AdmissionInMemoryExternalVideoTask
    {
        $task = new AdmissionInMemoryExternalVideoTask;
        $task->forceFill([
            'task_id' => $taskId,
            'status' => 'PROCESSING',
            'payload' => [
                'url_image' => 'https://z-test.test/image.jpg',
                'url_video' => 'https://z-test.test/video.mp4',
                'roboneo' => [
                    'submission' => [
                        'attempt' => 0,
                        'state' => 'queued',
                        'queued_at' => now()->toISOString(),
                        'deadline_at' => ($deadline ?: now()->addMinutes(50))->toISOString(),
                        'history' => [],
                    ],
                ],
            ],
        ]);

        return $task;
    }

    private function admissionService(
        AdmissionInMemoryExternalVideoTask $task,
        RoboNeoMotionApi $roboNeo,
    ): ExternalVideoTaskService {
        $tokens = $this->createMock(AiVideoApiTokenInterface::class);
        $tokens->method('getActiveTokens')->willReturn([
            ['id' => 9, 'token_api' => 'token-nine'],
            ['id' => 10, 'token_api' => 'token-ten'],
        ]);

        $tasks = $this->createMock(ExternalVideoTaskInterface::class);
        $tasks->method('findByTaskId')->willReturn($task);

        $trimmer = $this->createMock(MotionVideoTrimmer::class);
        $trimmer->method('trim')->willReturnCallback(static fn (string $path): string => $path);

        return new ExternalVideoTaskService(
            $roboNeo,
            $tokens,
            $tasks,
            $trimmer,
            $this->createMock(R2VideoStorageService::class),
        );
    }
}

class AdmissionInMemoryExternalVideoTask extends Model
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
