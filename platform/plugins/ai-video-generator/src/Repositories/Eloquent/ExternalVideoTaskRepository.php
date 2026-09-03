<?php

namespace Botble\AiVideoGenerator\Repositories\Eloquent;

use Botble\AiVideoGenerator\Models\ExternalVideoTask;
use Botble\AiVideoGenerator\Repositories\Interfaces\ExternalVideoTaskInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Database\Eloquent\Model;

class ExternalVideoTaskRepository extends RepositoriesAbstract implements ExternalVideoTaskInterface
{
    public function __construct(ExternalVideoTask $model)
    {
        parent::__construct($model);
    }

    public function findByTaskId(string $taskId): ?Model
    {
        return $this->getFirstBy(['task_id' => $taskId]);
    }
}
