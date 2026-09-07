@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    @if (auth()->user()?->hasPermission('ai-video-generator.api-tokens.edit'))
        <x-core::form
            :url="route('ai-video-generator.api-tokens.proxy-pool.update')"
            method="PUT"
        >
            <x-core::card class="mb-3">
                <x-core::card.header>
                    <div>
                        <x-core::card.title>Proxy pool RoboNeo</x-core::card.title>
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
