<?php

namespace Botble\AiVideoGenerator\Repositories\Eloquent;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoApiTokenInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;

class AiVideoApiTokenRepository extends RepositoriesAbstract implements AiVideoApiTokenInterface
{
    public function getLatestActiveToken(): ?array
    {
        $token = AiVideoApiToken::query()
            ->where('status', true)
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
