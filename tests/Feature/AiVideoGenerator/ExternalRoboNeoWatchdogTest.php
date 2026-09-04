<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Models\ExternalVideoTask;
use Botble\AiVideoGenerator\Services\RoboNeo\ExternalRoboNeoWatchdog;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\ExternalRoboNeoTaskSource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Api/RoboNeo/RoboNeoProtocolException.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/ExternalVideoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/ExternalVideoTaskInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/MotionVideoTrimmer.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/Contracts/RoboNeoTaskSource.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/Sources/ExternalRoboNeoTaskSource.php';
$watchdogFile = dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/ExternalRoboNeoWatchdog.php';
if (is_file($watchdogFile)) {
    require_once $watchdogFile;
}

class ExternalRoboNeoWatchdogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('plugins.ai-video-generator.general.roboneo.recovery_stale_seconds', 60);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Carbon::setTestNow('2026-09-04 09:30:00');

        Schema::create('ai_video_external_tasks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('task_id')->unique();
            $table->text('url_image');
            $table->text('url_video');
            $table->string('status');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_stale_admission_callback_and_poll_jobs_are_recovered_without_touching_fresh_tasks(): void
    {
        $this->assertTrue(class_exists(ExternalRoboNeoWatchdog::class));

        $this->task('stale-admission', 'PROCESSING', [
            'roboneo' => ['submission' => ['state' => 'retry_scheduled']],
        ], true);
        $this->task('stale-provider', 'PROCESSING', [
            'roboneo' => ['task_id' => 'provider-task'],
        ], true);
        $this->task('overdue-provider', 'PROCESSING', [
            'roboneo' => [
                'task_id' => 'overdue-provider-task',
                'processing_deadline_at' => now()->subSecond()->toISOString(),
            ],
        ], true);
        $this->task('stale-callback', 'CALLBACK_PENDING', [
            'roboneo' => ['task_id' => 'provider-callback'],
        ], true);
        $this->task('fresh-admission', 'PROCESSING', [
            'roboneo' => ['submission' => ['state' => 'queued']],
        ], false);

        $submissionIds = [];
        $pollIds = [];
        $failedIds = [];
        $source = $this->createMock(ExternalRoboNeoTaskSource::class);
        $source->method('dispatchSubmission')->willReturnCallback(
            static function (string $taskId) use (&$submissionIds): void {
                $submissionIds[] = $taskId;
            },
        );
        $source->method('dispatchPolling')->willReturnCallback(
            static function (string $taskId, int $delay) use (&$pollIds): void {
                $pollIds[] = [$taskId, $delay];
            },
        );
        $source->method('fail')->willReturnCallback(
            static function (ExternalVideoTask $task, string $code) use (&$failedIds): void {
                $failedIds[] = [$task->task_id, $code];
            },
        );

        $recovered = (new ExternalRoboNeoWatchdog($source))->recover();

        sort($submissionIds);
        $this->assertSame(['stale-admission', 'stale-callback'], $submissionIds);
        $this->assertSame([['stale-provider', 0]], $pollIds);
        $this->assertSame([['overdue-provider', 'POLLING_TIMEOUT']], $failedIds);
        $this->assertSame(4, $recovered);
    }

    private function task(string $taskId, string $status, array $payload, bool $stale): void
    {
        ExternalVideoTask::query()->create([
            'task_id' => $taskId,
            'url_image' => 'https://input.test/image.jpg',
            'url_video' => 'https://input.test/video.mp4',
            'status' => $status,
            'payload' => $payload,
        ]);
        DB::table('ai_video_external_tasks')->where('task_id', $taskId)->update([
            'updated_at' => $stale ? now()->subSeconds(61) : now(),
        ]);
    }
}
