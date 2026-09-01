@php
    $heroTitle = $homePost->title ?? 'Khai phá trí tuệ nhân tạo đa phương thức';
    $heroDescription = $homePost->excerpt ?? ($homePost ? \Illuminate\Support\Str::limit(strip_tags((string) $homePost->content), 260) : 'Trải nghiệm sức mạnh của AI tạo sinh qua hình ảnh, video và âm thanh. Nền tảng trung tâm cho mọi nhu cầu sáng tạo kỹ thuật số của bạn.');
    $heroLink = $homePost->link ?? route('public.credit-packages.index');
    $heroImage = $homePost?->image ? \Botble\Media\Facades\RvMedia::getImageUrl($homePost->image) : null;
    $heroTitleParts = preg_split('/\s+/', trim($heroTitle), 2);
    $heroTitleLead = $heroTitleParts[0] ?? '';
    $heroTitleRest = $heroTitleParts[1] ?? '';
    Theme::set('pageTitle', 'Trang chủ');

    $tools = [
        [
            'title' => 'Studio ảnh',
            'description' => 'Tạo hình ảnh chất lượng cao từ văn bản, áp dụng phong cách nghệ thuật đa dạng và chỉnh sửa chi tiết với độ chính xác cao.',
            'route' => 'public.studio-image',
            'icon' => 'image',
            'cost' => 'Từ 1 credit',
        ],
        [
            'title' => 'Phòng lab video',
            'description' => 'Biến hình ảnh tĩnh thành video động, tạo kịch bản tự động và áp dụng hiệu ứng thị giác điện ảnh phức tạp.',
            'route' => 'public.video-lab',
            'icon' => 'video',
            'cost' => 'Từ 1 credit',
        ],
        [
            'title' => 'Chuyển đổi Giọng nói',
            'description' => 'Nhân bản giọng nói, chuyển đổi văn bản thành giọng đọc tự nhiên (TTS) hỗ trợ đa ngôn ngữ với cảm xúc chân thực.',
            'url' => '#',
            'icon' => 'mic',
            'cost' => 'Từ 1 credit',
        ],
    ];
@endphp

<section class="webai-home-hero">
    <div class="webai-hero-copy">
        @if ($homePost)
            <h1><span>{{ $heroTitleLead }}</span>@if ($heroTitleRest) {{ ' ' . $heroTitleRest }}@endif</h1>
            <p>{{ $heroDescription }}</p>
            <a class="webai-primary-btn" href="{{ $heroLink }}">Khám phá ngay</a>
        @else
        <div class="webai-status"><span></span> Hệ thống đang hoạt động</div>

        <h1>Khai phá trí tuệ nhân tạo <span>đa phương thức</span></h1>

        <p>
            Trải nghiệm sức mạnh của AI tạo sinh qua hình ảnh, video và âm thanh.
            Nền tảng trung tâm cho mọi nhu cầu sáng tạo kỹ thuật số của bạn.
        </p>

        <a class="webai-primary-btn" href="#">Bảng giá &amp; Nạp credit</a>
        @endif
    </div>

    <div class="webai-hero-visual" aria-hidden="true">
        @if ($heroImage)
            <img src="{{ $heroImage }}" alt="">
        @else
        <svg viewBox="0 0 360 300" role="img">
            <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M222 14 283 28 326 70 337 132 318 190 276 224 217 232 159 213 123 169 116 108 151 51Z"/>
                <path d="M150 52 205 21 260 61 255 126 217 166 158 153 121 108"/>
                <path d="M205 21 222 86 255 126 318 190"/>
                <path d="M260 61 326 70 255 126"/>
                <path d="M151 51 222 86 158 153 123 169"/>
                <path d="M222 86 217 166 276 224"/>
                <path d="M158 153 217 166 217 232"/>
                <path d="M131 121 78 143 125 161"/>
                <path d="M78 143 38 171 93 182 123 169"/>
                <path d="M93 182 105 242 151 250 217 232"/>
                <path d="M105 242 140 282 246 284 217 232"/>
                <path d="M151 250 165 285"/>
                <path d="M246 284 268 244 276 224"/>
                <path d="M43 173 28 205 105 242"/>
                <path d="M116 108 77 143 121 108"/>
                <path d="M123 169 158 153 105 242"/>
                <path d="M217 166 268 244"/>
                <path d="M283 28 260 61 222 14"/>
                <path d="M326 70 337 132 255 126"/>
                <path d="M337 132 318 190 276 224"/>
                <path d="M38 171 28 205 93 182"/>
                <path d="M151 51 205 21 222 86"/>
                <path d="M121 108 222 86 158 153"/>
            </g>
            <g fill="currentColor">
                @foreach ([[222,14],[283,28],[326,70],[337,132],[318,190],[276,224],[217,232],[159,213],[123,169],[116,108],[151,51],[205,21],[260,61],[255,126],[217,166],[158,153],[78,143],[38,171],[93,182],[105,242],[151,250],[140,282],[246,284],[28,205]] as [$x, $y])
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="2.6"/>
                @endforeach
            </g>
        </svg>
        @endif
    </div>
</section>

@if (false && ($homePosts ?? collect())->isNotEmpty())
<section class="webai-content-posts" aria-labelledby="webai-content-posts-title">
    <div class="webai-content-posts__heading">
        <span>Ưu đãi mới</span>
        <h2 id="webai-content-posts-title">Khám phá cùng WebAI</h2>
    </div>

    <div class="webai-content-posts__grid">
        @foreach ($homePosts as $post)
            @php
                $imageUrl = $post->image ? \Botble\Media\Facades\RvMedia::getImageUrl($post->image) : null;
                $description = $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->content), 160);
            @endphp
            <article class="webai-content-post">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $post->title }}">
                @endif
                <div class="webai-content-post__body">
                    <h3>{{ $post->title }}</h3>
                    @if ($description)
                        <p>{{ $description }}</p>
                    @endif
                    @if ($post->link)
                        <a href="{{ $post->link }}">Xem thêm <span>→</span></a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

@if ($introModalPost ?? null)
    {!! Theme::partial('event-modal', ['post' => $introModalPost]) !!}
@endif

<section class="webai-tools-section" aria-labelledby="creative-tools-title">
    <h2 id="creative-tools-title">Công cụ sáng tạo:</h2>

    <div class="webai-tool-grid">
        @foreach (array_slice($tools, 0, 2) as $tool)
            @php
                $url = isset($tool['route']) && \Illuminate\Support\Facades\Route::has($tool['route']) ? route($tool['route']) : ($tool['url'] ?? '#');
            @endphp

            <article class="webai-tool-card">
                <div class="webai-tool-card-head">
                    <h3>{{ $tool['title'] }}</h3>

                    <div class="webai-tool-icon">
                        @if ($tool['icon'] === 'image')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v14H5z"/><path d="m7 16 4-4 3 3 2-2 3 3"/><circle cx="9" cy="9" r="1.5"/></svg>
                        @elseif ($tool['icon'] === 'video')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h11v10H4z"/><path d="m15 11 5-3v8l-5-3z"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a3 3 0 0 1 3 3v5a3 3 0 0 1-6 0V7a3 3 0 0 1 3-3Z"/><path d="M6 11a6 6 0 0 0 12 0"/><path d="M12 17v4"/></svg>
                        @endif
                    </div>
                </div>

                <p>{{ $tool['description'] }}</p>

                <div class="webai-tool-footer">
                    <span>⚡ {{ $tool['cost'] }}</span>
                    <a href="{{ $url }}" aria-label="{{ $tool['title'] }}">→</a>
                </div>
            </article>
        @endforeach
    </div>
</section>
