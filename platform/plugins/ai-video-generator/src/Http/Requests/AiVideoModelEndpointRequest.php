<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Enums\AiVideoModelEndpointTag;
use Botble\AiVideoGenerator\Models\AiVideoModelEndpoint;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class AiVideoModelEndpointRequest extends Request
{
    public function rules(): array
    {
        $endpoint = $this->route('aiVideoModelEndpoint');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:120', Rule::unique((new AiVideoModelEndpoint())->getTable(), 'code')->ignore($endpoint)],
            'image' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tag' => ['nullable', Rule::enum(AiVideoModelEndpointTag::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'boolean'],
        ];
    }
}
