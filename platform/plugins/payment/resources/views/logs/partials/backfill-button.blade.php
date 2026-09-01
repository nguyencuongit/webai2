<form method="POST" action="{{ route('payments.logs.backfill') }}" onsubmit="return confirm('Cập nhật nhật ký cho các giao dịch SePay đã hoàn thành nhưng chưa có log?');">
    @csrf
    <button type="submit" class="btn btn-outline-primary text-nowrap">
        <x-core::icon name="ti ti-refresh" /> Cập nhật nhật ký
    </button>
</form>

