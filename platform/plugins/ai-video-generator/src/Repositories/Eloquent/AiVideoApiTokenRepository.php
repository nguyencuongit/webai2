<?php

namespace Botble\AiVideoGenerator\Repositories\Eloquent;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Support\Facades\Schema;

class AiVideoApiTokenRepository extends RepositoriesAbstract implements AiVideoApiTokenInterface
{
    public function getActiveTokens(): array
    {
        $query = AiVideoApiToken::query()->where('status', true);

        if (Schema::hasColumn('ai_video_api_tokens', 'blocked_until')) {
            $query->where(static function ($query): void {
                $query->whereNull('blocked_until')->orWhere('blocked_until', '<=', now());
            });
        }

        return $query
            ->oldest('id')
            ->get(['id', 'token_api'])
            ->map(static fn (AiVideoApiToken $token): array => [
                'id' => (int) $token->getKey(),
                'token_api' => (string) $token->token_api,
            ])
            ->all();
    }

    public function getLatestActiveToken(): ?array
    {
        $query = AiVideoApiToken::query()->where('status', true);

        if (Schema::hasColumn('ai_video_api_tokens', 'blocked_until')) {
            $query->where(static function ($query): void {
                $query->whereNull('blocked_until')->orWhere('blocked_until', '<=', now());
            });
        }

        $token = $query
            ->latest('id')
            ->first(['id', 'token_api']);

        if (! $token) {
            return null;
        }

        return [
            'id' => $token->getKey(),
            'token_api' => $token->token_api,
        ];
    }

    public function deactivate(int $id): bool
    {
        return AiVideoApiToken::query()
            ->whereKey($id)
            ->where('status', true)
            ->update(['status' => false]) > 0;
    }
}
