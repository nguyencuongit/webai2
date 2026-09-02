@php
    $customer = auth('customer')->check() ? auth('customer')->user() : null;
    $adminUser = auth()->check() ? auth()->user() : null;
    $user = $customer ?: $adminUser;
    $creditBalance = (int) ($customer?->credits_balance ?? 0);
    $displayName = $user?->name ?? $user?->email ?? 'Khách';
    $avatarUrl = $user?->avatar_url ?? null;
    $displayName = $customer?->name ?? $customer?->email ?? '';
    $avatarUrl = $customer?->avatar_url;
    $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
    $loginUrl = route('ai-video-generator.login', ['redirect' => url()->current()]);
    $accountUrl = \Illuminate\Support\Facades\Route::has('customer.overview') ? route('customer.overview') : '#';
@endphp

<header class="webai-page-header">
    <div class="webai-page-header__intro">
        <h1>Tạo video chuyển động AI</h1>
        <p>Chọn model phù hợp, xem demo và bắt đầu tạo video ngay</p>
    </div>

    <div class="webai-page-header__actions">
        <div class="webai-page-header__balance">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="6" />
                <path d="M8 12h8M12 8v8" />
            </svg>
            <span>Số dư: <strong>{{ number_format($creditBalance, 0, ',', '.') }} điểm</strong></span>
        </div>

        <button class="webai-page-header__icon-button" type="button" aria-label="Thông báo">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                <path d="M10 21h4" />
            </svg>
        </button>

        <button class="webai-page-header__icon-button" type="button" aria-label="Trợ giúp">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path d="M9.5 9a2.6 2.6 0 1 1 4.4 1.9c-1.2 1.1-1.9 1.5-1.9 3.1" />
                <path d="M12 17h.01" />
            </svg>
        </button>

        @if ($customer)
            <a class="webai-page-header__profile" href="{{ $accountUrl }}" title="{{ $customer->email }}">
                <span class="webai-page-header__avatar">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $displayName }}">
                    @else
                        {{ $initial }}
                    @endif
                </span>
                <span>{{ $displayName }}</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
            </a>
        @else
            <a class="webai-page-header__profile" href="{{ $loginUrl }}">Đăng nhập</a>
        @endif
    </div>
</header>
