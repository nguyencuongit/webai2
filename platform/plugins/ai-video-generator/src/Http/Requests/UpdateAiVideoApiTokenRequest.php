<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class UpdateAiVideoApiTokenRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
        ]);
    }

    public function rules(): array
    {
        $apiToken = $this->route('apiToken');
        $apiTokenId = $apiToken instanceof AiVideoApiToken ? $apiToken->getKey() : $apiToken;

        return [
            'name' => ['required', 'string', 'max:255'],
            'token_api' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new AiVideoApiToken)->getTable(), 'token_api')->ignore($apiTokenId),
            ],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }
}
