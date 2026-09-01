<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Services\R2\R2VideoStorageService;
use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;

class GeneratedMediaCleanupService
{
    public function __construct(protected R2VideoStorageService $r2VideoStorage)
    {
    }

    /**
     * Delete output files for both legacy server-stored media and new R2 videos.
     */
    
    public function delete(array $generated): int
    {
        $mediaUrls = [];
        $deleted = 0;

        foreach ($generated as $media) {
            if (is_string($media) && $media !== '') {
                $mediaUrls[] = $media;

                continue;
            }

            if (! is_array($media)) {
                continue;
            }

            $r2Key = $media['r2_key'] ?? null;

            if (is_string($r2Key) && $r2Key !== '') {
                $this->r2VideoStorage->delete($r2Key);
                $deleted++;
            } elseif (is_string($media['url'] ?? null) && $media['url'] !== '') {
                // Media created before R2 stored both the video and thumbnail locally.
                $mediaUrls[] = $media['url'];
            }

            if (is_string($media['thumbnail'] ?? null) && $media['thumbnail'] !== '') {
                $mediaUrls[] = $media['thumbnail'];
            }
        }

        $mediaUrls = array_values(array_unique($mediaUrls));

        if (! $mediaUrls) {
            return $deleted;
        }

        $files = MediaFile::query()
            ->withoutGlobalScopes()
            ->where('url', 'like', 'ai-generations/%')
            ->get()
            ->filter(fn (MediaFile $file) => in_array(RvMedia::getImageUrl($file->url), $mediaUrls, true));

        foreach ($files as $file) {
            $file->forceDelete();
            $deleted++;
        }

        return $deleted;
    }
}
