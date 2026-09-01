@php
    Theme::set('pageTitle', 'Studio ảnh');
@endphp

{!! Theme::partial('tool-workspace', [
    'type' => 'image',
    'title' => 'Studio ảnh',
    'subtitle' => 'Tạo hình ảnh chất lượng cao từ văn bản, áp dụng phong cách nghệ thuật đa dạng và chỉnh sửa chi tiết với độ chính xác cao.',
    'recentTitle' => 'Ảnh gần đây',
    'emptyText' => 'Chưa có ảnh nào. Hãy thử tạo ảnh đầu tiên!',
    'placeholder' => 'Mô tả chi tiết hình ảnh bạn muốn tạo...',
    'badge' => 'IMAGE',
    'model' => 'Flux Kontext - Pro',
    'cost' => 4,
    'submitLabel' => 'Tạo ảnh',
]) !!}
