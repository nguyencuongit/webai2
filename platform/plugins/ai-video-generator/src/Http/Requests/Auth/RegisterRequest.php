<?php

namespace Botble\AiVideoGenerator\Http\Requests\Auth;

use Botble\AiVideoGenerator\Models\Customer;
use Botble\Base\Rules\EmailRule;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class RegisterRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'max:120', 'min:2'],
            'email' => ['required', new EmailRule(), Rule::unique((new Customer())->getTable())],
            'phone' => ['nullable', 'max:20', Rule::unique((new Customer())->getTable())],
            'password' => ['required', 'min:6', 'confirmed'],
            'agree_terms_and_policy' => ['sometimes', 'accepted:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'họ và tên',
            'email' => 'email',
            'phone' => 'số điện thoại',
            'password' => 'mật khẩu',
            'agree_terms_and_policy' => 'điều khoản và chính sách',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.min' => 'Họ và tên phải có ít nhất :min ký tự.',
            'name.max' => 'Họ và tên không được vượt quá :max ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
            'agree_terms_and_policy.accepted' => 'Bạn cần đồng ý với điều khoản và chính sách.',
        ];
    }
}
