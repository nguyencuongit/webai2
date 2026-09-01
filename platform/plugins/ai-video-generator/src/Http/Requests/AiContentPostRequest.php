<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class AiContentPostRequest extends Request
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:500'],
            'display_location' => ['required', Rule::in(['home', 'intro_modal'])],
            'status' => ['required', 'boolean'],
        ];
    }
}
