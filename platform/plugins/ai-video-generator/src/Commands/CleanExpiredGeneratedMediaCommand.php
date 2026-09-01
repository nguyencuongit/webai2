<?php

namespace Botble\AiVideoGenerator\Commands;

use Botble\AiVideoGenerator\Models\AiGenerationTask;
use Botble\AiVideoGenerator\Services\GeneratedMediaCleanupService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanExpiredGeneratedMediaCommand extends Command
{
    protected $signature = 'ai-video-generator:clean-expired-generated-media {--days=3 : Delete generated media older than this number of days}';

    protected $description = 'Delete generated AI media after its retention period expires.';

    public function handle(GeneratedMediaCleanupService $mediaCleanupService): int
    {
        $days = max(1, (int) $this->option('days'));
        $expiresAt = Carbon::now()->subDays($days);
        $deletedTasks = 0;
        $deletedFiles = 0;

        AiGenerationTask::query()
            ->where('is_completed', true)
            ->whereNotNull('generated')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $expiresAt)
            ->orderBy('id')
            ->chunkById(100, function ($tasks) use ($mediaCleanupService, &$deletedTasks, &$deletedFiles): void {
                foreach ($tasks as $task) {
                    $generated = array_values(array_filter($task->generated ?? []));

                    if (! $generated) {
                        continue;
                    }

                    try {
                        $deletedFiles += $mediaCleanupService->delete($generated);

                        // Preserve the task for administration/auditing while removing its expired output.
                        $task->update(['generated' => null]);
                        $deletedTasks++;
                    } catch (Throwable $exception) {
                        Log::error('Cannot clean expired AI generated media.', [
                            'task_id' => $task->task_id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info(sprintf(
            'Deleted %d generated media file(s) from %d expired AI task(s) older than %d day(s).',
            $deletedFiles,
            $deletedTasks,
            $days
        ));

        return self::SUCCESS;
    }
}
