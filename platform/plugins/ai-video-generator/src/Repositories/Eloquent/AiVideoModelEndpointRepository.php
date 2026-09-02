<?php

namespace Botble\AiVideoGenerator\Repositories\Eloquent;

use Botble\AiVideoGenerator\Models\AiVideoModelEndpoint;
use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoModelEndpointInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Support\Collection;

class AiVideoModelEndpointRepository extends RepositoriesAbstract implements AiVideoModelEndpointInterface
{
    public function getActiveForVideoLab(): Collection
    {
        return AiVideoModelEndpoint::query()
            ->where('status', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'image',
                'description',
                'tag',
                'price',
            ]);
    }

    public function getActiveByCode(string $code): ?AiVideoModelEndpoint
    {
        return AiVideoModelEndpoint::query()
            ->where('status', true)
            ->where('code', trim($code))
            ->first();
    }
}
