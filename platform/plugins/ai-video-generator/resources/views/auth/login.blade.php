@php
    Theme::set('breadcrumbStyle', 'none');
    Theme::set('pageTitle', 'Đăng nhập');
@endphp

<section class="webai-auth-page webai-auth-page--login">
    <div class="webai-auth-shell">
        <div class="webai-auth-copy">
            <div class="webai-status">
                <span></span>
                Không gian AI
            </div>
            <h1>Đăng nhập để tiếp tục sáng tạo</h1>
            <p>Quản lý credit, lịch sử tạo nội dung và các công cụ AI trong một không gian làm việc gọn gàng.</p>
        </div>

        <div class="webai-auth-form">
            <div class="container">
                <div class="row justify-content-center py-5">
                    <div class="col-lg-10">
                        <div class="auth-card auth-card__horizontal">
                            <div class="auth-card__right">
                                <div class="auth-card__header">
                                    <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                                        <div class="auth-card__header-icon bg-white p-3 rounded">
                                            <x-core::icon name="ti ti-lock" class="text-primary" />
                                        </div>
                                        <div>
                                            <h3 class="auth-card__header-title fs-4 mb-1">Đăng nhập tài khoản</h3>
                                            <p class="auth-card__header-description text-muted">Thông tin của bạn được dùng để bảo vệ và quản lý quyền truy cập tài khoản.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="auth-card__body">
                                    @include('plugins/ai-video-generator::auth.partials.messages')
                                    @include('plugins/ai-video-generator::auth.partials.social-login')

                                    <form method="POST" action="{{ route('ai-video-generator.login.post') }}">
                                        @csrf

                                        <div class="mb-3">
                                            <label class="form-label" for="email">Email hoặc số điện thoại</label>
                                            <div class="position-relative">
                                                <span class="auth-input-icon input-group-text"><x-core::icon name="ti ti-mail" /></span>
                                                <input class="form-control ps-5" id="email" name="email" type="text" value="{{ old('email', Cookie::get('customer_remember_email')) }}" placeholder="Nhập email hoặc số điện thoại" autocomplete="email" required>
                                            </div>
                                            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="password">Mật khẩu</label>
                                            <div class="position-relative">
                                                <span class="auth-input-icon input-group-text"><x-core::icon name="ti ti-lock" /></span>
                                                <input class="form-control ps-5" id="password" name="password" type="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                                            </div>
                                            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="row g-0 mb-3">
                                            <label class="form-check col-6">
                                                <input class="form-check-input" name="remember" type="checkbox" value="1">
                                                <span class="form-check-label">Ghi nhớ đăng nhập</span>
                                            </label>
                                        </div>

                                        <button class="btn btn-primary" type="submit">
                                            Đăng nhập
                                            <x-core::icon name="ti ti-arrow-narrow-right" />
                                        </button>

                                        <div class="mt-3 text-center">
                                            Chưa có tài khoản?
                                            <a class="ms-1 text-decoration-underline" href="{{ route('ai-video-generator.register') }}">Đăng ký ngay</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
