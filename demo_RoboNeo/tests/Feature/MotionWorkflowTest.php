<?php

namespace Tests\Feature;

use App\Jobs\PollMotionJob;
use App\Jobs\SubmitMotionJob;
use App\Models\MotionJob;
use App\Models\RoboNeoAccount;
use App\Services\RoboNeo\Contracts\RoboNeoGateway;
use App\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MotionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_can_be_quoted_confirmed_and_completed(): void
    {
        config()->set('roboneo.live_enabled', false);
        Storage::fake('local');
        $this->mock(MotionVideoTrimmer::class)
            ->shouldReceive('trim')
            ->once()
            ->withArgs(fn (string $path): bool => str_ends_with($path, '/motion.mp4'))
            ->andReturnUsing(fn (string $path): string => $path);

        $response = $this->post(route('motion.store'), [
            'prompt' => 'Preserve the character identity.',
            'quality' => 'std',
            'duration_seconds' => 10,
            'image' => UploadedFile::fake()->image('character.jpg', 720, 1280),
            'video' => UploadedFile::fake()->create('motion.mp4', 512, 'video/mp4'),
        ]);

        $job = MotionJob::query()->sole();
        $response->assertRedirect(route('motion.show', $job));
        $this->assertSame(MotionJob::STATUS_AWAITING_CONFIRMATION, $job->status);
        $this->assertSame(72, $job->quoted_cost);
        $this->assertTrue($job->dry_run);
        Storage::disk('local')->assertExists($job->image_path);
        Storage::disk('local')->assertExists($job->video_path);

        Queue::fake();
        $this->post(route('motion.confirm', $job))->assertRedirect(route('motion.show', $job));
        $job->refresh();
        $this->assertSame(MotionJob::STATUS_SUBMITTED, $job->status);
        Queue::assertPushed(SubmitMotionJob::class, fn (SubmitMotionJob $queued): bool => $queued->motionJobId === $job->id);

        $gateway = app(RoboNeoGateway::class);
        (new SubmitMotionJob($job->id))->handle($gateway);
        $this->assertSame(MotionJob::STATUS_PROCESSING, $job->fresh()->status);

        (new PollMotionJob($job->id))->handle($gateway);
        $job->refresh();
        $this->assertSame(MotionJob::STATUS_COMPLETED, $job->status);
        $this->assertNull($job->result_url);
        $this->assertNotNull($job->completed_at);
    }

    public function test_quote_validates_motion_media(): void
    {
        $this->post(route('motion.store'), [
            'prompt' => 'Test',
            'quality' => 'std',
            'duration_seconds' => 31,
            'image' => UploadedFile::fake()->create('character.txt', 1, 'text/plain'),
            'video' => UploadedFile::fake()->create('motion.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors(['duration_seconds', 'image', 'video']);
    }

    public function test_trimmed_video_is_quoted_as_an_mp4_asset(): void
    {
        config()->set('roboneo.live_enabled', false);
        Storage::fake('local');
        $this->mock(MotionVideoTrimmer::class)
            ->shouldReceive('trim')
            ->once()
            ->andReturnUsing(function (string $path): string {
                $trimmedPath = str_replace('/motion.mov', '/motion-trimmed.mp4', $path);
                Storage::disk('local')->put($trimmedPath, 'trimmed-video');
                Storage::disk('local')->delete($path);

                return $trimmedPath;
            });

        $response = $this->post(route('motion.store'), [
            'prompt' => 'Preserve the character identity.',
            'quality' => 'std',
            'duration_seconds' => 10,
            'image' => UploadedFile::fake()->image('character.jpg', 720, 1280),
            'video' => UploadedFile::fake()->create('reference.mov', 512, 'video/quicktime'),
        ]);

        $job = MotionJob::query()->sole();
        $response->assertRedirect(route('motion.show', $job));
        $this->assertSame('reference.mp4', $job->video_original_name);
        $this->assertStringEndsWith('/motion-trimmed.mp4', $job->video_path);
        Storage::disk('local')->assertExists($job->video_path);
    }

    public function test_live_quote_requires_an_active_roboneo_account(): void
    {
        config()->set('roboneo.live_enabled', true);
        Storage::fake('local');

        $this->post(route('motion.store'), [
            'prompt' => 'Preserve identity.',
            'quality' => 'std',
            'duration_seconds' => 10,
            'image' => UploadedFile::fake()->image('character.jpg'),
            'video' => UploadedFile::fake()->create('motion.mp4', 512, 'video/mp4'),
        ])->assertSessionHasErrors([
            'roboneo_account_id' => 'Hãy chọn một tài khoản RoboNeo đang hoạt động.',
        ]);

        $this->assertSame(0, MotionJob::query()->count());
    }

    public function test_live_form_selects_the_default_roboneo_account(): void
    {
        config()->set('roboneo.live_enabled', true);
        $other = RoboNeoAccount::factory()->create(['label' => 'Other account']);
        $default = RoboNeoAccount::factory()->default()->create(['label' => 'Default account']);

        $response = $this->get(route('motion.index'));

        $response->assertOk();
        $response->assertSee('Default account (mặc định)');
        $response->assertSee("value=\"{$default->id}\" selected", false);
        $response->assertSee("value=\"{$other->id}\"", false);
        $response->assertDontSee($default->access_token);
    }

    public function test_live_quote_is_locked_to_the_selected_roboneo_account(): void
    {
        config()->set('roboneo.live_enabled', true);
        Storage::fake('local');
        $account = RoboNeoAccount::factory()->default()->create();
        $this->mock(MotionVideoTrimmer::class)
            ->shouldReceive('trim')
            ->once()
            ->andReturnUsing(fn (string $path): string => $path);
        $this->mock(RoboNeoGateway::class)
            ->shouldReceive('quote')
            ->once()
            ->withArgs(fn (MotionJob $job): bool => $job->roboneo_account_id === $account->id)
            ->andReturn([
                'room_id' => 'room-123',
                'motion_node_id' => 'node-123',
                'quoted_cost' => 72,
                'session_data' => [],
            ]);

        $response = $this->post(route('motion.store'), [
            'roboneo_account_id' => $account->id,
            'prompt' => 'Preserve identity.',
            'quality' => 'std',
            'duration_seconds' => 10,
            'image' => UploadedFile::fake()->image('character.jpg'),
            'video' => UploadedFile::fake()->create('motion.mp4', 512, 'video/mp4'),
        ]);

        $job = MotionJob::query()->sole();
        $response->assertRedirect(route('motion.show', $job));
        $this->assertSame($account->id, $job->roboneo_account_id);
        $this->assertSame(MotionJob::STATUS_AWAITING_CONFIRMATION, $job->status);
    }
}
