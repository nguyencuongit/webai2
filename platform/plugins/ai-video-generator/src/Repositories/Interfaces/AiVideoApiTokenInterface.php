<?php

namespace Botble\AiVideoGenerator\Repositories\Interfaces;

use Botble\Support\Repositories\Interfaces\RepositoryInterface;

interface AiVideoApiTokenInterface extends RepositoryInterface
{
    /**
     * @return list<array{id: int, token_api: string}>
     */
    public function getActiveTokens(): array;

    /**
     * @return array{id: int, token_api: string}|null
     */
    public function getLatestActiveToken(): ?array;

    public function deactivate(int $id): bool;
}
