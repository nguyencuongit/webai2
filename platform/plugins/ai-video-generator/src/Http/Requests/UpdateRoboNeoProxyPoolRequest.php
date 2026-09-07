<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Services\RoboNeo\RoboNeoProxyPoolSettings;
use Botble\Support\Http\Requests\Request;
use Closure;
use InvalidArgumentException;

class UpdateRoboNeoProxyPoolRequest extends Request
{
    public function rules(): array
    {
        return [
            'proxy_pool' => [
                'nullable',
                'string',
                'max:65535',
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        app(RoboNeoProxyPoolSettings::class)->parse((string) $value);
                    } catch (InvalidArgumentException $exception) {
                        $fail($exception->getMessage());
                    }
                },
            ],
        ];
    }

    /** @return list<string> */
    public function proxyUrls(): array
    {
        return app(RoboNeoProxyPoolSettings::class)->parse((string) $this->input('proxy_pool', ''));
    }
}
