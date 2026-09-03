<?php

namespace Tests\Feature\AiVideoGenerator;

use Botble\AiVideoGenerator\Services\RoboNeo\MotionVideoTrimmer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Services/RoboNeo/MotionVideoTrimmer.php';

class MotionVideoTrimmerTest extends TestCase
{
    public function test_it_accepts_a_trimmed_video_with_a_small_container_duration_overrun(): void
    {
        $directory = storage_path('framework/testing/roboneo-trimmer');
        $sourcePath = $directory.'/source-'.Str::uuid().'.mp4';
        $trimmedPath = null;

        File::ensureDirectoryExists($directory);

        $generate = new Process([
            'ffmpeg', '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
            '-f', 'lavfi', '-i', 'color=c=black:s=32x32:r=23.976:d=10.2',
            '-f', 'lavfi', '-i', 'anullsrc=r=48000:cl=stereo:d=10.2',
            '-shortest', '-c:v', 'libx264', '-preset', 'ultrafast',
            '-pix_fmt', 'yuv420p', '-c:a', 'aac', $sourcePath,
        ]);
        $generate->setTimeout(30)->mustRun();

        try {
            $trimmedPath = (new MotionVideoTrimmer)->trim($sourcePath);

            $this->assertFileExists($trimmedPath);
            $this->assertGreaterThan(10.0, $this->duration($trimmedPath));
            $this->assertLessThanOrEqual(10.1, $this->duration($trimmedPath));
        } finally {
            File::delete(array_filter([$sourcePath, $trimmedPath]));
        }
    }

    private function duration(string $path): float
    {
        $probe = new Process([
            'ffprobe', '-v', 'error', '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1', $path,
        ]);
        $probe->setTimeout(10)->mustRun();

        return (float) trim($probe->getOutput());
    }
}
