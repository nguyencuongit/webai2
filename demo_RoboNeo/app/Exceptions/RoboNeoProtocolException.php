<?php

namespace App\Exceptions;

use RuntimeException;

class RoboNeoProtocolException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $protocolCode = null,
        public readonly array $responseData = [],
    ) {
        parent::__construct($message);
    }
}
