@php
    Theme::set('breadcrumbStyle', 'none');
    Theme::set('pageTitle', 'Đăng ký');
@endphp

<section class="webai-auth-page webai-auth-page--register">
    <div class="webai-auth-shell">
        <div class="webai-auth-copy">
            <div class="webai-status">
                <span></span>
                Bắt đầu ngay
            </div>
            <h1>Tạo tài khoản AI Video</h1>
            <p>Đăng ký để tạo ảnh, tạo video và quản lý credit trong không gian WebAI của bạn.</p>
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
                                            <x-core::icon name="ti ti-user-plus" class="text-primary" />
                                        </div>
                                        <div>
                                            <h3 class="auth-card__header-title fs-4 mb-1">Đăng ký tài khoản</h3>
                                            <p class="auth-card__header-description text-muted">Tạo tài khoản để sử dụng và quản lý không gian AI của bạn.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="auth-card__body">
                                    @include('plugins/ai-video-generator::auth.partials.messages')
                                    @include('plugins/ai-video-generator::auth.partials.social-login')

                                    <form method="POST" action="{{ route('ai-video-generator.register.post') }}">
                                        @csrf

                                        <div class="mb-3">
                                            <label class="form-label" for="name">Họ và tên</label>
                                            <div class="position-relative">
                                                <span class="auth-input-icon input-group-text"><x-core::icon name="ti ti-user" /></span>
                                                <input class="form-control ps-5" id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nhập họ và tên" autocomplete="name" required>
                                            </div>
                                            @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="email">Email</label>
                                            <div class="position-relative">
                                                <span class="auth-input-icon input-group-text"><x-core::icon name="ti ti-mail" /></span>
                                                <input class="form-control ps-5" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Nhập email" autocomplete="email" required>
                                            </div>
                                            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="phone">Số điện thoại (không bắt buộc)</label>
                                            <div class="position-relative">
                                                <span class="auth-input-icon input-group-text"><x-core::icon name="ti ti-phone" /></span>
                                                <input class="form-control ps-5" id="phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="Nhập số điện thoại" autocomplete="tel">
                                            </div>
                                            @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="password">Mật khẩu</label>
                                            <div class="position-relative">
                                                <span class="auth-input-icon input-group-text"><x-core::icon name="ti ti-lock" /></span>
                                                <input class="form-control ps-5" id="password" name="password" type="password" placeholder="Nhập mật khẩu" autocomplete="new-password" required>
                                            </div>
                                            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="password_confirmation">Nhập lại mật khẩu</label>
                                            <div class="position-relative">
                                                <span class="auth-input-icon input-group-text"><x-core::icon name="ti ti-lock" /></span>
                                                <input class="form-control ps-5" id="password_confirmation" name="password_confirmation" type="password" placeholder="Nhập lại mật khẩu" autocomplete="new-password" required>
                                            </div>
                                        </div>

                                        <label class="form-check mb-3">
                                            <input class="form-check-input" name="agree_terms_and_policy" type="checkbox" value="1">
                                            <span class="form-check-label">Tôi đồng ý với Điều khoản và Chính sách bảo mật</span>
                                        </label>
                                        @error('agree_terms_and_policy') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                                        <button class="btn btn-primary" type="submit">
                                            Đăng ký
                                            <x-core::icon name="ti ti-arrow-narrow-right" />
                                        </button>

                                        <div class="mt-3 text-center">
                                            Đã có tài khoản?
                                            <a class="ms-1 text-decoration-underline" href="{{ route('ai-video-generator.login') }}">Đăng nhập</a>
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
