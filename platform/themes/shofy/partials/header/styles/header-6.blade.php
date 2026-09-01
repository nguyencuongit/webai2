@php
    $currencies = collect();
    $hasCurrencies = false;
    $supportedLocales = [];

    if (is_plugin_active('ecommerce')) {
        $currencies = get_all_currencies();
        $hasCurrencies = $currencies->count() > 1;
    }

    if (is_plugin_active('language')) {
        $supportedLocales = Language::getSupportedLocales();
    }
@endphp

<style>
    .lyly-header-6 {
        position: relative;
        z-index: 11;
        background: #fff;
        color: #151515;
        border-bottom: 1px solid rgba(1, 15, 28, 0.08);
    }

    .lyly-header-6 a,
    .lyly-header-6 button {
        color: inherit;
    }

    .lyly-header-6__top {
        min-height: 64px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: center;
        gap: 24px;
        padding: 0 45px;
        background: {{ theme_option('header_top_background_color', $headerTopBackgroundColor) }};
        color: {{ $headerTopTextColor }};
    }

    .lyly-header-6__left,
    .lyly-header-6__right {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .lyly-header-6__left {
        justify-content: flex-start;
    }

    .lyly-header-6__right {
        justify-content: flex-end;
    }

    .lyly-header-6__logo {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 120px;
    }

    .lyly-header-6__logo .logo {
        display: inline-flex;
        align-items: center;
        line-height: 1;
    }

    .lyly-header-6__logo img {
        width: auto !important;
        max-width: min(220px, 34vw);
        height: 44px;
        max-height: 44px;
        object-fit: contain;
    }

    .lyly-header-6__pill,
    .lyly-header-6__icon,
    .lyly-header-6__search-submit,
    .lyly-header-6__menu-toggle {
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(17, 17, 17, 0.1);
        background: linear-gradient(180deg, #fff 0%, #fbfbfb 100%);
        box-shadow: 0 8px 22px rgba(1, 15, 28, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        color: #111;
        border-radius: 999px;
        transition: color 0.22s ease, border-color 0.22s ease, background 0.22s ease, box-shadow 0.22s ease, transform 0.22s ease;
    }

    .lyly-header-6__pill svg,
    .lyly-header-6__icon svg,
    .lyly-header-6__search-submit svg,
    .lyly-header-6__menu-toggle svg {
        width: 19px;
        height: 19px;
        stroke-width: 1.8;
    }

    .lyly-header-6__pill {
        gap: 8px;
        padding: 0 16px;
        font-weight: 700;
        font-size: 13px;
        line-height: 1;
        white-space: nowrap;
    }

    .lyly-header-6__lang-toggle {
        width: 42px;
        padding: 0;
        overflow: visible;
        border-color: transparent;
        background: transparent;
    }

    .lyly-header-6__lang-toggle .flag,
    .lyly-header-6__lang-toggle img {
        display: block;
        width: 32px !important;
        height: 32px !important;
        min-width: 32px;
        flex-shrink: 0;
        object-fit: cover;
        overflow: hidden;
        border-radius: 50%;
        border: 2px solid #fff;
        box-sizing: border-box;
    }

    .lyly-header-6__icon,
    .lyly-header-6__search-submit,
    .lyly-header-6__menu-toggle {
        position: relative;
        width: 42px;
        padding: 0;
    }

    .lyly-header-6__pill:hover,
    .lyly-header-6__icon:hover,
    .lyly-header-6__search-submit:hover,
    .lyly-header-6__menu-toggle:hover,
    .lyly-header-6__dropdown.is-open > .lyly-header-6__pill,
    .lyly-header-6__dropdown.is-open > .lyly-header-6__icon {
        color: #fff;
        border-color: #9f0000;
        background: linear-gradient(180deg, #c40000 0%, #9f0000 100%);
        box-shadow: 0 10px 24px rgba(181, 0, 0, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.22);
        transform: translateY(-1px);
    }

    .lyly-header-6__pill:active,
    .lyly-header-6__icon:active,
    .lyly-header-6__search-submit:active,
    .lyly-header-6__menu-toggle:active {
        transform: translateY(0);
        box-shadow: 0 5px 14px rgba(1, 15, 28, 0.08);
    }

    .lyly-header-6__search-form {
        --lyly-header-search-size: 42px;
        position: relative;
        width: var(--lyly-header-search-size);
        height: var(--lyly-header-search-size);
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        overflow: visible;
        transition: width 0.5s ease;
    }

    .lyly-header-6__search-form.is-open {
        width: min(340px, 42vw);
    }

    .lyly-header-6__search-form input.lyly-header-6__search-input {
        width: 100%;
        height: var(--lyly-header-search-size);
        min-height: var(--lyly-header-search-size);
        max-height: var(--lyly-header-search-size);
        padding: 0 52px 0 16px;
        border: 1px solid rgba(17, 17, 17, 0.1);
        border-radius: 999px;
        background: linear-gradient(180deg, #fff 0%, #fbfbfb 100%);
        box-shadow: 0 8px 22px rgba(1, 15, 28, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        box-sizing: border-box;
        color: #111;
        font-size: 14px;
        font-weight: 500;
        line-height: var(--lyly-header-search-size);
        opacity: 0;
        pointer-events: none;
        transform: scaleX(0.2);
        transform-origin: left center;
        transition: opacity 0.25s ease, transform 0.5s ease, border-color 0.2s ease;
        appearance: none;
    }

    .lyly-header-6__search-form input.lyly-header-6__search-input:focus {
        border-color: rgba(181, 0, 0, 0.32);
        box-shadow: 0 10px 26px rgba(1, 15, 28, 0.08), 0 0 0 3px rgba(181, 0, 0, 0.08);
        outline: none;
    }

    .lyly-header-6__search-form.is-open input.lyly-header-6__search-input {
        opacity: 1;
        pointer-events: auto;
        transform: scaleX(1);
    }

    .lyly-header-6__search-submit {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 2;
        transition: left 0.5s ease, color 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
    }

    .lyly-header-6__search-form.is-open .lyly-header-6__search-submit {
        left: calc(100% - var(--lyly-header-search-size));
    }

    .lyly-header-6__search-form .bb-quick-search-results {
        top: calc(100% + 10px);
        left: 0;
        right: 0;
        z-index: 100;
        min-width: 260px;
    }

    .lyly-header-6__dropdown {
        position: relative;
    }

    .lyly-header-6__dropdown-menu {
        position: absolute;
        top: calc(100% + 10px);
        left: 0;
        z-index: 99;
        min-width: 170px;
        padding: 8px;
        margin: 0;
        list-style: none;
        background: #fff;
        border: 1px solid rgba(1, 15, 28, 0.1);
        border-radius: 10px;
        box-shadow: 0 16px 40px rgba(1, 15, 28, 0.12);
        opacity: 0;
        visibility: hidden;
        transform: translateY(8px);
        transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
    }

    .lyly-header-6__dropdown.is-open > .lyly-header-6__dropdown-menu,
    .lyly-header-6__dropdown:hover > .lyly-header-6__dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .lyly-header-6__dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 6px 10px;
        border-radius: 7px;
        font-size: 14px;
        font-weight: 600;
        color: #222;
    }

    .lyly-header-6__dropdown-menu a:hover {
        color: #b50000;
        background: rgba(181, 0, 0, 0.08);
    }

    .lyly-header-6__badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: linear-gradient(180deg, #1fc49b 0%, #008f70 100%);
        border: 2px solid #fff;
        box-shadow: 0 6px 12px rgba(0, 143, 112, 0.22);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
    }

    .lyly-header-6__menu {
        --lyly-header-menu-height: 54px;
        --lyly-header-sticky-top: 5px;
        position: relative;
        min-height: var(--lyly-header-menu-height);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 45px;
        background: {{ $headerMainBackgroundColor }};
        color: {{ $headerMainTextColor }};
    }

    .lyly-header-6__menu.is-sticky {
        position: fixed;
        top: var(--lyly-header-sticky-top);
        left: 12px;
        right: 12px;
        width: calc(100% - 24px);
        z-index: 1048;
        border-radius: 999px;
        box-shadow: 0 10px 26px rgba(1, 15, 28, 0.14);
        animation: none;
    }

    .lyly-header-6__menu-spacer {
        display: none;
        height: var(--lyly-header-menu-height);
    }

    .lyly-header-6__menu-spacer.is-active {
        display: block;
    }

    .lyly-header-6__sticky-logo {
        position: absolute;
        left: 22px;
        top: 0;
        height: var(--lyly-header-menu-height);
        display: none;
        align-items: center;
        z-index: 2;
    }

    .lyly-header-6__menu.is-sticky .lyly-header-6__sticky-logo {
        display: flex;
    }

    .lyly-header-6__sticky-logo .logo {
        display: inline-flex;
        max-width: 140px;
        line-height: 1;
    }

    .lyly-header-6__sticky-logo img {
        max-width: 140px;
        max-height: 34px;
        object-fit: contain;
    }

    .lyly-header-6__nav {
        width: 100%;
        min-height: var(--lyly-header-menu-height);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lyly-header-6__nav-content {
        min-height: var(--lyly-header-menu-height);
        display: flex;
        align-items: center;
    }

    .lyly-main-menu {
        width: 100%;
        min-height: var(--lyly-header-menu-height);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 28px;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .lyly-main-menu__item {
        min-height: var(--lyly-header-menu-height);
        display: flex;
        align-items: center;
    }

    .lyly-main-menu__link {
        min-height: var(--lyly-header-menu-height);
        display: inline-flex;
        align-items: center;
        color: #000;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0;
        line-height: 1;
        text-transform: uppercase;
        border-bottom: 2px solid transparent;
        transition: border-color 0.2s ease, color 0.2s ease;
    }

    .lyly-header-6__menu.is-sticky .lyly-main-menu__link {
        transform: translateY(1px);
    }

    .lyly-main-menu__item:hover > .lyly-main-menu__link {
        color: #000;
        border-bottom-color: #b8b8b8;
    }

    .lyly-main-menu__mega {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 98;
        padding: 44px 45px 48px;
        background: #fff;
        border-top: 1px solid rgba(1, 15, 28, 0.08);
        opacity: 0;
        visibility: hidden;
        transform: translateY(8px);
        transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
    }

    .lyly-main-menu__mega,
    .lyly-main-menu__mega a,
    .lyly-main-menu__mega span,
    .lyly-main-menu__mega svg {
        color: #111;
    }

    .lyly-main-menu__item:hover > .lyly-main-menu__mega,
    .lyly-main-menu__item:focus-within > .lyly-main-menu__mega {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .lyly-main-menu__mega-inner {
        display: grid;
        grid-template-columns: 250px minmax(0, 1fr);
        gap: 32px;
        align-items: start;
    }

    .lyly-main-menu__media {
        position: relative;
        min-height: 86px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        padding: 14px;
        overflow: hidden;
        color: #111;
        background: linear-gradient(135deg, #d9d9d9, #8e8e8e);
        font-size: 13px;
        font-weight: 800;
        line-height: 1.2;
        text-align: center;
        text-transform: uppercase;
    }

    .lyly-main-menu__media::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.52));
    }

    .lyly-main-menu__media span {
        position: relative;
        z-index: 1;
    }

    .lyly-main-menu__columns {
        display: grid;
        grid-template-columns: repeat(4, minmax(140px, 1fr));
        gap: 42px;
    }

    .lyly-main-menu__column,
    .lyly-main-menu__sub-list {
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .lyly-main-menu__heading,
    .lyly-main-menu__sub-link {
        display: inline-flex;
        align-items: center;
        color: #232323;
        line-height: 1.35;
    }

    .lyly-main-menu__heading {
        margin-bottom: 10px;
        color: #000;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .lyly-main-menu__sub-list li + li {
        margin-top: 9px;
    }

    .lyly-main-menu__sub-link {
        font-size: 14px;
        font-weight: 400;
        text-transform: none;
    }

    .lyly-main-menu__heading:hover,
    .lyly-main-menu__sub-link:hover {
        color: #b50000;
    }

    .lyly-header-6__menu-toggle {
        display: none;
    }

    .lyly-header-6__menu-toggle--mobile {
        display: none;
    }

    .lyly-header-6__user-icon {
        display: none;
    }

    .lyly-header-6__dropdown--account .lyly-header-6__dropdown-menu {
        left: auto;
        right: 0;
    }

    @media (max-width: 1199px) {
        .lyly-header-6__top,
        .lyly-header-6__menu {
            padding-left: 24px;
            padding-right: 24px;
        }

        .lyly-header-6__left {
            overflow-x: auto;
            scrollbar-width: none;
        }

        .lyly-header-6__left::-webkit-scrollbar {
            display: none;
        }
    }

    @media (max-width: 991px) {
        .lyly-header-6__top {
            grid-template-columns: minmax(96px, 1fr) auto minmax(0, 1fr);
            gap: 12px;
            min-height: 68px;
            padding-left: 14px;
            padding-right: 14px;
        }

        .lyly-header-6__menu {
            display: none;
            min-height: 48px;
            padding-left: 14px;
            padding-right: 14px;
        }

        .lyly-header-6__menu-spacer {
            display: none !important;
        }

        .lyly-header-6__nav {
            display: none;
        }

        .lyly-header-6__menu-toggle--mobile {
            display: inline-flex;
        }

        .lyly-header-6__left {
            overflow: visible;
        }

        .lyly-header-6__logo {
            min-width: 90px;
        }

        .lyly-header-6__logo img {
            max-width: min(190px, 36vw);
            height: 38px;
            max-height: 38px;
        }

        .lyly-header-6__search-form.is-open {
            width: min(230px, 34vw);
        }

        .lyly-header-6__pill,
        .lyly-header-6__icon,
        .lyly-header-6__search-submit,
        .lyly-header-6__menu-toggle {
            width: 38px;
            height: 38px;
        }

        .lyly-header-6__pill svg,
        .lyly-header-6__icon svg,
        .lyly-header-6__search-submit svg,
        .lyly-header-6__menu-toggle svg {
            width: 18px;
            height: 18px;
        }

        .lyly-header-6__search-form {
            --lyly-header-search-size: 38px;
            width: var(--lyly-header-search-size);
            height: var(--lyly-header-search-size);
        }

        .lyly-header-6__badge {
            top: -5px;
            right: -5px;
            min-width: 17px;
            height: 17px;
            font-size: 10px;
        }

        .lyly-header-6__hide-tablet {
            display: none !important;
        }

        .lyly-header-6__user-name,
        .lyly-header-6__account-chevron {
            display: none;
        }

        .lyly-header-6__user-icon {
            display: inline-flex;
        }

        .lyly-header-6__dropdown-menu {
            left: auto;
            right: 0;
        }

        .lyly-header-6__pill {
            padding: 0 12px;
        }

        .lyly-header-6__account-toggle {
            width: 38px;
            padding: 0;
        }
    }

    @media (max-width: 575px) {
        .lyly-header-6__top {
            gap: 8px;
        }

        .lyly-header-6__left {
            gap: 7px;
        }

        .lyly-header-6__logo img {
            max-width: 34vw;
            height: 34px;
            max-height: 34px;
        }

        .lyly-header-6__pill,
        .lyly-header-6__icon,
        .lyly-header-6__search-submit,
        .lyly-header-6__menu-toggle {
            width: 34px;
            height: 34px;
        }

        .lyly-header-6__pill svg,
        .lyly-header-6__icon svg,
        .lyly-header-6__search-submit svg,
        .lyly-header-6__menu-toggle svg {
            width: 16px;
            height: 16px;
        }

        .lyly-header-6__search-form {
            --lyly-header-search-size: 34px;
            width: var(--lyly-header-search-size);
            height: var(--lyly-header-search-size);
        }

        .lyly-header-6__search-form.is-open {
            width: min(170px, 42vw);
        }

        .lyly-header-6__search-form input.lyly-header-6__search-input {
            padding-right: 48px;
        }

        .lyly-header-6__search-form .bb-quick-search-results {
            min-width: min(220px, calc(100vw - 28px));
        }

        .lyly-header-6__badge {
            top: -5px;
            right: -5px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            font-size: 9px;
        }

        .lyly-header-6__account-toggle {
            width: 34px;
        }

        .lyly-header-6__search-form.is-open .lyly-header-6__search-submit {
            left: calc(100% - var(--lyly-header-search-size));
        }

        .lyly-header-6__currency-label {
            display: none;
        }
    }
</style>

<header class="lyly-header-6">
    <div class="lyly-header-6__top">
        <div class="lyly-header-6__left">
            <button type="button" class="lyly-header-6__menu-toggle lyly-header-6__menu-toggle--mobile tp-offcanvas-open-btn" aria-label="{{ __('Open menu') }}">
                <x-core::icon name="ti ti-menu-2" />
            </button>

            @if(is_plugin_active('ecommerce'))
                <x-plugins-ecommerce::fronts.ajax-search id="lyly-header-6-search-form" class="lyly-header-6__search-form" data-lyly-header-search>
                    <x-plugins-ecommerce::fronts.ajax-search.input class="lyly-header-6__search-input" />
                    <button type="submit" class="lyly-header-6__search-submit" aria-label="{{ __('Search') }}" data-lyly-header-search-submit>
                        <x-core::icon name="ti ti-search" />
                    </button>
                </x-plugins-ecommerce::fronts.ajax-search>
            @endif
        </div>

        <div class="lyly-header-6__logo">
            {!! Theme::partial('header.logo') !!}
        </div>

        <div class="lyly-header-6__right">
            @if (is_plugin_active('language') && $supportedLocales && count($supportedLocales) > 1)
                <div class="lyly-header-6__dropdown lyly-header-6__hide-tablet">
                    <button type="button" class="lyly-header-6__icon " aria-label="{{ __('Change language') }}" data-lyly-header-dropdown>
                        {!! language_flag(Language::getCurrentLocaleFlag(), Language::getCurrentLocaleName(), 15) !!}
                    </button>
                    <ul class="lyly-header-6__dropdown-menu">
                        @foreach ($supportedLocales as $localeCode => $properties)
                            <li>
                                <a href="{{ Language::getSwitcherUrl($localeCode, $properties['lang_code']) }}">
                                    {!! language_flag($properties['lang_flag'], $properties['lang_name']) !!}
                                    <span>{{ $properties['lang_name'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- @if ($hasCurrencies)
                <div class="lyly-header-6__dropdown">
                    <button type="button" class="lyly-header-6__pill" data-lyly-header-dropdown>
                        <span class="lyly-header-6__currency-label">{{ get_application_currency()->title }}</span>
                        <x-core::icon name="ti ti-chevron-down" />
                    </button>
                    {!! Theme::partial('currency-switcher', ['class' => 'lyly-header-6__dropdown-menu']) !!}
                </div>
            @endif -->

            @if(is_plugin_active('ecommerce'))
                @if(EcommerceHelper::isCompareEnabled())
                    <a href="{{ route('public.compare') }}" class="lyly-header-6__icon lyly-header-6__hide-tablet" aria-label="{{ __('Compare') }}">
                        <x-core::icon name="ti ti-arrows-sort" />
                        <span class="lyly-header-6__badge" data-bb-value="compare-count">{{ Cart::instance('compare')->count() }}</span>
                    </a>
                @endif

                @if(EcommerceHelper::isWishlistEnabled())
                    <a href="{{ route('public.wishlist') }}" class="lyly-header-6__icon" aria-label="{{ __('Wishlist') }}">
                        <x-core::icon name="ti ti-heart" />
                        <span class="lyly-header-6__badge" data-bb-value="wishlist-count">{{ Cart::instance('wishlist')->count() }}</span>
                    </a>
                @endif

                @if(EcommerceHelper::isCartEnabled())
                    <button type="button" class="lyly-header-6__icon cartmini-open-btn" data-bb-toggle="open-mini-cart" data-url="{{ route('public.ajax.cart-content') }}" aria-label="{{ __('View cart') }}">
                        <x-core::icon name="ti ti-shopping-bag" />
                        <span class="lyly-header-6__badge" data-bb-value="cart-count">{{ Cart::instance('cart')->count() }}</span>
                    </button>
                @endif

                @auth('customer')
                    <div class="lyly-header-6__dropdown lyly-header-6__dropdown--account">
                        <button type="button" class="lyly-header-6__pill lyly-header-6__account-toggle" aria-label="{{ __('Account') }}" aria-expanded="false" data-lyly-header-dropdown>
                            <span class="lyly-header-6__user-icon" aria-hidden="true">
                                <x-core::icon name="ti ti-user" />
                            </span>
                            <span class="lyly-header-6__user-name">{{ Str::limit(auth('customer')->user()->name ?? '', 16) }}</span>
                            <span class="lyly-header-6__account-chevron" aria-hidden="true">
                                <x-core::icon name="ti ti-chevron-down" />
                            </span>
                        </button>
                        <ul class="lyly-header-6__dropdown-menu">
                            <li><a href="{{ route('customer.overview') }}">{{ __('My Profile') }}</a></li>
                            <li><a href="{{ route('customer.orders') }}">{{ __('Orders') }}</a></li>
                            <li><a href="{{ route('customer.logout') }}">{{ __('Logout') }}</a></li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('customer.login') }}" class="lyly-header-6__pill lyly-header-6__account-toggle" aria-label="{{ __('Login') }}">
                        <span class="lyly-header-6__user-icon" aria-hidden="true">
                            <x-core::icon name="ti ti-user" />
                        </span>
                        <span class="lyly-header-6__user-name">{{ __('Login') }}</span>
                    </a>
                @endauth
            @endif
        </div>
    </div>

    <div class="lyly-header-6__menu-spacer" data-lyly-header-menu-spacer></div>

    <div
        id="header-sticky"
        class="lyly-header-6__menu"
        data-lyly-header-menu
    >
        <button type="button" class="lyly-header-6__menu-toggle tp-offcanvas-open-btn" aria-label="{{ __('Open menu') }}">
            <x-core::icon name="ti ti-menu-2" />
        </button>

        <div class="lyly-header-6__sticky-logo">
            {!! Theme::partial('header.logo') !!}
        </div>

        <div class="lyly-header-6__nav">
            <nav class="lyly-header-6__nav-content">
                {!! Menu::renderMenuLocation('main-menu', ['view' => 'lyly-main-menu']) !!}
            </nav>
        </div>
    </div>
</header>

<script>
    var lylyHeaderSearch = document.querySelector('[data-lyly-header-search]');

    if (lylyHeaderSearch) {
        var lylyHeaderSearchInput = lylyHeaderSearch.querySelector('.lyly-header-6__search-input');
        var lylyHeaderSearchSubmit = lylyHeaderSearch.querySelector('[data-lyly-header-search-submit]');
        var isLylyHeaderCompactSearch = function () {
            return window.matchMedia('(max-width: 575px)').matches;
        };

        var openLylyHeaderSearch = function () {
            lylyHeaderSearch.classList.add('is-open');

            window.setTimeout(function () {
                lylyHeaderSearchInput.focus();
            }, 180);
        };

        var closeLylyHeaderSearch = function () {
            lylyHeaderSearch.classList.remove('is-open');
            lylyHeaderSearchInput.blur();
        };

        lylyHeaderSearchSubmit.addEventListener('click', function (event) {
            if (isLylyHeaderCompactSearch()) {
                event.preventDefault();
                closeLylyHeaderSearch();

                var searchArea = document.querySelector('.tp-search-area');
                var bodyOverlay = document.querySelector('.body-overlay');

                if (searchArea) {
                    searchArea.classList.add('opened');
                }

                if (bodyOverlay) {
                    bodyOverlay.classList.add('opened');
                }

                return;
            }

            if (!lylyHeaderSearch.classList.contains('is-open')) {
                event.preventDefault();
                openLylyHeaderSearch();

                return;
            }

            if (!lylyHeaderSearchInput.value.trim()) {
                event.preventDefault();
                lylyHeaderSearchInput.focus();
            }
        });

        lylyHeaderSearch.addEventListener('submit', function (event) {
            if (!lylyHeaderSearch.classList.contains('is-open')) {
                event.preventDefault();
                openLylyHeaderSearch();

                return;
            }

            if (!lylyHeaderSearchInput.value.trim()) {
                event.preventDefault();
                lylyHeaderSearchInput.focus();
            }
        });

        document.addEventListener('click', function (event) {
            if (lylyHeaderSearch.classList.contains('is-open') && !lylyHeaderSearch.contains(event.target)) {
                closeLylyHeaderSearch();
            }
        });
    }

    var lylyHeaderMenu = document.querySelector('[data-lyly-header-menu]');
    var lylyHeaderMenuSpacer = document.querySelector('[data-lyly-header-menu-spacer]');

    if (lylyHeaderMenu && lylyHeaderMenuSpacer) {
        var lylyHeaderStickyGap = 5;
        var lylyHeaderStickyOffset = lylyHeaderStickyGap;
        var lylyHeaderMenuTop = 0;
        var lylyHeaderMenuHeight = 0;

        var updateLylyHeaderStickyOffset = function () {
            var adminBar = document.getElementById('admin_bar');
            var adminBarHeight = 0;

            if (adminBar) {
                var adminBarRect = adminBar.getBoundingClientRect();

                adminBarHeight = adminBarRect.height || 0;
            }

            lylyHeaderStickyOffset = adminBarHeight + lylyHeaderStickyGap;
            lylyHeaderMenu.style.setProperty('--lyly-header-sticky-top', lylyHeaderStickyOffset + 'px');
        };

        var measureLylyHeaderMenu = function () {
            var wasSticky = lylyHeaderMenu.classList.contains('is-sticky');

            updateLylyHeaderStickyOffset();

            if (wasSticky) {
                lylyHeaderMenu.classList.remove('is-sticky');
                lylyHeaderMenuSpacer.classList.remove('is-active');
            }

            var rect = lylyHeaderMenu.getBoundingClientRect();

            lylyHeaderMenuTop = rect.top + window.pageYOffset;
            lylyHeaderMenuHeight = rect.height;
            lylyHeaderMenuSpacer.style.height = lylyHeaderMenuHeight + 'px';

            if (wasSticky) {
                lylyHeaderMenu.classList.add('is-sticky');
                lylyHeaderMenuSpacer.classList.add('is-active');
            }
        };

        var updateLylyHeaderMenuSticky = function () {
            updateLylyHeaderStickyOffset();

            var shouldStick = window.innerWidth > 991 && window.pageYOffset >= lylyHeaderMenuTop - lylyHeaderStickyOffset;

            lylyHeaderMenu.classList.toggle('is-sticky', shouldStick);
            lylyHeaderMenuSpacer.classList.toggle('is-active', shouldStick);
        };

        measureLylyHeaderMenu();
        updateLylyHeaderMenuSticky();

        window.addEventListener('scroll', updateLylyHeaderMenuSticky, { passive: true });
        window.addEventListener('resize', function () {
            measureLylyHeaderMenu();
            updateLylyHeaderMenuSticky();
        });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-lyly-header-dropdown]');

        document.querySelectorAll('.lyly-header-6__dropdown.is-open').forEach(function (dropdown) {
            if (!trigger || !dropdown.contains(trigger)) {
                dropdown.classList.remove('is-open');

                var dropdownTrigger = dropdown.querySelector('[data-lyly-header-dropdown]');

                if (dropdownTrigger) {
                    dropdownTrigger.setAttribute('aria-expanded', 'false');
                }
            }
        });

        if (!trigger) {
            return;
        }

        event.preventDefault();
        var dropdown = trigger.closest('.lyly-header-6__dropdown');
        var isOpen = dropdown.classList.toggle('is-open');

        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
</script>
