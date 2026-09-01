@php
    Theme::set('pageTitle', 'Thanh toán Credit');
@endphp

<section
    class="webai-credit-payment-page"
    data-webai-credit-payment
    data-payment-completed="{{ $isCompleted ? 'true' : 'false' }}"
    data-payment-status-url="{{ route('public.credit-package-purchases.status', $payment->charge_id) }}"
    data-credit-packages-url="{{ route('public.credit-packages.index') }}"
>
    <a class="webai-credit-payment-back" href="{{ route('public.credit-packages.index') }}">← Quay lại Mua Credit</a>

    <div class="webai-credit-payment-card">
        <div class="webai-credit-payment-details">
            <span class="webai-credit-payment-eyebrow">Thanh toán gói Credit</span>
            <h1>{{ data_get($payment->metadata, 'package_name') }}</h1>

            <dl class="webai-credit-payment-list">
                <div><dt>Người mua</dt><dd>{{ $customer->name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $customer->email }}</dd></div>
                @if ($customer->phone)
                    <div><dt>Số điện thoại</dt><dd>{{ $customer->phone }}</dd></div>
                @endif
                <div><dt>Mã gói</dt><dd>{{ data_get($payment->metadata, 'package_code') }}</dd></div>
                <div><dt>Credit nhận được</dt><dd>{{ number_format((int) data_get($payment->metadata, 'credits'), 0, ',', '.') }} Credits</dd></div>
                <div><dt>Nội dung chuyển khoản</dt><dd>{{ $payment->charge_id }}</dd></div>
                <div class="webai-credit-payment-total"><dt>Tổng thanh toán</dt><dd>{{ number_format($payment->amount, 0, ',', '.') }} đ</dd></div>
            </dl>
        </div>

        <div class="webai-credit-payment-qr">
            @if ($isCompleted)
                <div class="webai-credit-payment-success">
                    <span class="webai-credit-payment-success__icon">✓</span>
                    <strong>Chuyển khoản thành công</strong>
                    <small>Credit đã được cộng vào tài khoản của bạn.</small>
                    <em>Trang sẽ tự chuyển sau <b data-webai-payment-countdown>10</b>s</em>
                </div>
            @else
                <span>Mã QR thanh toán</span>
                @if ($qrCodeUrl)
                    <img src="{{ $qrCodeUrl }}" alt="Mã QR thanh toán SePay">
                    <small>Quét mã bằng ứng dụng ngân hàng để thanh toán đúng số tiền.</small>
                @else
                    <div class="webai-credit-payment-qr-empty">
                        <strong>Chưa thể tạo QR</strong>
                        <small>SePay chưa có tài khoản nhận tiền đang hoạt động.</small>
                    </div>
                @endif
            @endif
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const page = document.querySelector('[data-webai-credit-payment]');

        if (!page) {
            return;
        }

        if (page.dataset.paymentCompleted === 'true') {
            let seconds = 10;
            const countdown = page.querySelector('[data-webai-payment-countdown]');
            const interval = window.setInterval(function () {
                seconds -= 1;

                if (countdown) {
                    countdown.textContent = seconds;
                }

                if (seconds <= 0) {
                    window.clearInterval(interval);
                    window.location.href = page.dataset.creditPackagesUrl;
                }
            }, 1000);

            return;
        }

        window.setInterval(function () {
            fetch(page.dataset.paymentStatusUrl, { headers: { Accept: 'application/json' } })
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (data) {
                    if (data && data.completed) {
                        window.location.reload();
                    }
                })
                .catch(function () {});
        }, 3000);
    });
</script>
