@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $items = collect($urls ?? []);
    $perPage = (int) ($shortcode->per_page = (int) ($shortcode->per_page ?? 1));
    $page = (int) request()->get('vpage', 1); // tránh trùng page website

    $total = $items->count();
    $offset = ($page - 1) * $perPage;

    $pageItems = $items->slice($offset, $perPage)->values();
    $paginator = new LengthAwarePaginator($pageItems, $total, $perPage, $page, [
        'path' => request()->fullUrlWithoutQuery('vpage'),
        'pageName' => 'vpage',
    ]);
    $paginator->appends(request()->except('vpage'));

@endphp

<div class="row gy-4">

    @foreach ($pageItems as $url)
        @php
            // Lấy ID
            preg_match('/(?:v=|youtu\.be\/)([A-Za-z0-9_\-]+)/', $url, $m);
            $videoId = $m[1] ?? null;

            // Cache tiêu đề video theo ID (tối ưu cực mạnh)
            $title = Cache::remember("yt_title_$videoId", 3600, function () use ($url) {
                try {
                    $data = @file_get_contents("https://www.youtube.com/oembed?url=$url&format=json");
                    return $data ? json_decode($data)->title : null;
                } catch (\Exception $e) {
                    return null;
                }
            });
        @endphp

        @if ($videoId)
            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
                <div class="video-card">
                    <div class="video-thumb">
                        <iframe src="https://www.youtube.com/embed/{{ $videoId }}" loading="lazy"
                            allowfullscreen></iframe>
                    </div>
                    <div class="video-title">
                        {{ $title ?? 'Video hướng dẫn cắm hoa' }}
                    </div>
                </div>
            </div>
        @endif
    @endforeach

</div>


{{-- PAGINATION --}}
@if ($paginator->lastPage() > 1)
    <div class="video-pagination">
        <ul class="pagination-list">

            {{-- Previous --}}
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span>‹</span>
                @else
                    <a href="{{ $paginator->url($paginator->currentPage() - 1) }}">‹</a>
                @endif
            </li>

            {{-- Numeric pages --}}
            @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                <li class="page-item {{ $i == $paginator->currentPage() ? 'active' : '' }}">
                    @if ($i == $paginator->currentPage())
                        <span>{{ $i }}</span>
                    @else
                        <a href="{{ $paginator->url($i) }}">{{ $i }}</a>
                    @endif
                </li>
            @endfor

            {{-- Next --}}
            <li class="page-item {{ $paginator->currentPage() == $paginator->lastPage() ? 'disabled' : '' }}">
                @if ($paginator->currentPage() == $paginator->lastPage())
                    <span>›</span>
                @else
                    <a href="{{ $paginator->url($paginator->currentPage() + 1) }}">›</a>
                @endif
            </li>
        </ul>
    </div>
@endif
<style>

.video-grid {
    row-gap: 32px;
}


.video-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #f0f0f0;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.05);
    transition: all .25s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.video-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

/* ================================
   THUMBNAIL
==================================*/
.video-thumb {
    position: relative;
    overflow: hidden;
}

.video-thumb iframe {
    width: 100%;
    height: 220px;
    border: none;
    display: block;
}

.video-thumb::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.05);
    opacity: 0;
    transition: .3s;
    pointer-events: none; /* THÊM DÒNG NÀY */
}

.video-card:hover .video-thumb::after {
    opacity: 1;
}

/* ================================
   TITLE
==================================*/
.video-title {
    padding: 14px 16px 20px;
    font-size: 15px;
    font-weight: 600;
    color: #333;
    line-height: 1.45;
    flex-grow: 1;

    /* Cắt dòng */
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;

    transition: color .2s;
}

.video-card:hover .video-title {
    color: #cc6d3d; /* màu cam thương hiệu */
}

/* ================================
   PAGINATION
==================================*/
.video-pagination {
    margin-top: 32px;
    text-align: center;
    margin-bottom:30px;
}

.pagination-list {
    list-style: none;
    padding: 0;
    display: inline-flex;
    gap: 6px;
}

.pagination-list .page-item {
    display: inline-flex;
}

.pagination-list a,
.pagination-list span {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-width: 36px;
    height: 36px;
    border-radius: 8px;
    padding: 0 10px;

    font-size: 14px;
    font-weight: 600;
    color: #555;

    border: 1px solid #e2e2e2;
    background: #fff;
    transition: all .25s ease;
}

/* Active Page */
.pagination-list .page-item.active span {
    background: #f28c3d;
    color: #fff !important;
    border-color: #f28c3d;
    font-weight: 700;
}

/* Hover */
.pagination-list a:hover {
    background: #f4f4f4;
    border-color: #ccc;
}

/* Disabled */
.pagination-list .page-item.disabled span {
    opacity: 0.5;
    cursor: not-allowed;
}

.pb-80 {
    padding-bottom: 0px !important;
}

@media (max-width: 768px) {
    .video-thumb iframe {
        height: 180px;
    }
    .video-title {
        -webkit-line-clamp: 3;
    }
}
</style>
