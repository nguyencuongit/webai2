@php
    $customer = auth('customer')->check() ? auth('customer')->user() : null;
    $adminUser = auth()->check() ? auth()->user() : null;
    $user = $customer ?: $adminUser;
    $isLoggedIn = (bool) $user;
    $avatarUrl = $user->avatar_url ?? null;
    $displayName = $user->name ?? $user->email ?? 'Account';
    $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
    $accountUrl = $customer && \Illuminate\Support\Facades\Route::has('customer.overview')
        ? route('customer.overview')
        : '#';
    $loginUrl = \Illuminate\Support\Facades\Route::has('ai-video-generator.login')
        ? route('ai-video-generator.login')
        : (\Illuminate\Support\Facades\Route::has('customer.login')
            ? route('customer.login')
            : (\Illuminate\Support\Facades\Route::has('access.login') ? route('access.login') : url('login')));
    $logo = Theme::getLogoImage(['class' => 'webai-mobile-logo-image'], maxHeight: 36);
@endphp

<header class="webai-mobile-header">
    <button
        class="webai-mobile-menu-toggle"
        type="button"
        aria-label="Menu"
        aria-controls="webai-sidebar"
        aria-expanded="false"
        data-webai-menu-toggle
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <a class="webai-mobile-logo" href="{{ route('public.home') }}" aria-label="WebAI">
        @if ($logo)
            {{ $logo }}
        @else
            <span class="webai-mobile-logo-mark">S</span>
            <span class="webai-mobile-logo-text">SolanAI</span>
        @endif
    </a>

    <a class="webai-mobile-account" href="{{ $isLoggedIn ? $accountUrl : $loginUrl }}" aria-label="{{ $isLoggedIn ? 'Account' : 'Login' }}">
        @if ($isLoggedIn && $avatarUrl)
            <img src="{{ $avatarUrl }}" alt="{{ $displayName }}">
        @elseif ($isLoggedIn)
            <span>{{ $initial }}</span>
        @else
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                <path d="M10 17l5-5-5-5" />
                <path d="M15 12H3" />
            </svg>
        @endif
    </a>
</header>
