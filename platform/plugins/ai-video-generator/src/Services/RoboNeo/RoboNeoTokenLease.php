<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo;

use Illuminate\Contracts\Cache\Lock;
use Throwable;

class RoboNeoTokenLease
{
    private bool $released = false;

    public readonly int $tokenId;

    public readonly string $accessToken;

    /** @param array{id: int, token_api: string} $token */
    public function __construct(
        public readonly array $token,
        private readonly Lock $lock,
    ) {
        $this->tokenId = (int) $token['id'];
        $this->accessToken = trim((string) $token['token_api']);
    }

    public function release(): void
    {
        if ($this->released) {
            return;
        }

        try {
            $this->lock->release();
        } catch (Throwable) {
        }

        $this->released = true;
    }

    public function __destruct()
    {
        $this->release();
    }
}
