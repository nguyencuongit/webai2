<?php

namespace Botble\AiVideoGenerator\Http\Requests\Auth;

use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Factory;

class LoginRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'email hoặc số điện thoại',
            'password' => 'mật khẩu',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email hoặc số điện thoại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ];
    }

    public function isEmail($value): bool
    {
        return $this->container
            ->make(Factory::class)
            ->make(['email' => $value], ['email' => ['email']])
            ->passes();
    }
}
