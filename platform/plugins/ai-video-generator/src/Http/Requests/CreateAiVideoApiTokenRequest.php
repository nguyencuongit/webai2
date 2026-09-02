<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\AiVideoApiToken;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CreateAiVideoApiTokenRequest extends Request
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'token_api' => ['required', 'string', 'max:255', Rule::unique((new AiVideoApiToken)->getTable(), 'token_api')],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }
}
