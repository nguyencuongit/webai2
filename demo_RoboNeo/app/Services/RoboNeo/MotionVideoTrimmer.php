<?php

namespace App\Services\RoboNeo;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MotionVideoTrimmer
{
    public const MAX_DURATION_SECONDS = 10;

    public function trim(string $videoPath): string
    {
        $disk = Storage::disk('local');
        $sourcePath = $disk->path($videoPath);

        if ($this->duration($sourcePath) <= self::MAX_DURATION_SECONDS) {
            return $videoPath;
        }

        $directory = pathinfo($videoPath, PATHINFO_DIRNAME);
        $filename = pathinfo($videoPath, PATHINFO_FILENAME);
        $prefix = $directory === '.' ? '' : $directory.'/';
        $trimmedPath = $prefix.$filename.'-trimmed.mp4';
        $temporaryPath = $prefix.'.'.$filename.'-'.Str::uuid().'.tmp.mp4';
        $temporaryAbsolutePath = $disk->path($temporaryPath);

        try {
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
                $temporaryAbsolutePath,
            ]);

            if ($result->failed() || ! $disk->exists($temporaryPath)) {
                throw new RuntimeException('Không thể cắt video xuống 10 giây.');
            }

            if ($this->duration($temporaryAbsolutePath) > self::MAX_DURATION_SECONDS) {
                throw new RuntimeException('Video sau khi cắt vẫn dài hơn 10 giây.');
            }

            if (! $disk->move($temporaryPath, $trimmedPath)) {
                throw new RuntimeException('Không thể lưu video đã cắt.');
            }

            $disk->delete($videoPath);
        } finally {
            $this->deleteTemporaryFile($disk, $temporaryPath);
        }

        return $trimmedPath;
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
            throw new RuntimeException('Không thể đọc thời lượng video.');
        }

        return (float) $duration;
    }

    private function deleteTemporaryFile(FilesystemAdapter $disk, string $temporaryPath): void
    {
        if ($disk->exists($temporaryPath)) {
            $disk->delete($temporaryPath);
        }
    }
}
