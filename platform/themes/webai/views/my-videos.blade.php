@php
    Theme::set('pageTitle', 'Video của tôi');
@endphp

<section class="my-videos-dashboard">
    <div class="my-videos-dashboard__top">
        <div>
            <h1><span aria-hidden="true">▦</span> Bảng điều khiển</h1>
            <p>Theo dõi tiến độ tạo video, xem trước và tải xuống khi video hoàn tất.</p>
            <div class="my-videos-dashboard__note">
                <strong>Note:</strong> Người dùng có thể thoát trình duyệt, không cần treo máy trong lúc đợi AI tạo video.<br>
                Dữ liệu sẽ tự động xóa sau 3 ngày để bảo mật giúp khách hàng.
            </div>
        </div>

        <div class="my-videos-dashboard__controls">
            <label class="my-videos-search">
                <input type="search" aria-label="Tìm theo tiêu đề" placeholder="Tìm theo tiêu đề...">
            </label>
            <div class="my-videos-filters" aria-label="Lọc video">
                <button class="is-active" type="button">✦ Tất cả</button>
                <button type="button">⌛ Hàng đợi</button>
                <button type="button">◌ Đang xử lý</button>
                <button type="button">◉ Đã hoàn thành</button>
                <button type="button">⊗ Chưa thành công</button>
            </div>
        </div>
    </div>

    <div class="my-videos-dashboard__meta">
        <span>Hiển thị 0/0 video</span>
        <span>Trang: 0/0</span>
    </div>

    <div class="my-videos-empty">
        <strong>☻ Bạn chưa có video nào.</strong>
        <span>Hãy tạo video đầu tiên ngay!</span>
        <a href="{{ route('public.video-lab') }}">Tạo video</a>
    </div>
</section>
