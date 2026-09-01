<?php

namespace Botble\AiVideoGenerator\Services\R2;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class R2VideoStorageService
{
    protected string $disk = 'r2';

    protected string $folder = 'ai-videos';

    /**
     * Store a completed generated video without using Botble's media disk.
     *
     * @return array{key: string, url: string}
     */
    public function store(string $localPath, string $taskId, ?string $mimeType = null): array
    {
        $this->ensureConfigured();

        if (! is_file($localPath) || ! is_readable($localPath)) {
            throw new RuntimeException('The generated video file is not available for R2 upload.');
        }

        $taskId = trim($taskId);

        if ($taskId === '') {
            throw new RuntimeException('The generated video task ID is required for R2 upload.');
        }

        $key = sprintf('%s/%s.mp4', $this->folder, $taskId);
        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Cannot open the generated video for R2 upload.');
        }

        try {
            $uploaded = Storage::disk($this->disk)->put($key, $stream, array_filter([
                'ContentType' => $mimeType,
            ]));
        } finally {
            // Flysystem may close an uploaded stream itself. Closing it again
            // throws and makes a successful R2 upload look like a failure.
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $uploaded) {
            throw new RuntimeException('R2 did not accept the generated video upload.');
        }

        return [
            'key' => $key,
            'url' => Storage::disk($this->disk)->url($key),
        ];
    }

    public function delete(string $key): void
    {
        if ($key === '') {
            return;
        }

        $this->ensureConfigured();

        if (! Storage::disk($this->disk)->delete($key)) {
            throw new RuntimeException(sprintf('Cannot delete generated video from R2: %s', $key));
        }
    }

    public function temporaryDownloadUrl(string $key, string $filename): string
    {
        $this->ensureConfigured();

        return Storage::disk($this->disk)->temporaryUrl(
            $key,
            now()->addMinutes(5),
            [
                'ResponseContentDisposition' => sprintf('attachment; filename="%s"', addcslashes($filename, '"\\')),
            ],
        );
    }

    protected function ensureConfigured(): void
    {
        foreach (['key', 'secret', 'bucket', 'url', 'endpoint'] as $setting) {
            if (blank(config("filesystems.disks.{$this->disk}.{$setting}"))) {
                throw new RuntimeException('R2 video storage is not fully configured.');
            }
        }
    }
}
