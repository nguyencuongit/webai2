<?php

namespace Botble\AiVideoGenerator\Api\RoboNeo;

/**
 * Per-account RoboNeo session state. Persist the array returned by toArray()
 * outside this API layer if cookies/session continuity is required.
 */
class RoboNeoContext
{
    public function __construct(
        public string $gid,
        public string $mtG,
        public string $sid,
        public string $uid = '',
        public array $cookies = [],
    ) {}

    public static function make(?string $gid = null, ?string $uid = null, array $cookies = []): self
    {
        return new self(
            gid: $gid ?: RoboNeoIdentity::gid(),
            mtG: RoboNeoIdentity::hexId(),
            sid: RoboNeoIdentity::hexId(),
            uid: $uid ?: '',
            cookies: $cookies,
        );
    }

    public static function fromArray(array $session): self
    {
        return new self(
            gid: (string) ($session['gid'] ?? RoboNeoIdentity::gid()),
            mtG: (string) ($session['mt_g'] ?? RoboNeoIdentity::hexId()),
            sid: (string) ($session['sid'] ?? RoboNeoIdentity::hexId()),
            uid: (string) ($session['uid'] ?? ''),
            cookies: is_array($session['cookies'] ?? null) ? $session['cookies'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'gid' => $this->gid,
            'mt_g' => $this->mtG,
            'sid' => $this->sid,
            'uid' => $this->uid,
            'cookies' => $this->cookies,
        ];
    }
}
