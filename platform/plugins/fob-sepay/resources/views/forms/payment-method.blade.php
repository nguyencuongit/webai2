@php
    $name = Arr::get($formOptions, 'payment_name');
    $id = Arr::get($formOptions, 'payment_id');
    $logo = Arr::get($formOptions, 'payment_logo');
    $url = Arr::get($formOptions, 'payment_url');
    $description = Arr::get($formOptions, 'payment_description');
    $defaultDescriptionValue = Arr::get($formOptions, 'default_description_value');
    $status = get_payment_setting('status', $id);
    $sepayClient = new \FriendsOfBotble\SePay\SePayClient();
    $isConnected = $sepayClient->isConnected();
@endphp

<x-core::card class="mb-3">
    <x-core::table :hover="false" :striped="false">
        <x-core::table.body>
            <x-core::table.body.row>
                <x-core::table.body.cell class="border-end" style="width: 5%">
                    <x-core::icon name="ti ti-wallet" />
                </x-core::table.body.cell>
                <x-core::table.body.cell style="width: 20%">
                    <img src="{{ $logo }}" alt="{{ $name }}" style="width: 8rem">
                </x-core::table.body.cell>
                <x-core::table.body.cell>
                    @if($url)
                        <a href="{{ $url }}" target="_blank">{{ $name }}</a>
                    @else
                        {{ $name }}
                    @endif
                    @if($description)
                        <p class="mb-0">{{ $description }}</p>
                    @endif
                </x-core::table.body.cell>
            </x-core::table.body.row>
            <x-core::table.body.row>
                <x-core::table.body.cell colspan="3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div @class(['payment-name-label-group', 'hidden' => !$status])>
                                {{ trans('plugins/payment::payment.use') }}
                                <span class="method-name-label">{{ get_payment_setting('name', $id) }}</span>
                            </div>
                        </div>

                        <x-core::button
                            @class(['edit-payment-item-btn-trigger', 'hidden' => !$status])
                            onclick="toggleSePaySettings(this)"
                        >
                            {{ trans('plugins/payment::payment.edit') }}
                        </x-core::button>
                        <x-core::button
                            @class(['save-payment-item-btn-trigger', 'hidden' => $status])
                            onclick="toggleSePaySettings(this)"
                        >
                            {{ trans('plugins/payment::payment.settings') }}
                        </x-core::button>
                    </div>
                </x-core::table.body.cell>
            </x-core::table.body.row>
            <x-core::table.body.row class="payment-content-item hidden">
                <x-core::table.body.cell colspan="3">
                    @if ($isConnected)
                        @php
                            $profile = $form->getData('profile');
                        @endphp

                        @if($profile)
                            <div
                                class="sepay-connected-profile bg-body p-3 rounded mb-3"
                                data-get-bank-sub-accounts-url="{{ route('sepay.bank-sub-accounts') }}"
                                data-get-payment-codes-url="{{ route('sepay.payment-codes') }}"
                                data-bank-sub-account-id="{{ get_payment_setting('bank_sub_account_id', SEPAY_PAYMENT_METHOD_NAME) }}"
                                data-payment-code-prefix="{{ get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME) }}"
                            >
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                    <span class="avatar avatar-lg" style="background-image: url({{ $profile->avatar }});"></span>
                                    <div>
                                        <h4 class="mb-1">
                                            {{ $profile->last_name . ' ' . $profile->first_name }}
                                            <span class="badge bg-success text-bg-success ms-2">Đã kết nối</span>
                                        </h4>
                                        <p class="d-flex align-items-center gap-1 text-muted mb-0 small">
                                            <x-core::icon name="ti ti-id" />
                                            ID: {{ $profile->id }}
                                        </p>
                                    </div>
                                    <div class="ms-0 ms-lg-auto">
                                        <x-core::button type="button" color="danger" size="sm" outlined="true" onclick="disconnectSepay()">
                                            <x-core::icon name="ti ti-unlink" class="me-1" />
                                            Ngắt kết nối tài khoản
                                        </x-core::button>
                                    </div>
                                </div>

                                <div class="sepay-account-details">
                                    <div class="d-flex flex-wrap gap-4">
                                        <div class="d-flex align-items-center">
                                            <x-core::icon name="ti ti-mail" class="text-muted" />
                                            <span class="ms-2">{{ $profile->email }}</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <x-core::icon name="ti ti-phone" class="text-muted" />
                                            <span class="ms-2">{{ $profile->phone }}</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <x-core::icon name="ti ti-calendar" class="text-muted" />
                                            <span class="ms-2">{{ setting('sepay_connected_at') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (! $profile)
                            <div class="sepay-connected-profile bg-body p-3 rounded mb-3 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <h4 class="mb-1">
                                        SePay API Token
                                        <span class="badge bg-success text-bg-success ms-2">Đã kết nối</span>
                                    </h4>
                                    <p class="text-muted mb-0 small">API Token đã được xác thực. Hãy cấu hình tài khoản ngân hàng bên dưới.</p>
                                </div>
                                <x-core::button type="button" color="danger" size="sm" outlined="true" onclick="disconnectSepay()">
                                    <x-core::icon name="ti ti-unlink" class="me-1" />
                                    Ngắt kết nối
                                </x-core::button>
                            </div>
                        @endif

                        <x-core::form :url="route('payments.methods.post')">
                            <input type="hidden" name="type" value="{{ $id }}" class="payment_type" />

                            <div class="row">
                                <div class="col-md-6">
                                    <x-core::form.text-input
                                        :label="trans('plugins/payment::payment.method_name')"
                                        :name="get_payment_setting_key('name', $id)"
                                        data-counter="400"
                                        :value="get_payment_setting('name', $id, trans('plugins/payment::payment.pay_online_via', ['name' => $name]))"
                                    />

                                    <x-core::form.textarea
                                        :label="trans('core/base::forms.description')"
                                        :name="get_payment_setting_key('description', $id)"
                                        :value="get_payment_setting('description', $id, $defaultDescriptionValue)"
                                    />

                                    <x-core::form-group>
                                        <x-core::form.label for="{{ $logoKey = get_payment_setting_key('logo', $id) }}">
                                            {{ trans('plugins/payment::payment.method_logo') }}
                                        </x-core::form.label>
                                        {{ Form::mediaImage($logoKey, get_payment_setting('logo', $id)) }}
                                    </x-core::form-group>

                                    {{ $form->getOpenWrapperFormColumns() }}

                                    @foreach ($fields as $field)
                                        @continue(in_array($field->getName(), $exclude))

                                        {!! $field->render() !!}
                                    @endforeach

                                    {{ $form->getCloseWrapperFormColumns() }}

                                    {!! apply_filters(PAYMENT_METHOD_SETTINGS_CONTENT, null, $id) !!}
                                </div>
                            </div>

                            <div class="btn-list justify-content-end">
                                <x-core::button
                                    type="button"
                                    @class(['disable-payment-item', 'hidden' => !$status])
                                >
                                    {{ trans('plugins/payment::payment.deactivate') }}
                                </x-core::button>

                                <x-core::button
                                    @class(['save-payment-item btn-text-trigger-save', 'hidden' => $status])
                                    type="button"
                                    color="info"
                                >
                                    {{ trans('plugins/payment::payment.activate') }}
                                </x-core::button>
                                <x-core::button
                                    type="button"
                                    color="info"
                                    @class(['save-payment-item btn-text-trigger-update', 'hidden' => !$status])
                                    onclick="submitSePaySettings(this, event)"
                                >
                                    {{ trans('plugins/payment::payment.update') }}
                                </x-core::button>
                            </div>
                        </x-core::form>
                    @else
                        <div class="sepay-oauth-container">
                            <div class="row align-items-center mb-4">
                                <div class="col-md-4">
                                    <img src="{{ asset('vendor/core/plugins/fob-sepay/images/sepay.png') }}" alt="SePay" class="img-fluid rounded" />
                                </div>
                                <div class="col-md-8">
                                    <h3 class="mb-2 fw-bold text-primary">Kết nối với SePay</h3>
                                    <p class="text-muted mb-0">Kết nối tài khoản SePay của bạn để bắt đầu nhận thanh toán trực tuyến an toàn và thuận tiện.</p>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-center mb-4">
                                <x-core::icon name="ti ti-info-circle" class="me-3 fs-4" />
                                <div>
                                    Bạn cần có tài khoản SePay để tiếp tục. Sau khi kết nối, bạn có thể cấu hình thêm các tùy chọn thanh toán.
                                </div>
                            </div>

                            <form method="POST" action="{{ route('sepay.api-token.connect') }}" class="mx-auto mb-4" style="max-width: 36rem">
                                @csrf
                                <label for="sepay-api-token" class="form-label">SePay API Token</label>
                                <input
                                    id="sepay-api-token"
                                    name="api_token"
                                    type="password"
                                    class="form-control @error('api_token') is-invalid @enderror"
                                    value="{{ old('api_token') }}"
                                    autocomplete="off"
                                    required
                                >
                                @error('api_token')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="text-center mt-3">
                                    <x-core::button type="submit" color="primary">
                                        <x-core::icon name="ti ti-key" class="me-2" />
                                        Connect with API Token
                                    </x-core::button>
                                </div>
                            </form>

                            <div class="text-center mb-4 d-none">
                                <x-core::button
                                    type="button"
                                    onclick="openSepayOAuth()"
                                    color="primary"
                                >
                                    <x-core::icon name="ti ti-link" class="me-2" />
                                    Kết nối với SePay ngay
                                </x-core::button>
                            </div>
                        </div>
                    @endif
                </x-core::table.body.cell>
            </x-core::table.body.row>
        </x-core::table.body>
    </x-core::table>
</x-core::card>


<script>
    function toggleSePaySettings(button) {
        const content = button.closest('tbody')?.querySelector('.payment-content-item');

        content?.classList.toggle('hidden');
    }

    function submitSePaySettings(button, event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        const form = button.closest('form');

        $httpClient.make()
            .withButtonLoading($(button))
            .post(form.action, $(form).serialize())
            .then(({ data }) => Botble.showSuccess(data.message))
            .catch((error) => {
                const message = error.response?.data?.message || 'Không thể cập nhật cấu hình SePay.';
                Botble.showError(message);
            });

        return false;
    }

    function openSepayOAuth() {
        document.getElementById('sepay-api-token')?.focus();
    }

    function disconnectSepay() {
        if (confirm('Bạn có chắc chắn muốn ngắt kết nối tài khoản SePay?')) {
            $.ajax({
                url: '{{ route('sepay.oauth.disconnect') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {
                    if (response.data.success) {
                        location.reload();
                    } else {
                        alert('Đã xảy ra lỗi. Vui lòng thử lại.');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Đã xảy ra lỗi. Vui lòng thử lại.');
                }
            });
        }
    }
</script>
