<?php

namespace Botble\AiVideoGenerator\Http\Requests;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends Request
{
    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = $customer instanceof Customer ? $customer->getKey() : $customer;

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique((new Customer())->getTable(), 'email')->ignore($customerId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'credits_balance' => ['required', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'integer', Rule::exists((new Customer())->getTable(), 'id'), Rule::notIn([$customerId])],
            'status' => ['required', Rule::in(['activated', 'locked'])],
            'private_notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->boolean('is_change_password')) {
            $rules['password'] = ['required', 'string', 'min:6', 'confirmed'];
        }

        return $rules;
    }
}
