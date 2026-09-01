<div class="webai-payment-modal" data-webai-payment-modal hidden aria-hidden="true">
    <div class="webai-payment-modal__backdrop" data-webai-payment-close></div>
    <section class="webai-payment-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="webai-payment-title">
        <button class="webai-payment-modal__close" type="button" aria-label="Đóng" data-webai-payment-close>×</button>

        <div class="webai-payment-modal__details">
            <span class="webai-payment-modal__eyebrow">Thanh toán gói Credit</span>
            <h2 id="webai-payment-title" data-webai-payment-package-name>Gói Credit</h2>

            <dl class="webai-payment-modal__list">
                <div><dt>Người mua</dt><dd>{{ $customer->name }}</dd></div>
                <div><dt>Email</dt><dd>{{ $customer->email }}</dd></div>
                @if ($customer->phone)
                    <div><dt>Số điện thoại</dt><dd>{{ $customer->phone }}</dd></div>
                @endif
                <div><dt>Mã gói</dt><dd data-webai-payment-package-code>—</dd></div>
                <div><dt>Credit nhận được</dt><dd data-webai-payment-package-credits>—</dd></div>
                <div class="webai-payment-modal__total"><dt>Tổng thanh toán</dt><dd data-webai-payment-package-price>—</dd></div>
            </dl>
        </div>

        <div class="webai-payment-modal__qr">
            <img class="webai-payment-modal__qr-image" data-webai-payment-qr-image alt="QR thanh toán SePay" hidden>
            <small class="webai-payment-modal__qr-note" data-webai-payment-qr-note hidden>Quét mã bằng ứng dụng ngân hàng để thanh toán đúng số tiền.</small>
            <span>Mã QR thanh toán</span>
            <div class="webai-payment-modal__qr-placeholder" aria-label="Mã QR sẽ được thêm sau khi kết nối SePay">
                <span>QR</span>
                <small>Đang chờ kết nối SePay</small>
            </div>
        </div>
        <img class="webai-payment-modal__qr-image" data-webai-payment-qr-image alt="QR thanh toán SePay" hidden>
        <small class="webai-payment-modal__qr-note" data-webai-payment-qr-note hidden>Quét mã bằng ứng dụng ngân hàng để thanh toán đúng số tiền.</small>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.querySelector('[data-webai-payment-modal]');

        if (!modal) {
            return;
        }

        const closeModal = function () {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
        };

        const openModal = function (packageData) {
                const qrImage = modal.querySelector('[data-webai-payment-qr-image]');
                const qrPlaceholder = modal.querySelector('.webai-payment-modal__qr-placeholder');
                const qrNote = modal.querySelector('[data-webai-payment-qr-note]');

                modal.querySelector('[data-webai-payment-package-name]').textContent = packageData.name || 'Gói Credit';
                modal.querySelector('[data-webai-payment-package-code]').textContent = packageData.code || '—';
                modal.querySelector('[data-webai-payment-package-credits]').textContent = packageData.credits ? packageData.credits + ' Credits' : '—';
                modal.querySelector('[data-webai-payment-package-price]').textContent = packageData.price ? packageData.price + ' đ' : '—';
                const hasQr = Boolean(packageData.qr_url);
                qrImage.hidden = !hasQr;
                qrPlaceholder.hidden = hasQr;
                qrNote.hidden = !hasQr;

                if (hasQr) {
                    qrImage.src = packageData.qr_url;
                } else {
                    qrImage.removeAttribute('src');
                }

                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
        };

        document.querySelectorAll('form[data-webai-credit-buy]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const button = form.querySelector('button[type="submit"]');
                button.disabled = true;

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                })
                    .then(async function (response) {
                        const payload = await response.json();

                        if (!response.ok) {
                            if (response.status === 401 && payload.login_url) {
                                window.location.href = payload.login_url;
                            }

                            throw new Error(payload.message || 'Không thể tạo yêu cầu thanh toán.');
                        }

                        return payload;
                    })
                    .then(function (payload) {
                        openModal(payload.data || {});
                    })
                    .catch(function (error) {
                        window.alert(error.message);
                    })
                    .finally(function () {
                        button.disabled = false;
                    });
            });
        });

        modal.querySelectorAll('[data-webai-payment-close]').forEach(function (element) {
            element.addEventListener('click', closeModal);
        });
    });
</script>
