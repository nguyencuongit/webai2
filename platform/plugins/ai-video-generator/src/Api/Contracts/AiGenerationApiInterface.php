<?php

namespace Botble\AiVideoGenerator\Api\Contracts;

interface AiGenerationApiInterface
{
    public function create(array|string $payload): array;

    public function getTask(string $taskId): array;
}
