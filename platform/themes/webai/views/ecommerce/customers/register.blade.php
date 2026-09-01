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
            {!! $form->bannerDirection('horizontal')->renderForm() !!}
        </div>
    </div>
</section>
