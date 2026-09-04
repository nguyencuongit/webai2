<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

interface RoboNeoTaskSource
{
    public function key(): string;

    public function find(string $taskId): ?Model;

    /** @return array{image: string, video: string} */
    public function prepareInputs(Model $task): array;

    public function cleanupInputs(Model $task): void;

    public function dispatchSubmission(string $taskId, ?Carbon $at = null): void;

    public function dispatchPolling(string $taskId, int $delaySeconds): void;

    /** @param array{key: string, url: string} $storedVideo */
    public function complete(Model $task, array $storedVideo): void;

    public function fail(Model $task, string $code, string $message): void;

    public function resumePendingCompletion(Model $task): bool;

    public function isTerminal(Model $task): bool;
}
