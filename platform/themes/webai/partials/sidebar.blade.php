@php
    $sidebarMenu = Menu::renderMenuLocation('main-menu', ['view' => 'sidebar-menu']);
    $logo = Theme::getLogoImage(['class' => 'webai-brand-logo'], maxHeight: 100);
@endphp

<aside class="webai-sidebar" id="webai-sidebar">
    <a class="webai-brand" href="{{ route('public.home') }}" aria-label="WebAI">
        @if ($logo)
            {{ $logo }}
        @else
            <span class="webai-brand-mark">S</span>
            <span class="webai-brand-text">Solan<span>AI</span></span>
        @endif
    </a>

    <div class="webai-sidebar-navigation">
        <nav class="webai-nav" aria-label="Main navigation">
            {!! $sidebarMenu !!}
        </nav>
    </div>

    {{-- <aside class="webai-sidebar-promo" aria-label="Giới thiệu nhận điểm">
        <div class="webai-sidebar-promo__icon" aria-hidden="true">♔</div>
        <div class="webai-sidebar-promo__title">Giới thiệu nhận điểm</div>
        <p>Nhận <strong>10.000</strong> điểm cho mỗi người bạn giới thiệu</p>
        <a href="{{ route('public.credit-packages.index') }}">Giới thiệu ngay <span aria-hidden="true">→</span></a>
    </aside> --}}
</aside>
