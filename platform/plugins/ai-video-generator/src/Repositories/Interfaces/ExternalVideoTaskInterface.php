<?php

namespace Botble\AiVideoGenerator\Repositories\Interfaces;

use Botble\Support\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

interface ExternalVideoTaskInterface extends RepositoryInterface
{
    public function findByTaskId(string $taskId): ?Model;
}
