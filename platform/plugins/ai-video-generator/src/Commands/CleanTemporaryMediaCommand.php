<?php

namespace Botble\AiVideoGenerator\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanTemporaryMediaCommand extends Command
{
    protected $signature = 'ai-video-generator:clean-temporary-media {--days=3 : Delete files older than this number of days}';

    protected $description = 'Clean temporary media uploaded before generating AI videos.';

    protected string $folder = 'ai-video-generator/media';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $expiresAt = Carbon::now()->subDays($days)->timestamp;
        $disk = Storage::disk('public');
        $deleted = 0;

        foreach ($disk->allFiles($this->folder) as $path) {
            try {
                if ($disk->lastModified($path) > $expiresAt) {
                    continue;
                }

                if ($disk->delete($path)) {
                    $deleted++;
                }
            } catch (Throwable) {
                continue;
            }
        }

        $this->info(sprintf('Deleted %d temporary AI media file(s) older than %d day(s).', $deleted, $days));

        return self::SUCCESS;
    }
}
