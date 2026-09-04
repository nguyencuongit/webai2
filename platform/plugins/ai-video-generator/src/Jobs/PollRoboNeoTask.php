<?php

namespace Botble\AiVideoGenerator\Jobs;

use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoTaskPipelineService;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\CustomerRoboNeoTaskSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PollRoboNeoTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly string $taskId)
    {
        $this->onQueue((string) config('plugins.ai-video-generator.general.roboneo.poll_queue', 'default'));
    }

    public function handle(RoboNeoTaskPipelineService $pipeline, CustomerRoboNeoTaskSource $source): void
    {
        $pipeline->poll($source, $this->taskId);
    }
}
