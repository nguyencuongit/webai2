@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Nhập API token từ Excel</h4>
                </div>
                <div class="card-body">
                    @if (session('error_msg'))
                        <div class="alert alert-danger">{{ session('error_msg') }}</div>
                    @endif

                    @if (session('success_msg'))
                        <div class="alert alert-success">{{ session('success_msg') }}</div>
                    @endif

                    @if (session('import_errors'))
                        <div class="alert alert-warning mb-3">
                            <div class="fw-bold mb-1">Các dòng không được nhập:</div>
                            <ul class="mb-0">
                                @foreach (session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="text-secondary">Chấp nhận file <code>.xlsx</code>, <code>.xls</code> hoặc <code>.csv</code>, tối đa 5 MB. Token đã tồn tại sẽ được bỏ qua.</p>

                    <p>
                        <a class="btn btn-outline-primary" href="{{ route('ai-video-generator.api-tokens.import.template') }}">
                            <x-core::icon name="ti ti-download" /> Tải file Excel mẫu
                        </a>
                    </p>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered mb-0">
                            <thead><tr><th>name *</th><th>token_api *</th></tr></thead>
                            <tbody><tr><td>Tên tài khoản</td><td>Token RoboNeo</td></tr></tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('ai-video-generator.api-tokens.import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label required" for="api-token-import-file">File Excel</label>
                            <input class="form-control @error('file') is-invalid @enderror" id="api-token-import-file" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit"><x-core::icon name="ti ti-upload" /> Nhập dữ liệu</button>
                            <a class="btn btn-outline-secondary" href="{{ route('ai-video-generator.api-tokens.index') }}">Quay lại</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
