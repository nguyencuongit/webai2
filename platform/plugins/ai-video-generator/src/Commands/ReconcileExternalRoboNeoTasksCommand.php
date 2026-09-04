<?php

namespace Botble\AiVideoGenerator\Commands;

use Botble\AiVideoGenerator\Services\RoboNeo\ExternalRoboNeoWatchdog;
use Illuminate\Console\Command;

class ReconcileExternalRoboNeoTasksCommand extends Command
{
    protected $signature = 'ai-video-generator:reconcile-external-roboneo {--limit=500}';

    protected $description = 'Recover stale RoboNeo submission, polling, and callback jobs';

    public function handle(ExternalRoboNeoWatchdog $watchdog): int
    {
        $recovered = $watchdog->recover(max(1, (int) $this->option('limit')));

        $this->components->info(sprintf('Queued %d stale RoboNeo task(s) for reconciliation.', $recovered));

        return self::SUCCESS;
    }
}
