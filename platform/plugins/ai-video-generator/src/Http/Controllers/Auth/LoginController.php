<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Auth;

use Botble\AiVideoGenerator\Http\Requests\Auth\LoginRequest;
use Botble\AiVideoGenerator\Models\Customer;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends BaseController
{
    public function showLoginForm()
    {
        if (auth('customer')->check()) {
            return redirect()->to(BaseHelper::getHomepageUrl());
        }

        $title = 'Đăng nhập';

        SeoHelper::setTitle($title);
        Theme::breadcrumb()->add($title, route('ai-video-generator.login'));

        if (request()->has('redirect') && request()->get('redirect')) {
            session(['url.intended' => request()->get('redirect')]);
        }

        return Theme::scope(
            'ai-video-generator.auth.login',
            [],
            'plugins/ai-video-generator::auth.login'
        )->render();
    }

    public function login(LoginRequest $request)
    {
        $login = $request->input('email');

        $customer = Customer::query()
            ->where($request->isEmail($login) ? 'email' : 'phone', $login)
            ->first();

        if (! $customer || ! Hash::check($request->input('password'), $customer->password)) {
            throw ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập không đúng.',
            ]);
        }

        if ($customer->status && $customer->status !== 'activated') {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản của bạn đã bị khóa, vui lòng liên hệ quản trị viên.',
            ]);
        }

        auth('customer')->login($customer, $request->boolean('remember'));

        $request->session()->regenerate();

        Cookie::queue('customer_remember_email', $login, 525600);

        return $this
            ->httpResponse()
            ->setNextUrl(session()->pull('url.intended', BaseHelper::getHomepageUrl()))
            ->setMessage('Đăng nhập thành công!');
    }

    public function logout(Request $request)
    {
        auth('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(BaseHelper::getHomepageUrl());
    }
}
