<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class UpdateAiVideoApiTokenRequest extends Request
{
    public function rules(): array
    {
        $apiToken = $this->route('apiToken');
        $apiTokenId = $apiToken instanceof AiVideoApiToken ? $apiToken->getKey() : $apiToken;

        return [
            'token_api' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new AiVideoApiToken())->getTable(), 'token_api')->ignore($apiTokenId),
            ],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }
}
