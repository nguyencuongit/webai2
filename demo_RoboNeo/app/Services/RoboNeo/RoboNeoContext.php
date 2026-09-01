<?php

namespace App\Services\RoboNeo;

use App\Models\MotionJob;

class RoboNeoContext
{
    public function __construct(
        public string $gid,
        public string $mtG,
        public string $sid,
        public string $uid = '',
        public array $cookies = [],
    ) {}

    public static function fromJob(MotionJob $job): self
    {
        $session = $job->session_data ?? [];

        return new self(
            gid: $session['gid'] ?? config('roboneo.credentials.gid') ?: RoboNeoIdentity::gid(),
            mtG: $session['mt_g'] ?? RoboNeoIdentity::hexId(),
            sid: $session['sid'] ?? RoboNeoIdentity::hexId(),
            uid: $session['uid'] ?? $job->roboneoAccount?->uid ?? '',
            cookies: $session['cookies'] ?? [],
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
