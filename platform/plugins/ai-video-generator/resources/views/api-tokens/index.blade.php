@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @if (auth()->user()?->hasPermission('ai-video-generator.api-tokens.edit'))
        <x-core::card class="mb-3">
            <x-core::card.header>
                <div>
                    <x-core::card.title>KiotProxy động cho RoboNeo</x-core::card.title>
                    <div class="text-muted mt-1">
                        Mỗi IP chỉ nhận tối đa số task đã cấu hình. Khi đủ giới hạn, hệ thống đóng lô, chờ toàn bộ task kết thúc rồi đổi IP trước khi nhận lô tiếp theo. Lỗi 6003 cũng đóng lô ngay lập tức.
                    </div>
                </div>
            </x-core::card.header>

            <x-core::card.body>
                <x-core::alert type="info" class="mb-3">
                    Key được mã hóa trong database và không hiển thị lại. Khi có ít nhất một key đang bật, KiotProxy là đường truyền chính; proxy pool tĩnh bên dưới chỉ là dự phòng khi không bật key nào.
                </x-core::alert>

                <x-core::form :url="route('ai-video-generator.api-tokens.kiot-proxy-keys.store')" method="POST">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-2">
                            <x-core::form.text-input name="name" label="Tên key" placeholder="KiotProxy 01" required />
                        </div>
                        <div class="col-md-4">
                            <x-core::form.text-input name="proxy_key" type="password" label="KiotProxy key" autocomplete="new-password" required />
                        </div>
                        <div class="col-md-2">
                            <x-core::form.select name="region" label="Khu vực">
                                <option value="random">Ngẫu nhiên</option>
                                <option value="bac">Miền Bắc</option>
                                <option value="trung">Miền Trung</option>
                                <option value="nam">Miền Nam</option>
                            </x-core::form.select>
                        </div>
                        <div class="col-md-2">
                            <x-core::form.text-input name="max_concurrent_tasks" type="number" label="Task tối đa / proxy" value="5" min="1" max="15" required />
                        </div>
                        <div class="col-md-2">
                            <x-core::button type="submit" color="primary" icon="ti ti-plus" class="w-100">Thêm key</x-core::button>
                        </div>
                    </div>
                </x-core::form>

                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Khu vực</th>
                                <th>Trạng thái</th>
                                <th>IP hiện tại</th>
                                <th>Hết hạn / có thể đổi</th>
                                <th>Task / giới hạn</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kiotProxyKeys as $key)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $key->name }}</div>
                                        <small class="text-muted">Key #{{ $key->getKey() }} · ********</small>
                                    </td>
                                    <td>{{ strtoupper($key->region) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $key->is_active ? ($key->health_status === 'healthy' ? 'success' : 'warning') : 'secondary' }} text-white">
                                            {{ $key->is_active ? $key->health_status : 'disabled' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $key->proxy_fingerprint ?: '—' }}
                                        @if ($key->location)<small class="d-block text-muted">{{ $key->location }}</small>@endif
                                    </td>
                                    <td>
                                        <small>{{ $key->expiration_at?->format('d/m/Y H:i:s') ?: '—' }}</small>
                                        @if ($key->next_request_at)<small class="d-block text-muted">Đổi từ {{ $key->next_request_at->format('d/m/Y H:i:s') }}</small>@endif
                                    </td>
                                    <td>
                                        @php($activeLeases = (int) ($key->active_task_leases_count ?? 0))
                                        @if ($activeLeases > 0)
                                            <span class="badge bg-blue text-white">{{ $activeLeases }} / {{ $key->max_concurrent_tasks ?: 5 }}</span>
                                            @if ($key->batch_sealed)
                                                <span class="badge bg-warning text-white">Đang đóng lô</span>
                                            @endif
                                            <small class="d-block text-muted">đến {{ $key->lease_until->format('H:i:s') }}</small>
                                        @else
                                            <span class="text-muted">0 / {{ $key->max_concurrent_tasks ?: 5 }}</span>
                                        @endif
                                        <form class="d-flex gap-1 mt-1" method="POST" action="{{ route('ai-video-generator.api-tokens.kiot-proxy-keys.capacity', $key) }}">
                                            @csrf @method('PATCH')
                                            <input class="form-control form-control-sm" style="width: 68px" type="number" name="max_concurrent_tasks" min="1" max="15" value="{{ $key->max_concurrent_tasks ?: 5 }}" aria-label="Task tối đa mỗi proxy">
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Lưu</button>
                                        </form>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <form class="d-inline" method="POST" action="{{ route('ai-video-generator.api-tokens.kiot-proxy-keys.rotate', $key) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" type="submit" @disabled($activeLeases > 0)>Đổi IP</button>
                                        </form>
                                        <form class="d-inline" method="POST" action="{{ route('ai-video-generator.api-tokens.kiot-proxy-keys.toggle', $key) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">{{ $key->is_active ? 'Tắt' : 'Bật' }}</button>
                                        </form>
                                        <form class="d-inline" method="POST" action="{{ route('ai-video-generator.api-tokens.kiot-proxy-keys.destroy', $key) }}" onsubmit="return confirm('Xóa KiotProxy key này?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit" @disabled($activeLeases > 0)>Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Chưa có KiotProxy key; hệ thống đang dùng proxy pool tĩnh.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-core::card.body>
        </x-core::card>

        <x-core::form
            :url="route('ai-video-generator.api-tokens.proxy-pool.update')"
            method="PUT"
        >
            <x-core::card class="mb-3">
                <x-core::card.header>
                    <div>
                        <x-core::card.title>Proxy pool RoboNeo dự phòng</x-core::card.title>
                        <div class="text-muted mt-1">
                            Quản lý đường truyền proxy dùng khi gửi và theo dõi task RoboNeo.
                        </div>
                    </div>
                </x-core::card.header>

                <x-core::card.body>
                    <x-core::alert type="info" class="mb-3">
                        Có thể nhập URL <code>http://user:password@host:port</code> hoặc
                        <code>host:port:user:password</code>, mỗi proxy một dòng. Dữ liệu đăng nhập được mã hóa trong database.
                    </x-core::alert>

                    <x-core::form.textarea
                        name="proxy_pool"
                        label="Danh sách proxy"
                        :value="old('proxy_pool', $proxyPool)"
                        rows="7"
                        placeholder="http://user:password@proxy.example.com:3128"
                        :helper-text="sprintf('Đang cấu hình %d proxy. Để trống và lưu nếu muốn tắt proxy pool.', $proxyCount)"
                        spellcheck="false"
                        autocomplete="off"
                    />
                </x-core::card.body>

                <x-core::card.footer class="d-flex justify-content-end">
                    <x-core::button type="submit" color="primary" icon="ti ti-device-floppy">
                        Lưu proxy pool
                    </x-core::button>
                </x-core::card.footer>
            </x-core::card>
        </x-core::form>
    @else
        <x-core::alert type="info" class="mb-3">
            RoboNeo đang sử dụng {{ $proxyCount }} proxy. Bạn không có quyền sửa cấu hình này.
        </x-core::alert>
    @endif

    {!! $table->render('core/table::base-table') !!}
@endsection
