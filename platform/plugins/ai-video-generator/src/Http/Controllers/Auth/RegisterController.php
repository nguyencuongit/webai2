<?php

namespace Botble\AiVideoGenerator\Http\Controllers\Auth;

use Botble\AiVideoGenerator\Http\Requests\Auth\RegisterRequest;
use Botble\AiVideoGenerator\Models\Customer;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class RegisterController extends BaseController
{
    public function showRegistrationForm()
    {
        if (auth('customer')->check()) {
            return redirect()->to(BaseHelper::getHomepageUrl());
        }

        $title = 'Đăng ký';

        SeoHelper::setTitle($title);
        Theme::breadcrumb()->add($title, route('ai-video-generator.register'));

        return Theme::scope(
            'ai-video-generator.auth.register',
            [],
            'plugins/ai-video-generator::auth.register'
        )->render();
    }

    public function register(RegisterRequest $request)
    {
        $customer = Customer::query()->create([
            'name' => BaseHelper::clean($request->input('name')),
            'email' => BaseHelper::clean($request->input('email')),
            'phone' => BaseHelper::clean($request->input('phone')),
            'password' => Hash::make($request->input('password')),
            'confirmed_at' => Carbon::now(),
            'status' => 'activated',
        ]);

        event(new Registered($customer));

        auth('customer')->login($customer);

        return $this
            ->httpResponse()
            ->setNextUrl(BaseHelper::getHomepageUrl())
            ->setMessage('Đăng ký thành công!');
    }
}
