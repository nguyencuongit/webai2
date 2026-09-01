<?php

namespace Tests\Unit\Services\RoboNeo;

use App\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class MotionVideoTrimmerTest extends TestCase
{
    public function test_video_at_or_below_ten_seconds_is_left_unchanged(): void
    {
        Storage::fake('local');
        $videoPath = 'motion/motion.mp4';
        Storage::disk('local')->put($videoPath, 'original-video');
        Process::fake([
            '*ffprobe*' => Process::result(output: "10.000000\n"),
        ])->preventStrayProcesses();

        $preparedPath = app(MotionVideoTrimmer::class)->trim($videoPath);

        $this->assertSame($videoPath, $preparedPath);
        $this->assertSame('original-video', Storage::disk('local')->get($videoPath));
        Process::assertRanTimes(fn (PendingProcess $process): bool => $process->command[0] === 'ffprobe', 1);
    }

    public function test_video_longer_than_ten_seconds_is_replaced_with_trimmed_output(): void
    {
        Storage::fake('local');
        $videoPath = 'motion/motion.mov';
        Storage::disk('local')->put($videoPath, 'original-video');
        $probeCount = 0;
        Process::fake(function (PendingProcess $process) use (&$probeCount) {
            if ($process->command[0] === 'ffprobe') {
                $probeCount++;

                return Process::result(output: $probeCount === 1 ? "12.300000\n" : "10.000000\n");
            }

            if ($process->command[0] === 'ffmpeg'
                && in_array('-t', $process->command, true)
                && in_array('10', $process->command, true)) {
                file_put_contents($process->command[array_key_last($process->command)], 'trimmed-video');

                return Process::result();
            }

            return Process::result(errorOutput: 'Unexpected process command.', exitCode: 1);
        })->preventStrayProcesses();

        $preparedPath = app(MotionVideoTrimmer::class)->trim($videoPath);

        $this->assertSame('motion/motion-trimmed.mp4', $preparedPath);
        $this->assertSame('trimmed-video', Storage::disk('local')->get($preparedPath));
        Storage::disk('local')->assertMissing($videoPath);
        Process::assertRanTimes(fn (PendingProcess $process): bool => in_array($process->command[0], ['ffprobe', 'ffmpeg'], true), 3);
    }

    public function test_failed_transcode_keeps_the_original_video(): void
    {
        Storage::fake('local');
        $videoPath = 'motion/motion.mp4';
        Storage::disk('local')->put($videoPath, 'original-video');
        Process::fake([
            '*ffprobe*' => Process::result(output: "12.300000\n"),
            '*ffmpeg*' => Process::result(errorOutput: 'Encoder failed.', exitCode: 1),
        ])->preventStrayProcesses();

        try {
            app(MotionVideoTrimmer::class)->trim($videoPath);
            $this->fail('Expected the video trim to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Không thể cắt video xuống 10 giây.', $exception->getMessage());
        }

        $this->assertSame('original-video', Storage::disk('local')->get($videoPath));
        Storage::disk('local')->assertMissing('motion/motion-trimmed.mp4');
        Process::assertRanTimes(fn (PendingProcess $process): bool => in_array($process->command[0], ['ffprobe', 'ffmpeg'], true), 2);
    }

    public function test_output_over_ten_seconds_is_rejected_and_original_is_kept(): void
    {
        Storage::fake('local');
        $videoPath = 'motion/motion.mp4';
        Storage::disk('local')->put($videoPath, 'original-video');
        $probeCount = 0;
        Process::fake(function (PendingProcess $process) use (&$probeCount) {
            if ($process->command[0] === 'ffprobe') {
                $probeCount++;

                return Process::result(output: $probeCount === 1 ? "12.300000\n" : "10.200000\n");
            }

            file_put_contents($process->command[array_key_last($process->command)], 'too-long-video');

            return Process::result();
        })->preventStrayProcesses();

        try {
            app(MotionVideoTrimmer::class)->trim($videoPath);
            $this->fail('Expected the trimmed duration to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Video sau khi cắt vẫn dài hơn 10 giây.', $exception->getMessage());
        }

        $this->assertSame('original-video', Storage::disk('local')->get($videoPath));
        Storage::disk('local')->assertMissing('motion/motion-trimmed.mp4');
        Process::assertRanTimes(fn (PendingProcess $process): bool => in_array($process->command[0], ['ffprobe', 'ffmpeg'], true), 3);
    }
}
