<div class="offcanvas__area offcanvas__radius">
    <style>
        .lyly-offcanvas-menu {
            margin-bottom: 40px;
        }

        .lyly-mobile-menu,
        .lyly-mobile-menu--sub {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .lyly-mobile-menu__item + .lyly-mobile-menu__item {
            border-top: 1px solid rgba(1, 15, 28, 0.08);
        }

        .lyly-mobile-menu__row {
            min-height: 42px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lyly-mobile-menu__link {
            min-width: 0;
            flex: 1 1 auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 0;
            color: #222;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .lyly-mobile-menu__link svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
        }

        .lyly-mobile-menu__toggle {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: #222;
        }

        .lyly-mobile-menu__toggle svg {
            width: 18px;
            height: 18px;
            transition: transform 0.2s ease;
        }

        .lyly-mobile-menu__item.is-open > .lyly-mobile-menu__row .lyly-mobile-menu__toggle svg {
            transform: rotate(180deg);
        }

        .lyly-mobile-menu__children {
            display: none;
            padding: 0 0 8px 14px;
        }

        .lyly-mobile-menu__item.is-open > .lyly-mobile-menu__children {
            display: block;
        }

        .lyly-mobile-menu--sub .lyly-mobile-menu__link {
            font-size: 13px;
            font-weight: 600;
            text-transform: none;
        }
    </style>

    <div class="offcanvas__wrapper">
        <div class="offcanvas__close">
            <button class="offcanvas__close-btn offcanvas-close-btn" aria-label="{{ __('Close menu') }}">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 1L1 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M1 1L11 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="offcanvas__content">
            <div class="offcanvas__top mb-70 d-flex justify-content-between align-items-center">
                <div class="offcanvas__logo logo">
                    {!! Theme::partial('header.logo') !!}
                </div>
            </div>
            @if (is_plugin_active('ecommerce') && theme_option('enabled_header_categories_dropdown_on_mobile', 'yes') === 'yes')
                <div class="pb-40 offcanvas__category">
                    <button class="tp-offcanvas-category-toggle">
                        <x-core::icon name="ti ti-menu-2" />
                        {{ __('All Categories') }}
                    </button>
                    <div class="tp-category-mobile-menu"></div>
                </div>
            @endif

            <div class="lyly-offcanvas-menu d-xl-none">
                {!! Menu::renderMenuLocation('main-menu', ['view' => 'lyly-mobile-menu']) !!}
            </div>

            @if ($hotline = theme_option('hotline'))
                <div class="offcanvas__btn">
                    <a href="tel:{{ $hotline }}" class="tp-btn-2 tp-btn-border-2">
                        {{ __('Contact Us') }}
                    </a>
                </div>
            @endif
        </div>
        <div class="offcanvas__bottom">
            <div class="offcanvas__footer d-flex align-items-center justify-content-between">
                @if (is_plugin_active('ecommerce') && ($currencies = get_all_currencies()) && $currencies->count() > 1)
                    <div class="offcanvas__currency-wrapper currency">
                        <span class="offcanvas__currency-selected-currency tp-currency-toggle" id="tp-offcanvas-currency-toggle">
                            {{ __('Currency: :currency', ['currency' => get_application_currency()->title]) }}
                        </span>
                        {!! Theme::partial('currency-switcher', ['class' => 'offcanvas__currency-list tp-currency-list']) !!}
                    </div>
                @endif

                {!! Theme::partial('language-switcher', ['type' => 'mobile']) !!}
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-lyly-mobile-menu-toggle]');

            if (!trigger) {
                return;
            }

            event.preventDefault();

            var item = trigger.closest('.lyly-mobile-menu__item');
            var isOpen = item.classList.toggle('is-open');

            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    </script>
</div>
