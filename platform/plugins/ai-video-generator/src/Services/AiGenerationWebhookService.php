<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Repositories\Interfaces\AiGenerationTaskInterface;
use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class AiGenerationWebhookService
{
    protected string $mediaFolder = 'ai-generations';

    public function __construct(
        protected AiGenerationTaskInterface $taskRepository,
        protected R2VideoStorageService $r2VideoStorage,
    )
    {
    }

    public function handle(array $webhookData)
    {
        $webhookData = $this->storeGeneratedFiles($webhookData);

        return $this->taskRepository->updateFromWebhook($webhookData);
    }

    protected function storeGeneratedFiles(array $webhookData): array
    {
        $generated = $webhookData['task']['generated'] ?? [];
        $taskId = $webhookData['task']['task_id'] ?? null;

        if (! is_array($generated) || empty($generated)) {
            return $webhookData;
        }

        if (! is_string($taskId) || trim($taskId) === '') {
            Log::warning('Cannot store AI generated media without a task ID.');

            return $webhookData;
        }

        $storedFiles = [];

        foreach ($generated as $url) {
            if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $storedFiles[] = $this->storeGeneratedFile($url, $taskId);
        }

        $webhookData['task']['generated'] = array_values(array_filter($storedFiles));

        return $webhookData;
    }

    protected function storeGeneratedFile(string $url, string $taskId): string|array
    {
        $temporaryBasePath = tempnam(storage_path('app'), 'ai-video-');

        if ($temporaryBasePath === false) {
            Log::warning('Cannot create a temporary file for AI generated media.', ['url' => $url]);

            return $url;
        }

        $temporaryPath = $temporaryBasePath . '.' . $this->fileExtension($url);
        $thumbnailPath = null;
        File::move($temporaryBasePath, $temporaryPath);

        try {
            $response = Http::timeout(300)
                ->sink($temporaryPath)
                ->get($url);

            if ($response->failed() || ! File::exists($temporaryPath) || File::size($temporaryPath) === 0) {
                Log::warning('Cannot download AI generated file from webhook URL.', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return $url;
            }

            if ($this->isVideo($url)) {
                $storedVideo = $this->r2VideoStorage->store(
                    $temporaryPath,
                    $taskId,
                    $this->mimeType($url),
                );

                $thumbnailPath = $temporaryBasePath . '.webp';

                return [
                    'url' => $storedVideo['url'],
                    'r2_key' => $storedVideo['key'],
                    'thumbnail' => $this->storeVideoThumbnail($temporaryPath, $thumbnailPath, $url),
                ];
            }

            $result = RvMedia::uploadFromPath($temporaryPath, 0, $this->mediaFolder, $this->mimeType($url));

            if (! empty($result['error'])) {
                Log::warning('Cannot store AI generated file from webhook URL.', [
                    'url' => $url,
                    'message' => $result['message'] ?? null,
                ]);

                return $url;
            }

            $file = $result['data'] ?? null;

            if (! $file) {
                return $url;
            }

            $storedUrl = RvMedia::getImageUrl($file->url) ?: $url;

            return $storedUrl;
        } catch (Throwable $exception) {
            Log::warning('Cannot download AI generated file from webhook URL.', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return $url;
        } finally {
            File::delete($temporaryPath);

            if ($thumbnailPath) {
                File::delete($thumbnailPath);
            }
        }
    }

    protected function storeVideoThumbnail(string $videoPath, string $thumbnailPath, string $sourceUrl): ?string
    {
        try {
            $process = new Process([
                'ffmpeg',
                '-y',
                '-ss',
                '0.5',
                '-i',
                $videoPath,
                '-frames:v',
                '1',
                '-vf',
                'scale=640:-2',
                $thumbnailPath,
            ]);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful() || ! File::exists($thumbnailPath) || File::size($thumbnailPath) === 0) {
                Log::warning('Cannot create AI generated video thumbnail.', [
                    'url' => $sourceUrl,
                ]);

                return null;
            }

            $result = RvMedia::uploadFromPath($thumbnailPath, 0, $this->mediaFolder, 'image/webp');

            if (! empty($result['error']) || empty($result['data'])) {
                Log::warning('Cannot store AI generated video thumbnail.', [
                    'url' => $sourceUrl,
                    'response' => $result,
                ]);

                return null;
            }

            return RvMedia::getImageUrl($result['data']->url) ?: null;
        } catch (Throwable $exception) {
            Log::warning('Cannot create AI generated video thumbnail.', [
                'url' => $sourceUrl,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function isVideo(string $url): bool
    {
        return in_array($this->fileExtension($url), ['mp4', 'mov', 'webm'], true);
    }

    protected function fileExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path ?: '', PATHINFO_EXTENSION));

        return $extension ?: 'bin';
    }

    protected function mimeType(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path ?: '', PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            default => null,
        };
    }

}
