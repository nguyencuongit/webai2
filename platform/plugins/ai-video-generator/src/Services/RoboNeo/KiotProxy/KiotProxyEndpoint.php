<?php

namespace Botble\AiVideoGenerator\Services\RoboNeo\KiotProxy;

use Illuminate\Support\Carbon;

final readonly class KiotProxyEndpoint
{
    public function __construct(
        public string $httpProxy,
        public ?string $socks5Proxy,
        public string $fingerprint,
        public ?string $location,
        public ?Carbon $expirationAt,
        public ?Carbon $nextRequestAt,
        public int $ttl,
        public int $ttc,
    ) {}
}
