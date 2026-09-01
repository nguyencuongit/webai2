<?php

namespace Botble\AiVideoGenerator\Api;

use Botble\AiVideoGenerator\Api\Contracts\AiGenerationApiInterface;
use InvalidArgumentException;

class MagnificApiFactory
{
    public function __construct(protected MagnificApiCatalog $catalog)
    {
    }

    public function make(string $name): AiGenerationApiInterface
    {
        $model = $this->catalog->get($name);

        if (! $model) {
            throw new InvalidArgumentException("Magnific API [{$name}] is not supported.");
        }

        if (! empty($model['endpoint'])) {
            return app()->makeWith(MagnificGenerationApi::class, [
                'model' => $model,
            ]);
        }

        if (empty($model['api'])) {
            throw new InvalidArgumentException("Magnific API [{$name}] has not been implemented yet.");
        }

        return app($model['api']);
    }

    public function models(): array
    {
        return $this->catalog->options();
    }
}
