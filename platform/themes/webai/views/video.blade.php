@php
    Theme::set('pageTitle', 'Phòng lab video');
@endphp

{!! Theme::partial('tool-workspace', [
    'type' => 'video',
    'title' => 'Phòng lab video',
    'subtitle' => 'Biến ý tưởng của bạn thành những thước phim chuyển động chân thực và sống động bằng công nghệ AI tiên tiến nhất.',
    'recentTitle' => 'Video gần đây',
    'emptyText' => 'Chưa có video nào. Hãy thử tạo video đầu tiên!',
    'placeholder' => 'Mô tả chi tiết video bạn muốn tạo...',
    'badge' => 'VIDEO',
    'model' => 'Seedance 2.0 - Mini',
    'models' => $aiModels ?? [],
    'recentTasks' => $recentTasks ?? collect(),
    'submitLabel' => 'Tạo video',
]) !!}
