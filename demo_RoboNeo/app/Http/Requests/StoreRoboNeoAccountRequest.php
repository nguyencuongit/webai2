<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoboNeoAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'access_token' => ['required', 'string', 'min:20', 'max:4096'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
