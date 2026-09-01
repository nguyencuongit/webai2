@php
    Theme::set('pageTitle', 'Gói dịch vụ');
@endphp

<section class="service-plans">
    <header class="service-plans__header">
        <h1>Mở khóa toàn bộ <span>Chatbot Prompt</span></h1>
        <p>Truy cập không giới hạn, hướng dẫn chi tiết và cập nhật mới nhất mỗi tuần.</p>
        <div class="service-plans__tabs"><button class="is-active" type="button">Hàng tháng</button><button type="button">Gói năm Studio <em>Tặng khóa học</em></button></div>
    </header>

    <div class="service-plans__grid">
        @forelse ($creditPackages as $package)
            @php($features = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $package->features) ?: []))))
            <article class="service-plan {{ $package->is_popular ? 'is-featured' : '' }}">
                @if ($package->is_popular)<span class="service-plan__badge">✧ Được chọn nhiều nhất</span>@endif
                <header><i>✦</i><div><h2>{{ $package->name }}</h2><p>{{ number_format($package->credits, 0, ',', '.') }} Credit</p></div></header>
                <div class="service-plan__price">{{ number_format($package->price, 0, ',', '.') }}đ</div>
                <dl><div><dt>Số Credit nhận được</dt><dd>{{ number_format($package->credits, 0, ',', '.') }}</dd></div><div><dt>Mã gói</dt><dd>{{ $package->code }}</dd></div></dl>
                <form method="POST" action="{{ route('public.credit-package-purchases.start') }}" data-webai-credit-buy>@csrf<input type="hidden" name="package_id" value="{{ $package->id }}"><button class="service-plan__button" type="submit">Chọn gói này</button></form>
                @if ($features)<ul>@foreach ($features as $feature)<li>✓ <span>{{ $feature }}</span></li>@endforeach</ul>@endif
                <footer>♢ Bảo hành tài khoản AI 15 ngày · Hỗ trợ Zalo</footer>
            </article>
        @empty
            <p>Chưa có gói Credit nào.</p>
        @endforelse
    </div>
</section>

@if ($customer)
    {!! Theme::partial('credit-payment-modal', compact('customer')) !!}
@endif
