@php
    $items = [
        [
            'title' => 'Trang chủ',
            'route' => 'public.home',
            'icon' => 'home',
            'active' => request()->routeIs('public.home', 'public.index'),
        ],
        [
            'title' => 'Studio ảnh',
            'route' => 'public.studio-image',
            'icon' => 'image',
            'active' => request()->routeIs('public.studio-image'),
        ],
        [
            'title' => 'Phòng lab video',
            'route' => 'public.video-lab',
            'icon' => 'video',
            'active' => request()->routeIs('public.video-lab'),
        ],
        [
            'title' => 'Chỉnh sửa video',
            'url' => '#',
            'icon' => 'scissors',
            'chevron' => true,
        ],
        [
            'title' => 'Giọng nói',
            'url' => '#',
            'icon' => 'mic',
            'chevron' => true,
        ],
        [
            'title' => 'Nạp tiền',
            'url' => '#',
            'icon' => 'card',
        ],
    ];
@endphp

<ul class="webai-nav-list">
    @foreach ($items as $item)
        @php
            $url = isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : ($item['url'] ?? '#');
            $isActive = $item['active'] ?? request()->url() === $url;
        @endphp

        <li>
            <a class="webai-nav-item {{ $isActive ? 'is-active' : '' }}" href="{{ $url }}">
                <span class="webai-nav-icon">
                    @switch($item['icon'])
                        @case('home')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z"/></svg>
                            @break
                        @case('image')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v14H5z"/><path d="m7 16 4-4 3 3 2-2 3 3"/><circle cx="9" cy="9" r="1.5"/></svg>
                            @break
                        @case('video')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h11v10H4z"/><path d="m15 11 5-3v8l-5-3z"/></svg>
                            @break
                        @case('scissors')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 8-8 8"/><path d="m6 8 12 12"/><circle cx="5" cy="7" r="2"/><circle cx="5" cy="17" r="2"/></svg>
                            @break
                        @case('mic')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V7a3 3 0 0 1 3-3Z"/><path d="M6 11a6 6 0 0 0 12 0"/><path d="M12 17v4"/></svg>
                            @break
                        @default
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v10H4z"/><path d="M4 10h16"/></svg>
                    @endswitch
                </span>
                <span>{{ $item['title'] }}</span>

                @if (! empty($item['chevron']))
                    <span class="webai-nav-chevron">⌄</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>
