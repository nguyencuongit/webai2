<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CreateCustomerRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique((new Customer())->getTable(), 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'credits_balance' => ['required', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'integer', Rule::exists((new Customer())->getTable(), 'id')],
            'status' => ['required', Rule::in(['activated', 'locked'])],
            'private_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
