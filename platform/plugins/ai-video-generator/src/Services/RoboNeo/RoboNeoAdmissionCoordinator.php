<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

class RoboNeoAdmissionCoordinator
{
    private const GLOBAL_COOLDOWN = 'roboneo:admission:global-cooldown-until';

    private const GLOBAL_LOCK = 'roboneo:admission:global-submit';

    private const TOKEN_COOLDOWN = 'roboneo:admission:token:%d:cooldown-until';

    private const TOKEN_LAST_USED = 'roboneo:admission:token:%d:last-used';

    private const TOKEN_LOCK = 'roboneo:admission:token:%d';

    /**
     * @param  list<array{id: int, token_api: string}>  $tokens
     * @param  list<int>  $excludedIds
     */
    public function leaseToken(array $tokens, array $excludedIds = []): ?RoboNeoTokenLease
    {
        usort($tokens, function (array $left, array $right) use ($excludedIds): int {
            $leftExcluded = in_array((int) $left['id'], $excludedIds, true);
            $rightExcluded = in_array((int) $right['id'], $excludedIds, true);

            return [$leftExcluded, $this->lastUsedAt((int) $left['id']), (int) $left['id']]
                <=> [$rightExcluded, $this->lastUsedAt((int) $right['id']), (int) $right['id']];
        });

        foreach ($tokens as $token) {
            $tokenId = (int) $token['id'];

            if ($this->tokenCooldownUntil($tokenId) > now()->getTimestamp()) {
                continue;
            }

            $lock = Cache::lock(
                sprintf(self::TOKEN_LOCK, $tokenId),
                (int) config('plugins.ai-video-generator.general.roboneo.motion.token_lease_seconds', 600),
            );

            if ($lock->get()) {
                return new RoboNeoTokenLease($token, $lock);
            }
        }

        return null;
    }

    public function acquireSubmitGate(): ?Lock
    {
        if ($this->globalCooldownUntil() > now()->getTimestamp()) {
            return null;
        }

        $lock = Cache::lock(
            self::GLOBAL_LOCK,
            (int) config('plugins.ai-video-generator.general.roboneo.motion.global_submit_lock_seconds', 180),
        );

        return $lock->get() ? $lock : null;
    }

    public function cooldownToken(int $tokenId, DateTimeInterface $until): void
    {
        Cache::put(sprintf(self::TOKEN_COOLDOWN, $tokenId), $until->getTimestamp(), $until);
    }

    public function cooldownGlobal(DateTimeInterface $until): void
    {
        Cache::put(self::GLOBAL_COOLDOWN, $until->getTimestamp(), $until);
    }

    public function markTokenUsed(int $tokenId, DateTimeInterface $at): void
    {
        Cache::forever(sprintf(self::TOKEN_LAST_USED, $tokenId), $at->getTimestamp());
    }

    public function globalCooldownUntil(): int
    {
        return (int) Cache::get(self::GLOBAL_COOLDOWN, 0);
    }

    public function tokenCooldownUntil(int $tokenId): int
    {
        return (int) Cache::get(sprintf(self::TOKEN_COOLDOWN, $tokenId), 0);
    }

    private function lastUsedAt(int $tokenId): int
    {
        return (int) Cache::get(sprintf(self::TOKEN_LAST_USED, $tokenId), 0);
    }
}
