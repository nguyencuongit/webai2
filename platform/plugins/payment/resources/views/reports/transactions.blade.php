@extends(BaseHelper::getAdminMasterLayoutTemplate())

@php
    Assets::addScripts('apexchart')->addStyles('apexchart');
@endphp

@section('content')
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4">
        @include('plugins/payment::reports.partials.stat-card', [
            'label' => 'Tổng số giao dịch',
            'value' => number_format($report['total_transactions']),
            'icon' => 'ti ti-receipt',
            'color' => '#206bc4',
        ])
        @include('plugins/payment::reports.partials.stat-card', [
            'label' => 'Tổng tiền giao dịch thành công',
            'value' => number_format($report['total_completed_amount'], 0, ',', '.'),
            'suffix' => 'đ',
            'isMoney' => true,
            'icon' => 'ti ti-cash',
            'color' => '#2fb344',
        ])
        @include('plugins/payment::reports.partials.stat-card', [
            'label' => 'Tổng số người đã nạp',
            'value' => number_format($report['total_customers']),
            'icon' => 'ti ti-users',
            'color' => '#ae3ec9',
        ])
        @include('plugins/payment::reports.partials.stat-card', [
            'label' => 'Giao dịch thành công',
            'value' => number_format($report['total_completed_transactions']),
            'icon' => 'ti ti-circle-check',
            'color' => '#2fb344',
        ])
        @include('plugins/payment::reports.partials.stat-card', [
            'label' => 'Giao dịch chờ xử lý',
            'value' => number_format($report['total_pending_transactions']),
            'icon' => 'ti ti-clock-hour-4',
            'color' => '#f59f00',
        ])
    </div>

    <x-core::card class="mb-4" style="box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, .18) !important;">
        <x-core::card.body>
            <form action="{{ route('payments.reports.transactions') }}" method="GET" class="row g-3 align-items-end" id="transaction-report-filter">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="transaction-report-date">Chọn ngày</label>
                    <input id="transaction-report-date" class="form-control" name="date" type="date" value="{{ $date }}">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="transaction-report-month">Chọn tháng và năm</label>
                    <input id="transaction-report-month" class="form-control" name="month" type="month" value="{{ $month }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit"><x-core::icon name="ti ti-filter" /> Lọc</button>
                    <a class="btn btn-outline-secondary" href="{{ route('payments.reports.transactions') }}" title="Đặt lại"><x-core::icon name="ti ti-refresh" /></a>
                </div>
            </form>
        </x-core::card.body>
    </x-core::card>

    <x-core::card class="mb-4" style="box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, .18) !important;">
        <x-core::card.header>
            <div class="d-flex align-items-center justify-content-between gap-3 w-100">
                <x-core::card.title>{{ $report['chart']['title'] }}</x-core::card.title>
                @if ($month || $date)
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('payments.reports.transactions') }}"><x-core::icon name="ti ti-arrow-left" /> Quay lại theo tháng</a>
                @endif
            </div>
        </x-core::card.header>
        <x-core::card.body>
            <div id="transaction-report-chart"></div>
        </x-core::card.body>
    </x-core::card>

    <x-core::card style="box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, .18) !important;">
        <x-core::card.header>
            <div>
                <x-core::card.title>Đã chuyển khoản thành công nhưng đang chờ xử lý</x-core::card.title>
                <div class="text-muted small mt-1">Đối chiếu payment đang chờ với giao dịch tiền vào từ SePay trong 30 ngày gần nhất.</div>
            </div>
        </x-core::card.header>
        @if (! $report['sepay_available'])
            <x-core::alert type="warning" class="m-3 mb-0">{{ $report['sepay_error'] }}</x-core::alert>
        @endif
        <div class="table-responsive">
            <table class="table table-vcenter card-table mb-0">
                <thead>
                    <tr>
                        <th>Mã giao dịch</th>
                        <th class="text-end">Số tiền</th>
                        <th>Trạng thái thanh toán</th>
                        <th>Mã giao dịch SePay</th>
                        <th>Thời gian chuyển khoản</th>
                        <th>Trạng thái hệ thống</th>
                        <th class="text-end">Tác vụ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['pending_transfers'] as $payment)
                        <tr>
                            <td>
                                <a href="{{ route('payment.show', $payment['payment_id']) }}">{{ $payment['charge_id'] }}</a>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($payment['amount'], 0, ',', '.') }} {{ $payment['currency'] }}</td>
                            <td><span class="badge bg-success-lt">{{ $payment['payment_status_label'] }}</span></td>
                            <td>{{ $payment['sepay_transaction_id'] }}</td>
                            <td>{{ $payment['transferred_at'] ?: '—' }}</td>
                            <td><span class="badge bg-warning-lt">Đang chờ xử lý</span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-primary" href="{{ route('payments.reports.transactions.show', $payment['payment_id']) }}" title="Xem">
                                    <x-core::icon name="ti ti-eye" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                Không có giao dịch SePay đã chuyển khoản nhưng còn ở trạng thái chờ.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-core::card>
@endsection

@push('footer')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dateInput = document.getElementById('transaction-report-date');
            const monthInput = document.getElementById('transaction-report-month');

            dateInput.addEventListener('change', function () { if (this.value) monthInput.value = ''; });
            monthInput.addEventListener('change', function () { if (this.value) dateInput.value = ''; });

            const chartMonthKeys = @json($report['chart']['keys']);
            const reportUrl = @json(route('payments.reports.transactions'));

            new ApexCharts(document.getElementById('transaction-report-chart'), {
                series: [
                    { name: 'Giao dịch thành công', data: @json($report['chart']['completed']) },
                    { name: 'Giao dịch chờ xử lý', data: @json($report['chart']['pending']) },
                ],
                chart: {
                    type: 'bar', height: 350, stacked: true, toolbar: { show: false },
                    events: {
                        dataPointSelection: function (event, chartContext, config) {
                            const month = chartMonthKeys[config.dataPointIndex];
                            if (month) window.location.href = reportUrl + '?month=' + encodeURIComponent(month);
                        }
                    }
                },
                xaxis: { categories: @json($report['chart']['labels']) },
                yaxis: { labels: { formatter: function (value) { return Math.round(value); } } },
                colors: ['#2fb344', '#f59f00'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '52%' } },
                dataLabels: { enabled: false },
                legend: { position: 'top' },
                noData: { text: 'Chưa có giao dịch.' },
            }).render();
        });
    </script>
@endpush

