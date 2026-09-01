<?php

namespace Botble\AiVideoGenerator\Repositories\Interfaces;

use Botble\Support\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Support\Collection;

interface AiVideoModelEndpointInterface extends RepositoryInterface
{
    /**
     * @return Collection<int, \Botble\AiVideoGenerator\Models\AiVideoModelEndpoint>
     */
    public function getActiveForVideoLab(): Collection;
}
