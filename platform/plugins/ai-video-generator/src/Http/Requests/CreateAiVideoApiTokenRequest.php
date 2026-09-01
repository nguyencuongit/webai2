<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CreateAiVideoApiTokenRequest extends Request
{
    public function rules(): array
    {
        return [
            'token_api' => ['required', 'string', 'max:255', Rule::unique((new AiVideoApiToken())->getTable(), 'token_api')],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }
}
