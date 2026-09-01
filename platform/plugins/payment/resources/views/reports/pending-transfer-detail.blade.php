@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card style="box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, .18) !important;">
        <x-core::card.header>
            <x-core::card.title>Thông tin giao dịch chờ xác nhận</x-core::card.title>
        </x-core::card.header>
        <x-core::card.body>
            <x-core::datagrid>
                <x-core::datagrid.item>
                    <x-slot:title>Mã giao dịch</x-slot:title>
                    {{ $transfer['charge_id'] }}
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>Số tiền</x-slot:title>
                    {{ number_format($transfer['amount'], 0, ',', '.') }} {{ $transfer['currency'] }}
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>Trạng thái thanh toán</x-slot:title>
                    <span class="badge bg-success-lt">{{ $transfer['payment_status_label'] }}</span>
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>Mã giao dịch SePay</x-slot:title>
                    {{ $transfer['sepay_transaction_id'] }}
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>Thời gian chuyển khoản</x-slot:title>
                    {{ $transfer['transferred_at'] ?: '—' }}
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>Trạng thái hệ thống</x-slot:title>
                    <span class="badge bg-warning-lt">Đang chờ xử lý</span>
                </x-core::datagrid.item>
            </x-core::datagrid>

            <div class="mt-4 d-flex gap-2">
                <form method="POST" action="{{ route('payments.reports.transactions.confirm', $transfer['payment_id']) }}" onsubmit="return confirm('Xác nhận giao dịch này đã chuyển khoản thành công? Hệ thống sẽ chuyển payment sang Hoàn thành.');">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <x-core::icon name="ti ti-circle-check" /> Xác nhận đã chuyển khoản thành công
                    </button>
                </form>
                <a class="btn btn-outline-secondary" href="{{ route('payments.reports.transactions') }}">Quay lại</a>
            </div>
        </x-core::card.body>
    </x-core::card>
@endsection

