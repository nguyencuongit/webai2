<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\AiVideoCreditPackage;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class UpdateCreditPackageRequest extends Request
{
    public function rules(): array
    {
        $package = $this->route('creditPackage');
        $packageId = $package instanceof AiVideoCreditPackage ? $package->getKey() : $package;

        return [
            'code' => [
                'required',
                'string',
                'max:120',
                Rule::unique((new AiVideoCreditPackage())->getTable(), 'code')->ignore($packageId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'integer', 'min:1'],
            'credits' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_popular' => ['nullable', 'boolean'],
        ];
    }
}
