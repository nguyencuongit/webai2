<?php

namespace Tests\Unit\AiVideoGenerator;

use Botble\AiVideoGenerator\Models\ExternalVideoTask;
use Botble\AiVideoGenerator\Repositories\Eloquent\ExternalVideoTaskRepository;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Models/ExternalVideoTask.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Interfaces/ExternalVideoTaskInterface.php';
require_once dirname(__DIR__, 3).'/platform/plugins/ai-video-generator/src/Repositories/Eloquent/ExternalVideoTaskRepository.php';

class ExternalVideoTaskRepositoryTest extends TestCase
{
    public function test_constructor_initializes_the_base_repository_model(): void
    {
        $repository = new ExternalVideoTaskRepository(new ExternalVideoTask);

        $this->assertInstanceOf(ExternalVideoTask::class, $repository->getModel());
    }
}
