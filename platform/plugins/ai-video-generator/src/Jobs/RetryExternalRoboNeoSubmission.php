<?php

namespace Botble\AiVideoGenerator\Jobs;

use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoTaskPipelineService;
use Botble\AiVideoGenerator\Services\RoboNeo\Sources\ExternalRoboNeoTaskSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RetryExternalRoboNeoSubmission implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly string $taskId,
        public readonly int $expectedAttempt,
    ) {}

    public function handle(RoboNeoTaskPipelineService $pipeline, ExternalRoboNeoTaskSource $source): void
    {
        $pipeline->submit($source, $this->taskId);
    }
}
