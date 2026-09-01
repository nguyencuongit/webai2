@php
    Theme::set('pageTitle', "N\u{1EA1}p Credit");
@endphp

<section class="webai-credit-page">
    <header class="webai-credit-header">
        <h1>N&#7841;p Credit</h1>
        <p>Mua th&#234;m Credit &#273;&#7875; ti&#7871;p t&#7909;c t&#7841;o &#7843;nh, video v&#224; gi&#7885;ng n&#243;i AI.</p>
        <strong>L&#432;u &#253;: H&#7841;n s&#7917; d&#7909;ng credits 3 th&#225;ng k&#7875; t&#7915; ng&#224;y n&#7841;p.</strong>
    </header>

    <div class="webai-pricing-grid">
        @forelse ($creditPackages as $package)
            <article class="webai-pricing-card">
                <h2>{{ $package->name }}</h2>
                <p>M&#227; g&#243;i: {{ $package->code }}</p>

                <div class="webai-price">{{ number_format($package->price, 0, ',', '.') }} &#273;</div>
                <div class="webai-credit-amount">{{ number_format($package->credits, 0, ',', '.') }} Credits</div>

                <a class="webai-buy-button" href="#" data-credit-package="{{ $package->code }}">Mua ngay</a>
            </article>
        @empty
            <p class="webai-credit-empty">Ch&#432;a c&#243; g&#243;i n&#7841;p credit n&#224;o.</p>
        @endforelse
    </div>
</section>
