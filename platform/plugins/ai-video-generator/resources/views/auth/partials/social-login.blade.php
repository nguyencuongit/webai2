@php
    $hasGoogleLogin = defined('SOCIAL_LOGIN_MODULE_SCREEN_NAME')
        && class_exists(\Botble\SocialLogin\Facades\SocialService::class)
        && Route::has('auth.social')
        && \Botble\SocialLogin\Facades\SocialService::setting('enable')
        && \Botble\SocialLogin\Facades\SocialService::getProviderEnabled('google');
@endphp

@if ($hasGoogleLogin)
    <div class="webai-social-login">
        <a
            class="webai-google-login"
            href="{{ route('auth.social', ['provider' => 'google', 'guard' => 'customer']) }}"
        >
            <span class="webai-google-login__icon">G</span>
            <span>Tiếp tục với Google</span>
        </a>

        <div class="webai-auth-divider">
            <span>Hoặc đăng nhập bằng email</span>
        </div>
    </div>
@endif
