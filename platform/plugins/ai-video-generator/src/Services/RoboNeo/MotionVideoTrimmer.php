<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class MotionVideoTrimmer
{
    public const MAX_DURATION_SECONDS = 10;

    /**
     * Returns the original path when it is already 10 seconds or shorter.
     * Longer videos are re-encoded into a temporary MP4 file.
     */
    public function trim(string $sourcePath): string
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('The RoboNeo input video is not available.');
        }

        if ($this->duration($sourcePath) <= self::MAX_DURATION_SECONDS) {
            return $sourcePath;
        }

        $directory = storage_path('app/ai-video-generator/roboneo-trimmed');
        File::ensureDirectoryExists($directory);
        $outputPath = $directory.'/'.pathinfo($sourcePath, PATHINFO_FILENAME).'-'.Str::uuid().'.mp4';

        $result = Process::timeout(1200)->run([
            'ffmpeg',
            '-nostdin',
            '-hide_banner',
            '-loglevel',
            'error',
            '-y',
            '-i',
            $sourcePath,
            '-t',
            (string) self::MAX_DURATION_SECONDS,
            '-map',
            '0:v:0',
            '-map',
            '0:a:0?',
            '-c:v',
            'libx264',
            '-preset',
            'medium',
            '-crf',
            '20',
            '-pix_fmt',
            'yuv420p',
            '-c:a',
            'aac',
            '-b:a',
            '128k',
            '-movflags',
            '+faststart',
            $outputPath,
        ]);

        if ($result->failed() || ! is_file($outputPath) || filesize($outputPath) === 0) {
            File::delete($outputPath);

            throw new RuntimeException('Cannot trim the RoboNeo input video to 10 seconds.');
        }

        if ($this->duration($outputPath) > self::MAX_DURATION_SECONDS) {
            File::delete($outputPath);

            throw new RuntimeException('The trimmed RoboNeo input video is longer than 10 seconds.');
        }

        return $outputPath;
    }

    private function duration(string $videoPath): float
    {
        $result = Process::timeout(30)->run([
            'ffprobe',
            '-v',
            'error',
            '-show_entries',
            'format=duration',
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $videoPath,
        ]);
        $duration = trim($result->output());

        if ($result->failed() || ! is_numeric($duration) || (float) $duration <= 0) {
            throw new RuntimeException('Cannot read the RoboNeo input video duration.');
        }

        return (float) $duration;
    }
}
