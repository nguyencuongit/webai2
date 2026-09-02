<?php

namespace Botble\AiVideoGenerator\Api\RoboNeo;

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
