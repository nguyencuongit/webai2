<?php

namespace Botble\AiVideoGenerator\Jobs;

use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoTaskPipelineService;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\ExternalRoboNeoTaskSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SubmitExternalRoboNeoTask implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly string $taskId)
    {
        $this->onQueue((string) config('plugins.ai-video-generator.general.roboneo.submit_queue', 'default'));
    }

    public function handle(RoboNeoTaskPipelineService $pipeline, ExternalRoboNeoTaskSource $source): void
    {
        $pipeline->submit($source, $this->taskId);
    }
}
