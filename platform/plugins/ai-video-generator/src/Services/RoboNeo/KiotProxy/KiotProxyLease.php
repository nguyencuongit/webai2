<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy;

final readonly class KiotProxyLease
{
    public function __construct(
        public int $keyId,
        public string $owner,
        public string $proxyId,
        public string $proxyUrl,
        public string $fingerprint,
    ) {}
}
