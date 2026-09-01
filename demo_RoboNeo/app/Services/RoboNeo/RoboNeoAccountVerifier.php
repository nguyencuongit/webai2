<?php

namespace App\Services\RoboNeo;

class RoboNeoAccountVerifier
{
    public function verify(string $accessToken): string
    {
        $context = new RoboNeoContext(
            gid: config('roboneo.credentials.gid') ?: RoboNeoIdentity::gid(),
            mtG: RoboNeoIdentity::hexId(),
            sid: RoboNeoIdentity::hexId(),
        );
        $client = new RoboNeoApiClient($context, $accessToken);
        $client->initialize();

        return $client->contextSnapshot()['uid'];
    }
}
