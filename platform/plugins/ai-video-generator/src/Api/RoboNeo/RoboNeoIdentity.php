<?php

namespace Botble\AiVideoGenerator\Api\RoboNeo;

use Illuminate\Support\Str;

class RoboNeoIdentity
{
    public static function gid(): string
    {
        return implode('-', [
            substr(bin2hex(random_bytes(7)), 0, 15),
            substr(bin2hex(random_bytes(7)), 0, 14),
            str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT),
            str_pad((string) random_int(0, 9_999_999), 7, '0', STR_PAD_LEFT),
            substr(bin2hex(random_bytes(8)), 0, 15),
        ]);
    }

    public static function hexId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function traceId(): string
    {
        return (string) Str::uuid();
    }

    public static function nodeId(): string
    {
        return Str::random(21);
    }

    public static function seed(): string
    {
        return ((int) floor(microtime(true) * 1000)).'-'.str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
    }
}
