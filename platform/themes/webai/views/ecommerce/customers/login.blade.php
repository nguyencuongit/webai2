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
            {!! $form->bannerDirection('horizontal')->renderForm() !!}
        </div>
    </div>
</section>
