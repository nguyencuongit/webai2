<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy;

use Botble\AiVideoGenerator\Api\RoboNeo\RoboNeoProtocolException;
use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Models\KiotProxyKey;
use Botble\AiVideoGenerator\Models\KiotProxyTaskLease;
use Botble\AiVideoGenerator\Models\RoboNeoNetworkIncident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class KiotProxyManager
{
    public function __construct(protected KiotProxyApiClient $client) {}

    public function hasConfiguredKeys(): bool
    {
        return Schema::hasTable('ai_video_kiot_proxy_keys')
            && KiotProxyKey::query()->where('is_active', true)->exists();
    }

    public function acquire(string $owner, Carbon $leaseUntil, ?int $preferredKeyId = null): ?KiotProxyLease
    {
        if ($this->circuitOpenUntil()?->isFuture()) {
            return null;
        }

        $excluded = [];
        $strictPreferredKey = $preferredKeyId !== null;

        while (true) {
            $key = $this->reserveKey($owner, $leaseUntil, $preferredKeyId, $excluded);

            if (! $key) {
                return null;
            }

            try {
                $endpoint = Cache::lock('roboneo:kiot-proxy:endpoint:'.$key->getKey(), 90)
                    ->block(60, function () use ($key): KiotProxyEndpoint {
                        $key = $key->fresh();
                        $endpoint = $this->resolveEndpoint($key);
                        $key->forceFill([
                            'current_http_proxy' => $endpoint->httpProxy,
                            'current_socks5_proxy' => $endpoint->socks5Proxy,
                            'proxy_fingerprint' => $endpoint->fingerprint,
                            'location' => $endpoint->location,
                            'expiration_at' => $endpoint->expirationAt,
                            'next_request_at' => $endpoint->nextRequestAt,
                            'rotate_required' => false,
                            'health_status' => 'healthy',
                            'blocked_until' => null,
                            'last_used_at' => now(),
                        ])->save();

                        return $endpoint;
                    });

                return new KiotProxyLease(
                    (int) $key->getKey(),
                    $owner,
                    'kiot-key-'.$key->getKey(),
                    $endpoint->httpProxy,
                    $endpoint->fingerprint,
                );
            } catch (RoboNeoProtocolException $exception) {
                $this->discardTaskLease((int) $key->getKey(), $owner);
                $this->markKeyApiFailure($key, $exception);

                if ($strictPreferredKey) {
                    return null;
                }

                $excluded[] = (int) $key->getKey();
                $preferredKeyId = null;
            } catch (Throwable $exception) {
                $this->discardTaskLease((int) $key->getKey(), $owner);

                throw $exception;
            }
        }
    }

    public function forTask(int $keyId, string $owner): KiotProxyLease
    {
        $taskLease = KiotProxyTaskLease::query()
            ->where('kiot_proxy_key_id', $keyId)
            ->where('owner', $owner)
            ->where('lease_until', '>', now())
            ->first();
        $key = KiotProxyKey::query()->find($keyId);

        if (! $taskLease || ! $key || ! $key->current_http_proxy) {
            throw new RoboNeoProtocolException(
                'The KiotProxy lease for this RoboNeo task is unavailable.',
                'kiot_proxy_lease_unavailable',
            );
        }

        if ($key->expiration_at?->lte(now()->addSeconds(15))) {
            $endpoint = $this->client->current((string) $key->proxy_key);

            if (! $endpoint || $endpoint->fingerprint !== $key->proxy_fingerprint) {
                throw new RoboNeoProtocolException(
                    'The KiotProxy session changed before the RoboNeo task completed.',
                    'kiot_proxy_session_changed',
                );
            }

            $key->forceFill([
                'current_http_proxy' => $endpoint->httpProxy,
                'expiration_at' => $endpoint->expirationAt,
                'next_request_at' => $endpoint->nextRequestAt,
            ])->save();
        }

        $leaseUntil = now()->addMinutes($this->configInt('lease_minutes', 55));
        $taskLease->update(['lease_until' => $leaseUntil]);
        $this->syncLeaseSummary($keyId);

        return $this->lease($key, $owner);
    }

    public function markAccepted(KiotProxyLease $lease, int $tokenId = 0): void
    {
        $key = KiotProxyKey::query()->find($lease->keyId);

        if ($key) {
            $updates = [
                'health_status' => 'healthy',
                'failure_count' => 0,
                'last_failure_code' => null,
                'last_success_at' => now(),
                'lease_until' => now()->addMinutes($this->configInt('lease_minutes', 55)),
            ];

            if ($key->rotate_required) {
                unset($updates['health_status'], $updates['failure_count'], $updates['last_failure_code']);
            }

            $key->update($updates);
        }

        KiotProxyTaskLease::query()
            ->where('kiot_proxy_key_id', $lease->keyId)
            ->where('owner', $lease->owner)
            ->update([
                'accepted_at' => now(),
                'lease_until' => now()->addMinutes($this->configInt('lease_minutes', 55)),
            ]);
        $this->syncLeaseSummary($lease->keyId);

        if ($tokenId > 0 && Schema::hasColumn('ai_video_api_tokens', 'health_status')) {
            AiVideoApiToken::query()->whereKey($tokenId)->update([
                'health_status' => 'healthy',
                'blocked_until' => null,
                'busy_strikes' => 0,
                'last_failure_code' => null,
            ]);
        }
    }

    public function mark6003(
        KiotProxyLease $lease,
        int $tokenId,
        string $source,
        string $taskReference,
    ): Carbon {
        $now = now();
        RoboNeoNetworkIncident::query()->create([
            'source' => mb_substr($source, 0, 32),
            'task_reference' => mb_substr($taskReference, 0, 255),
            'api_token_id' => $tokenId ?: null,
            'kiot_proxy_key_id' => $lease->keyId,
            'proxy_fingerprint' => $lease->fingerprint,
            'code' => '6003',
            'occurred_at' => $now,
        ]);

        $key = KiotProxyKey::query()->find($lease->keyId);
        $retryAt = $now->copy()->addSeconds($this->configInt('failure_cooldown_seconds', 300));

        if ($key) {
            if ($key->next_request_at?->gt($retryAt)) {
                $retryAt = $key->next_request_at->copy();
            }

            $key->forceFill([
                'health_status' => 'draining_6003',
                'batch_sealed' => true,
                'rotate_required' => true,
                'failure_count' => (int) $key->failure_count + 1,
                'last_failure_code' => '6003',
                'last_failed_at' => $now,
                'blocked_until' => $retryAt,
            ])->save();
        }

        $this->quarantineToken($tokenId, '6003');
        // Do not invalidate an IP while other provider tasks are still polling through it.
        // release() calls /proxies/out immediately only when this is the final lease.
        $this->release($lease, true);
        $circuitUntil = $this->circuitOpenUntil();

        return $circuitUntil?->gt($retryAt) ? $circuitUntil : $retryAt;
    }

    public function markTransportFailure(KiotProxyLease $lease, string $code): Carbon
    {
        $retryAt = now()->addSeconds($this->configInt('failure_cooldown_seconds', 300));
        KiotProxyKey::query()
            ->whereKey($lease->keyId)
            ->update([
                'health_status' => 'draining_transport_failed',
                'batch_sealed' => true,
                'rotate_required' => true,
                'blocked_until' => $retryAt,
                'last_failure_code' => mb_substr($code, 0, 64),
                'last_failed_at' => now(),
            ]);
        $this->release($lease, true);

        return $retryAt;
    }

    public function release(KiotProxyLease $lease, bool $releaseRemote = true): void
    {
        $result = $this->detachTaskLease($lease->keyId, $lease->owner, $releaseRemote);
        $key = $result['key'] ?? null;

        if (! $key || ($result['remaining'] ?? 0) > 0) {
            return;
        }

        $released = true;
        if ($releaseRemote) {
            try {
                $this->client->release((string) $key->proxy_key);
            } catch (Throwable $exception) {
                $released = false;
                Log::warning('KiotProxy release failed.', [
                    'kiot_proxy_key_id' => $key->getKey(),
                    'code' => $exception instanceof RoboNeoProtocolException
                        ? $exception->protocolCode
                        : 'kiot_proxy_release_failed',
                ]);
            }
        }

        DB::transaction(function () use ($key, $releaseRemote, $released): void {
            $lockedKey = KiotProxyKey::query()->lockForUpdate()->find($key->getKey());

            if (! $lockedKey || $this->activeLeaseCount((int) $key->getKey()) > 0) {
                return;
            }

            $updates = [
                'leased_by' => null,
                'lease_until' => null,
            ];

            if ($releaseRemote) {
                $updates += [
                    'batch_sealed' => false,
                    'current_http_proxy' => null,
                    'current_socks5_proxy' => null,
                    'expiration_at' => null,
                    'proxy_fingerprint' => null,
                    'location' => null,
                ];
            }

            if (! $released) {
                $updates['rotate_required'] = true;
            }

            if ($lockedKey->health_status === 'releasing') {
                $updates['health_status'] = $released ? 'healthy' : 'release_failed';
                $updates['blocked_until'] = $released
                    ? null
                    : now()->addSeconds($this->configInt('retry_seconds', 30));
            }

            $lockedKey->forceFill($updates)->save();
        }, 3);
    }

    public function earliestAvailableAt(): Carbon
    {
        $circuit = $this->circuitOpenUntil();

        if ($circuit?->isFuture()) {
            return $circuit;
        }

        $keys = KiotProxyKey::query()
            ->where('is_active', true)
            ->where(static function (Builder $query): void {
                $query->whereNull('blocked_until')->orWhere('blocked_until', '<=', now());
            })
            ->get();
        $availableNow = $keys->contains(function (KiotProxyKey $key): bool {
            $active = $this->activeLeaseCount((int) $key->getKey());

            return ! $key->batch_sealed
                && $active < $this->capacity($key)
                && (! $key->rotate_required || $active === 0);
        });

        if ($availableNow) {
            return now()->addSecond();
        }

        $batchIsDraining = KiotProxyKey::query()
            ->where('is_active', true)
            ->where('batch_sealed', true)
            ->exists();

        if ($batchIsDraining) {
            return now()->addSeconds($this->configInt('retry_seconds', 30));
        }

        $timestamps = KiotProxyKey::query()
            ->where('is_active', true)
            ->get(['blocked_until', 'lease_until', 'next_request_at'])
            ->flatMap(static fn (KiotProxyKey $key): array => [
                $key->blocked_until,
                $key->lease_until,
                $key->next_request_at,
            ])
            ->filter(static fn ($date): bool => $date?->isFuture() === true)
            ->sortBy(static fn ($date): int => $date->getTimestamp())
            ->first();

        return $timestamps?->copy() ?: now()->addSeconds($this->configInt('retry_seconds', 30));
    }

    public function rotateNow(KiotProxyKey $key): KiotProxyEndpoint
    {
        if ($this->activeLeaseCount((int) $key->getKey()) > 0) {
            throw new RoboNeoProtocolException('This KiotProxy key is being used by a running task.', 'kiot_proxy_key_in_use');
        }

        if ($key->next_request_at?->isFuture()) {
            throw new RoboNeoProtocolException('KiotProxy has not reached nextRequestAt yet.', 'kiot_proxy_ttc_active');
        }

        try {
            $this->client->release((string) $key->proxy_key);
        } catch (Throwable) {
        }

        $endpoint = $this->client->rotate((string) $key->proxy_key, (string) $key->region);
        $key->forceFill([
            'current_http_proxy' => $endpoint->httpProxy,
            'current_socks5_proxy' => $endpoint->socks5Proxy,
            'proxy_fingerprint' => $endpoint->fingerprint,
            'location' => $endpoint->location,
            'expiration_at' => $endpoint->expirationAt,
            'next_request_at' => $endpoint->nextRequestAt,
            'rotate_required' => false,
            'batch_sealed' => false,
            'health_status' => 'healthy',
            'blocked_until' => null,
            'failure_count' => 0,
            'last_failure_code' => null,
        ])->save();

        return $endpoint;
    }

    public function releaseKey(KiotProxyKey $key): void
    {
        if ($this->activeLeaseCount((int) $key->getKey()) > 0) {
            throw new RoboNeoProtocolException('This KiotProxy key is being used by a running task.', 'kiot_proxy_key_in_use');
        }

        try {
            $this->client->release((string) $key->proxy_key);
        } catch (RoboNeoProtocolException $exception) {
            if (! in_array($exception->protocolCode, ['kiot_proxy_not_assigned', 'kiot_proxy_key_not_found'], true)) {
                throw $exception;
            }
        }
    }

    private function reserveKey(string $owner, Carbon $leaseUntil, ?int $preferredKeyId, array $excluded): ?KiotProxyKey
    {
        return DB::transaction(function () use ($owner, $leaseUntil, $preferredKeyId, $excluded): ?KiotProxyKey {
            KiotProxyTaskLease::query()->where('lease_until', '<=', now())->delete();
            $minimumLeaseUntil = $leaseUntil->copy()->max(now()->addMinutes($this->configInt('lease_minutes', 55)));
            $existingLease = KiotProxyTaskLease::query()->where('owner', $owner)->lockForUpdate()->first();

            if ($existingLease) {
                $key = KiotProxyKey::query()->lockForUpdate()->find($existingLease->kiot_proxy_key_id);

                if ($key) {
                    $existingLease->update(['lease_until' => $minimumLeaseUntil]);
                    $this->syncLeaseSummary((int) $key->getKey());

                    return $key->fresh();
                }

                $existingLease->delete();
            }

            $query = KiotProxyKey::query()
                ->where('is_active', true)
                ->whereNotIn('id', $excluded)
                ->where(static function (Builder $query): void {
                    $query->whereNull('blocked_until')->orWhere('blocked_until', '<=', now());
                });

            if ($preferredKeyId) {
                $query->whereKey($preferredKeyId);
            }

            $keys = $query
                ->orderByRaw('last_used_at IS NOT NULL')
                ->oldest('last_used_at')
                ->lockForUpdate()
                ->get();
            $key = $keys->first(function (KiotProxyKey $candidate): bool {
                $active = $this->activeLeaseCount((int) $candidate->getKey());

                return $active < $this->capacity($candidate)
                    && ! $candidate->batch_sealed
                    && (! $candidate->rotate_required || $active === 0);
            });

            if (! $key) {
                return null;
            }

            KiotProxyTaskLease::query()->create([
                'kiot_proxy_key_id' => $key->getKey(),
                'owner' => $owner,
                'lease_until' => $minimumLeaseUntil,
            ]);
            $active = $this->activeLeaseCount((int) $key->getKey());
            $key->forceFill([
                'leased_by' => 'shared:'.$active,
                'lease_until' => $minimumLeaseUntil,
                'last_used_at' => now(),
                'batch_sealed' => $active >= $this->capacity($key),
            ])->save();

            return $key->fresh();
        }, 3);
    }

    private function resolveEndpoint(KiotProxyKey $key): KiotProxyEndpoint
    {
        $minimumExpiry = now()->addSeconds($this->configInt('minimum_remaining_ttl_seconds', 600));

        if (! $key->rotate_required && $key->current_http_proxy && $key->expiration_at?->gt($minimumExpiry)) {
            return new KiotProxyEndpoint(
                (string) $key->current_http_proxy,
                $key->current_socks5_proxy ? (string) $key->current_socks5_proxy : null,
                (string) $key->proxy_fingerprint,
                $key->location,
                $key->expiration_at,
                $key->next_request_at,
                (int) max(0, now()->diffInSeconds($key->expiration_at, false)),
                (int) max(0, now()->diffInSeconds($key->next_request_at, false)),
            );
        }

        $endpoint = $this->client->current((string) $key->proxy_key);

        if ($endpoint && ! $key->rotate_required && $endpoint->expirationAt?->gt($minimumExpiry)) {
            return $endpoint;
        }

        $nextRequestAt = $endpoint?->nextRequestAt ?: $key->next_request_at;

        if ($nextRequestAt?->isFuture()) {
            throw new RoboNeoProtocolException(
                'KiotProxy is cooling down until nextRequestAt.',
                'kiot_proxy_ttc_active',
                ['next_request_at' => $nextRequestAt->toISOString()],
            );
        }

        if ($endpoint) {
            try {
                $this->client->release((string) $key->proxy_key);
            } catch (Throwable) {
            }
        }

        return $this->client->rotate((string) $key->proxy_key, (string) $key->region);
    }

    private function markKeyApiFailure(KiotProxyKey $key, RoboNeoProtocolException $exception): void
    {
        $invalid = $exception->protocolCode === 'kiot_proxy_key_not_found';
        $nextRequestAt = $this->carbon(data_get($exception->responseData, 'next_request_at'));
        $key->forceFill([
            'is_active' => $invalid ? false : $key->is_active,
            'health_status' => $invalid ? 'invalid_key' : 'api_failed',
            'blocked_until' => $nextRequestAt ?: now()->addSeconds($this->configInt('retry_seconds', 30)),
            'last_failure_code' => $exception->protocolCode,
            'last_failed_at' => now(),
            'failure_count' => (int) $key->failure_count + 1,
        ])->save();
    }

    private function capacity(KiotProxyKey $key): int
    {
        return max(1, min(15, (int) ($key->max_concurrent_tasks ?: 5)));
    }

    private function activeLeaseCount(int $keyId): int
    {
        return KiotProxyTaskLease::query()
            ->where('kiot_proxy_key_id', $keyId)
            ->where('lease_until', '>', now())
            ->count();
    }

    private function syncLeaseSummary(int $keyId): void
    {
        $leases = KiotProxyTaskLease::query()
            ->where('kiot_proxy_key_id', $keyId)
            ->where('lease_until', '>', now());
        $count = (clone $leases)->count();
        KiotProxyKey::query()->whereKey($keyId)->update([
            'leased_by' => $count > 0 ? 'shared:'.$count : null,
            'lease_until' => $count > 0 ? (clone $leases)->max('lease_until') : null,
        ]);
    }

    private function discardTaskLease(int $keyId, string $owner): void
    {
        KiotProxyTaskLease::query()
            ->where('kiot_proxy_key_id', $keyId)
            ->where('owner', $owner)
            ->delete();
        $this->syncLeaseSummary($keyId);
    }

    /** @return array{key: KiotProxyKey|null, remaining: int} */
    private function detachTaskLease(int $keyId, string $owner, bool $releaseRemote): array
    {
        return DB::transaction(function () use ($keyId, $owner, $releaseRemote): array {
            $key = KiotProxyKey::query()->lockForUpdate()->find($keyId);

            if (! $key) {
                return ['key' => null, 'remaining' => 0];
            }

            KiotProxyTaskLease::query()
                ->where('kiot_proxy_key_id', $keyId)
                ->where('owner', $owner)
                ->delete();
            KiotProxyTaskLease::query()
                ->where('kiot_proxy_key_id', $keyId)
                ->where('lease_until', '<=', now())
                ->delete();
            $remaining = $this->activeLeaseCount($keyId);
            $latestLease = $remaining > 0
                ? KiotProxyTaskLease::query()->where('kiot_proxy_key_id', $keyId)->max('lease_until')
                : null;
            $updates = [
                'leased_by' => $remaining > 0 ? 'shared:'.$remaining : null,
                'lease_until' => $latestLease,
            ];

            if ($remaining === 0 && $releaseRemote && $key->health_status === 'healthy') {
                $updates['health_status'] = 'releasing';
                $updates['blocked_until'] = now()->addSeconds(90);
            }

            $key->forceFill($updates)->save();

            return ['key' => $key->fresh(), 'remaining' => $remaining];
        }, 3);
    }

    private function quarantineToken(int $tokenId, string $code): void
    {
        if ($tokenId <= 0 || ! Schema::hasColumn('ai_video_api_tokens', 'busy_strikes')) {
            return;
        }

        $token = AiVideoApiToken::query()->find($tokenId);

        if (! $token) {
            return;
        }

        $strikes = (int) $token->busy_strikes + 1;
        $seconds = $strikes >= 2
            ? $this->configInt('token_repeated_6003_cooldown_seconds', 86400)
            : $this->configInt('token_first_6003_cooldown_seconds', 21600);
        $token->forceFill([
            'health_status' => 'blocked_6003',
            'blocked_until' => now()->addSeconds($seconds),
            'busy_strikes' => $strikes,
            'last_failure_code' => $code,
            'last_failed_at' => now(),
        ])->save();
    }

    private function circuitOpenUntil(): ?Carbon
    {
        if (! Schema::hasTable('ai_video_roboneo_network_incidents')) {
            return null;
        }

        $lookback = now()->subMinutes($this->configInt('circuit_lookback_minutes', 10));
        $query = RoboNeoNetworkIncident::query()
            ->where('code', '6003')
            ->where('occurred_at', '>=', $lookback);
        $distinct = (clone $query)->whereNotNull('proxy_fingerprint')->distinct()->count('proxy_fingerprint');
        $activeKeys = max(1, KiotProxyKey::query()->where('is_active', true)->count());
        $threshold = min(
            $activeKeys,
            max(
                $this->configInt('circuit_minimum_distinct_proxies', 2),
                (int) ceil($activeKeys * max(0.1, (float) config('plugins.ai-video-generator.general.roboneo.kiot_proxy.circuit_proxy_ratio', 0.5))),
            ),
        );

        if ($distinct < $threshold) {
            return null;
        }

        $last = (clone $query)->max('occurred_at');

        return $last ? Carbon::parse($last)->addSeconds($this->configInt('circuit_pause_seconds', 900)) : null;
    }

    private function lease(KiotProxyKey $key, string $owner): KiotProxyLease
    {
        return new KiotProxyLease(
            (int) $key->getKey(),
            $owner,
            'kiot-key-'.$key->getKey(),
            (string) $key->current_http_proxy,
            (string) $key->proxy_fingerprint,
        );
    }

    private function carbon(mixed $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::parse($value) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function configInt(string $key, int $default): int
    {
        return max(1, (int) config('plugins.ai-video-generator.general.roboneo.kiot_proxy.'.$key, $default));
    }
}
