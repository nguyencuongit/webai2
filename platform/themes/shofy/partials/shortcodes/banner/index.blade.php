@php
    $position = in_array($shortcode->position, ['left', 'center', 'right'], true) ? $shortcode->position : 'center';
    $textColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $shortcode->text_color) ? $shortcode->text_color : '#ffffff';
    $fontFamilies = [
        'inherit' => ['stack' => 'inherit'],
        'inter' => ['stack' => 'Inter, Arial, sans-serif', 'google' => 'Inter:wght@400;500;600;700'],
        'poppins' => ['stack' => 'Poppins, Arial, sans-serif', 'google' => 'Poppins:wght@400;500;600;700'],
        'montserrat' => ['stack' => 'Montserrat, Arial, sans-serif', 'google' => 'Montserrat:wght@400;500;600;700'],
        'roboto' => ['stack' => 'Roboto, Arial, sans-serif', 'google' => 'Roboto:wght@400;500;700'],
        'arial' => ['stack' => 'Arial, sans-serif'],
        'open-sans' => ['stack' => '\'Open Sans\', Arial, sans-serif', 'google' => 'Open+Sans:wght@400;500;600;700'],
        'georgia' => ['stack' => 'Georgia, serif'],
        'lora' => ['stack' => 'Lora, Georgia, serif', 'google' => 'Lora:wght@400;500;600;700'],
        'merriweather' => ['stack' => 'Merriweather, Georgia, serif', 'google' => 'Merriweather:wght@400;700'],
        'times-new-roman' => ['stack' => '\'Times New Roman\', serif'],
        'helvetica-neue' => ['stack' => '\'Helvetica Neue\', Arial, sans-serif'],
        'playfair-display' => ['stack' => '\'Playfair Display\', Georgia, serif', 'google' => 'Playfair+Display:wght@400;500;600;700'],
        'cormorant-garamond' => ['stack' => '\'Cormorant Garamond\', Georgia, serif', 'google' => 'Cormorant+Garamond:wght@400;500;600;700'],
        'dancing-script' => ['stack' => '\'Dancing Script\', cursive', 'google' => 'Dancing+Script:wght@400;500;600;700'],
        'great-vibes' => ['stack' => '\'Great Vibes\', cursive', 'google' => 'Great+Vibes'],
    ];
    $legacyFontFamilies = collect($fontFamilies)->mapWithKeys(fn ($font, $key) => [$font['stack'] => $key])->all() + [
        '"Open Sans", Arial, sans-serif' => 'open-sans',
        '"Times New Roman", serif' => 'times-new-roman',
        '"Helvetica Neue", Arial, sans-serif' => 'helvetica-neue',
        '"Playfair Display", Georgia, serif' => 'playfair-display',
        '"Cormorant Garamond", Georgia, serif' => 'cormorant-garamond',
        '"Dancing Script", cursive' => 'dancing-script',
        '"Great Vibes", cursive' => 'great-vibes',
    ];
    $selectedFont = (string) $shortcode->font_family;
    $fontKey = array_key_exists($selectedFont, $fontFamilies)
        ? $selectedFont
        : ($legacyFontFamilies[$selectedFont] ?? 'inherit');
    $font = $fontFamilies[$fontKey];
    $fontFamily = $font['stack'];
    $videoUrl = RvMedia::url($shortcode->image);
    $videoExtension = strtolower(pathinfo(parse_url($videoUrl, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
    $videoType = match ($videoExtension) {
        'webm' => 'video/webm',
        'ogg', 'ogv' => 'video/ogg',
        default => 'video/mp4',
    };
    
    $separatedSlogans = [
        $shortcode->slogan_1,
        $shortcode->slogan_2,
        $shortcode->slogan_3,
    ];

    $hasSeparatedSlogans = collect($separatedSlogans)->contains(function ($slogan): bool {
        $plainText = html_entity_decode(strip_tags((string) $slogan));
        $plainText = str_replace("\xc2\xa0", ' ', $plainText);

        return trim($plainText) !== '';
    });

    $sloganContent = (string) $shortcode->slogans;
    $sloganItems = $hasSeparatedSlogans
        ? $separatedSlogans
        : preg_split('/<hr\b[^>]*>|<p>\s*-{3,}\s*<\/p>|\R\s*-{3,}\s*\R/i', $sloganContent);

    $slogans = collect($sloganItems)->filter(function ($slogan): bool {
        $plainText = html_entity_decode(strip_tags((string) $slogan));
        $plainText = str_replace("\xc2\xa0", ' ', $plainText);

        return trim($plainText) !== '';
    })->map(function ($slogan): string {
        return str_replace(['“', '”', 'â€œ', 'â€', '&quot;', '&#34;'], '"', (string) $slogan);
    })->values();
    $sloganDuration = max($slogans->count(), 1) * 4;
@endphp

@if (isset($font['google']))
    {!! BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family=' . $font['google'] . '&display=swap') !!}
@endif

@if ($shortcode->overlap_title && $fontKey !== 'great-vibes')
    {!! BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap') !!}
@endif

<div class="lyly-banner-composition" data-lyly-banner-composition>
    <section
        {!! $shortcode->htmlAttributes() !!}
        class="lyly-shortcode-banner lyly-shortcode-banner--{{ $position }} lyly-shortcode-banner--font-{{ $fontKey }} lyly-shortcode-banner--slogans-{{ $slogans->count() }}"
        style="--lyly-banner-color: {{ $textColor }};"
    >
        <div class="lyly-shortcode-banner__media">
            {{-- {!! RvMedia::image($shortcode->image, $shortcode->title ?: __('Banner'), attributes: ['loading' => 'lazy']) !!} --}}
            <video autoplay muted loop playsinline preload="metadata">
                <source src="{{ $videoUrl }}" type="{{ $videoType }}">
            </video>
        </div>

        @if ($slogans->isNotEmpty())
            <div class="lyly-shortcode-banner__content">
                <div class="lyly-shortcode-banner__slogans" style="--lyly-slogan-duration: {{ $sloganDuration }}s;">
                    @foreach ($slogans as $slogan)
                        <div class="lyly-shortcode-banner__slogan ck-content" style="--lyly-slogan-delay: {{ $loop->index * 4 }}s;">
                            {!! BaseHelper::clean($slogan) !!}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @include(Theme::getThemeNamespace('partials.shortcodes.banner.overlap'), ['shortcode' => $shortcode])
</div>

<style>
    .lyly-banner-composition {
        --lyly-banner-overlap-travel: clamp(110px, 14vw, 240px);
        position: relative;
        overflow: hidden;
        height: auto;
    }

    .lyly-shortcode-banner {
        position: relative;
        width: 100%;
        z-index: 1;
        overflow: hidden;
        color: var(--lyly-banner-color);
    }

    .lyly-shortcode-banner--font-inherit {
        font-family: inherit;
    }

    .lyly-shortcode-banner--font-inter {
        font-family: Inter, Arial, sans-serif;
    }

    .lyly-shortcode-banner--font-poppins {
        font-family: Poppins, Arial, sans-serif;
    }

    .lyly-shortcode-banner--font-montserrat {
        font-family: Montserrat, Arial, sans-serif;
    }

    .lyly-shortcode-banner--font-roboto {
        font-family: Roboto, Arial, sans-serif;
    }

    .lyly-shortcode-banner--font-arial {
        font-family: Arial, sans-serif;
    }

    .lyly-shortcode-banner--font-open-sans {
        font-family: 'Open Sans', Arial, sans-serif;
    }

    .lyly-shortcode-banner--font-georgia {
        font-family: Georgia, serif;
    }

    .lyly-shortcode-banner--font-lora {
        font-family: Lora, Georgia, serif;
    }

    .lyly-shortcode-banner--font-merriweather {
        font-family: Merriweather, Georgia, serif;
    }

    .lyly-shortcode-banner--font-times-new-roman {
        font-family: 'Times New Roman', serif;
    }

    .lyly-shortcode-banner--font-helvetica-neue {
        font-family: 'Helvetica Neue', Arial, sans-serif;
    }

    .lyly-shortcode-banner--font-playfair-display {
        font-family: 'Playfair Display', Georgia, serif;
    }

    .lyly-shortcode-banner--font-cormorant-garamond {
        font-family: 'Cormorant Garamond', Georgia, serif;
    }

    .lyly-shortcode-banner--font-dancing-script {
        font-family: 'Dancing Script', cursive;
    }

    .lyly-shortcode-banner--font-great-vibes {
        font-family: 'Great Vibes', cursive;
    }

    .lyly-shortcode-banner__media img {
        display: block;
        width: 100%;
        height: auto;
    }

    .lyly-shortcode-banner__media video {
        display: block;
        width: 100%;
        height: auto;
    }

    .lyly-shortcode-banner__content {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        padding: clamp(24px, 5vw, 72px);
        pointer-events: none;
    }

    .lyly-shortcode-banner--left .lyly-shortcode-banner__content {
        justify-content: flex-start;
        text-align: left;
    }

    .lyly-shortcode-banner--center .lyly-shortcode-banner__content {
        justify-content: center;
        text-align: center;
    }

    .lyly-shortcode-banner--right .lyly-shortcode-banner__content {
        justify-content: flex-end;
        text-align: right;
    }

    .lyly-shortcode-banner__slogans {
        position: relative;
        width: min(560px, 100%);
        min-height: clamp(40px, 6vw, 78px);
    }

    .lyly-shortcode-banner__slogan {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        margin: 0;
        color: inherit;
        font-family: inherit;
        font-size: clamp(28px, 5vw, 64px);
        font-weight: 700;
        line-height: 1.08;
        opacity: 0;
        transform: translateY(14px);
        animation: lyly-banner-slogan var(--lyly-slogan-duration) ease-in-out infinite;
        animation-delay: var(--lyly-slogan-delay);
    }

    .lyly-shortcode-banner--left .lyly-shortcode-banner__slogan {
        align-items: flex-start;
    }

    .lyly-shortcode-banner--right .lyly-shortcode-banner__slogan {
        align-items: flex-end;
    }

    .lyly-shortcode-banner__slogan :where(h1, h2, h3, h4, p, ul, ol) {
        margin: 0;
        color: inherit;
        font-family: inherit;
        line-height: inherit;
    }

    .lyly-shortcode-banner__slogan :where(ul, ol) {
        padding-left: 1.2em;
    }

    .lyly-shortcode-banner__slogan .text-tiny {
        font-size: .7em;
    }

    .lyly-shortcode-banner__slogan .text-small {
        font-size: .85em;
    }

    .lyly-shortcode-banner__slogan .text-big {
        font-size: 1.4em;
    }

    .lyly-shortcode-banner__slogan .text-huge {
        font-size: 1.8em;
    }

    @keyframes lyly-banner-slogan {
        0% {
            opacity: 0;
            transform: translateY(14px);
        }

        8%,
        26% {
            opacity: 1;
            transform: translateY(0);
        }

        33%,
        100% {
            opacity: 0;
            transform: translateY(-10px);
        }
    }

    .lyly-shortcode-banner--slogans-1 .lyly-shortcode-banner__slogan {
        position: static;
        opacity: 1;
        transform: none;
        animation: none;
    }

    .lyly-banner-overlap {
        position: relative;
        z-index: 2;
        min-height: clamp(330px, 42vw, 630px);
        display: flex;
        align-items: center;
        width: 100%;
        padding: clamp(36px, 6vw, 92px) clamp(20px, 7vw, 120px);
        overflow: hidden;
        background-color: #f4f0fb;
        background-image: var(--lyly-banner-overlap-bg);
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;
        box-shadow: 0 -18px 45px rgba(1, 15, 28, 0.12);
        transform: translate3d(0, 0, 0);
        will-change: transform;
    }

    .lyly-banner-overlap__inner {
        position: relative;
        z-index: 1;
        width: min(1180px, 100%);
        margin: 0 auto;
    }

    .lyly-banner-overlap__content {
        position: relative;
        z-index: 1;
        width: min(470px, 100%);
    }

    .lyly-banner-overlap__content h2 {
        margin: 0;
        color: #d85a9a;
        font-family: 'Great Vibes', cursive;
        font-size: clamp(30px, 4vw, 58px);
        font-weight: 400;
        line-height: 1.05;
    }

    .lyly-banner-overlap__content p {
        width: min(420px, 100%);
        margin: 14px 0 0;
        color: #6c6375;
        font-size: clamp(14px, 1.3vw, 18px);
        line-height: 1.65;
    }

    @media (max-width: 575px) {
        .lyly-banner-composition {
            --lyly-banner-overlap-travel: 72px;
        }

        .lyly-shortcode-banner__content {
            padding: 20px;
        }

        .lyly-shortcode-banner__slogans {
            width: min(320px, 100%);
        }

        .lyly-banner-overlap {
            min-height: 390px;
            padding: 44px 20px 32px;
            background-position: center right;
        }
    }

    @media (min-width: 576px) and (max-width: 991px) {
        .lyly-banner-composition {
            --lyly-banner-overlap-travel: 120px;
        }
    }
</style>

<script>
    (function () {
        var compositions = document.querySelectorAll('[data-lyly-banner-composition]');

        if (!compositions.length) {
            return;
        }

        var clamp = function (value, min, max) {
            return Math.min(Math.max(value, min), max);
        };
        var getNumber = function (value) {
            return parseFloat(value) || 0;
        };
        var items = Array.prototype.map.call(compositions, function (composition) {
            return {
                composition: composition,
                banner: composition.querySelector('.lyly-shortcode-banner'),
                panel: composition.querySelector('[data-lyly-banner-overlap-panel]'),
                travel: 0,
            };
        }).filter(function (item) {
            return item.banner && item.panel;
        });
        var ticking = false;

        if (!items.length) {
            return;
        }

        var measure = function () {
            items.forEach(function (item) {
                item.composition.style.height = 'auto';

                var configuredTravel = getNumber(getComputedStyle(item.composition).getPropertyValue('--lyly-banner-overlap-travel'));
                var bannerHeight = item.banner.getBoundingClientRect().height;
                var panelHeight = item.panel.getBoundingClientRect().height;

                if (!bannerHeight || !panelHeight) {
                    return;
                }

                item.travel = Math.min(Math.max(configuredTravel, panelHeight * 0.85), bannerHeight);
                item.composition.style.height = (bannerHeight + panelHeight - item.travel ).toFixed(2) + 'px';
            });
        };
        var update = function () {
            ticking = false;

            items.forEach(function (item) {
                var rect = item.banner.getBoundingClientRect();
                var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                var lead = viewportHeight * 0.16;
                var distance = Math.max(item.travel * 0.55, viewportHeight * 0.1);
                var progress = clamp((-rect.top - lead) / distance, 0, 1);
                var y = item.travel * progress * -1;

                item.panel.style.transform = 'translate3d(0, ' + y.toFixed(2) + 'px, 0)';
            });
        };
        var requestUpdate = function () {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(update);
        };

        measure();
        update();
        window.requestAnimationFrame(function () {
            measure();
            update();
        });
        window.setTimeout(function () {
            measure();
            update();
        }, 300);

        items.forEach(function (item) {
            var media = item.banner.querySelector('img, video');

            if (!media) {
                return;
            }

            media.addEventListener('load', function () {
                measure();
                requestUpdate();
            });
            media.addEventListener('loadedmetadata', function () {
                measure();
                requestUpdate();
            });
        });

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', function () {
            measure();
            requestUpdate();
        });
        window.addEventListener('load', function () {
            measure();
            requestUpdate();
        });
    })();
</script>
