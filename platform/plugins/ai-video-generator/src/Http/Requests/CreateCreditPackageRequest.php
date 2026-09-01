<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\AiVideoCreditPackage;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CreateCreditPackageRequest extends Request
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:120', Rule::unique((new AiVideoCreditPackage())->getTable(), 'code')],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'integer', 'min:1'],
            'credits' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_popular' => ['nullable', 'boolean'],
        ];
    }
}
