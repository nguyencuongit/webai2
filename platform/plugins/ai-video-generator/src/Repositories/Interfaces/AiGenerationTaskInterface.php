<?php

namespace Botble\AiVideoGenerator\Repositories\Interfaces;

use Botble\Support\Repositories\Interfaces\RepositoryInterface;

interface AiGenerationTaskInterface extends RepositoryInterface
{
    public function storeFromResponse(array $response, array|string $payload, ?int $customerId = null);

    public function updateFromWebhook(array $webhookData);
}
