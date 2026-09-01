<?php

namespace Botble\AiVideoGenerator\Services;

use Botble\AiVideoGenerator\Repositories\Interfaces\AiVideoModelEndpointInterface;
use Illuminate\Support\Collection;

class VideoLabDataService
{
    public function __construct(protected AiVideoModelEndpointInterface $endpointRepository)
    {
    }

    /**
     * Get active endpoint models for display on the Video Lab page.
     */
    public function models(): Collection
    {
        return $this->endpointRepository->getActiveForVideoLab();
    }
}
