<?php

namespace Botble\AiVideoGenerator\Exceptions;

use Illuminate\Http\Client\RequestException;
use RuntimeException;

class MagnificInsufficientCreditsException extends RuntimeException
{
    public function __construct(
        public readonly int $apiTokenId,
        public readonly RequestException $requestException
    ) {
        parent::__construct('Magnific API token has insufficient credits.', 0, $requestException);
    }
}
