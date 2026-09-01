@extends('layouts.app', ['title' => 'Job '.$job->id])

@section('content')
    @php
        $statusLabel = match ($job->status) {
            'uploading' => 'Đang tải lên',
            'awaiting_confirmation' => 'Chờ xác nhận',
            'submitted' => 'Đã gửi',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn tất',
            'failed' => 'Thất bại',
            'cancelled' => 'Đã hủy',
            default => $job->status,
        };
        $statusClass = match ($job->status) {
            'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'failed' => 'border-red-200 bg-red-50 text-red-800',
            'cancelled' => 'border-stone-200 bg-stone-100 text-stone-600',
            'awaiting_confirmation' => 'border-amber-200 bg-amber-50 text-amber-800',
            default => 'border-blue-200 bg-blue-50 text-blue-800',
        };
        $step = match ($job->status) {
            'uploading' => 1,
            'awaiting_confirmation' => 2,
            'submitted', 'processing' => 3,
            'completed' => 4,
            default => 0,
        };
    @endphp

    <nav class="mb-5 flex items-center gap-2 text-sm text-stone-500" aria-label="Breadcrumb">
        <a href="{{ route('motion.index') }}" class="hover:text-stone-900">Motion Control</a>
        <i data-lucide="chevron-right" class="size-4" aria-hidden="true"></i>
        <span class="font-mono text-xs text-stone-700">{{ $job->id }}</span>
    </nav>

    @if ($errors->any())
        <div class="mb-5 flex gap-3 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <i data-lucide="circle-alert" class="mt-0.5 size-5 shrink-0" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="mb-6 flex flex-col justify-between gap-4 border-b border-stone-200 pb-5 sm:flex-row sm:items-start">
        <div class="min-w-0">
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}" data-status-badge>{{ $statusLabel }}</span>
                <span class="inline-flex rounded-full border border-stone-200 bg-white px-2.5 py-1 text-xs text-stone-600">{{ $job->dry_run ? 'Dry run' : 'Live API' }}</span>
            </div>
            <h1 class="truncate text-2xl font-semibold text-stone-950">{{ $job->image_original_name }}</h1>
            <p class="mt-1 truncate text-sm text-stone-500">{{ $job->video_original_name }}</p>
        </div>
        <a href="{{ route('motion.manifest', $job) }}" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md border border-stone-300 bg-white px-3 text-sm font-medium text-stone-700 shadow-sm hover:bg-stone-50">
            <i data-lucide="file-json" class="size-4" aria-hidden="true"></i>
            Manifest
        </a>
    </div>

    <ol class="mb-6 grid grid-cols-4 overflow-hidden rounded-md border border-stone-200 bg-white" aria-label="Tiến trình">
        @foreach ([1 => 'Tải media', 2 => 'Báo giá', 3 => 'Render', 4 => 'Kết quả'] as $index => $label)
            <li class="relative flex min-h-14 items-center gap-2 border-r border-stone-200 px-3 last:border-r-0 sm:px-4 {{ $step >= $index ? 'bg-emerald-50/60' : '' }}">
                <span class="grid size-5 shrink-0 place-items-center rounded-full text-[11px] font-semibold {{ $step >= $index ? 'bg-emerald-700 text-white' : 'bg-stone-200 text-stone-500' }}">
                    @if ($step > $index)
                        <i data-lucide="check" class="size-3" aria-hidden="true"></i>
                    @else
                        {{ $index }}
                    @endif
                </span>
                <span class="hidden text-xs font-medium sm:inline {{ $step >= $index ? 'text-emerald-900' : 'text-stone-500' }}">{{ $label }}</span>
            </li>
        @endforeach
    </ol>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
        <div class="space-y-6">
            @if ($job->status === 'awaiting_confirmation')
                <section class="rounded-md border border-amber-200 bg-amber-50" aria-labelledby="quote-title">
                    <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p id="quote-title" class="text-xs font-semibold uppercase tracking-wider text-amber-800">Báo giá RoboNeo</p>
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-3xl font-semibold text-stone-950">{{ number_format($job->quoted_cost) }}</span>
                                <span class="text-sm font-medium text-stone-600">cà rốt</span>
                            </div>
                            <p class="mt-1 text-xs text-amber-900">{{ $job->duration_seconds }} giây · Standard · {{ $job->dry_run ? 'Không phát sinh credit' : 'Xác nhận sẽ tạo task trả phí' }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <form action="{{ route('motion.cancel', $job) }}" method="POST">@csrf
                                <button class="inline-flex h-10 items-center gap-2 rounded-md border border-stone-300 bg-white px-4 text-sm font-medium text-stone-700 shadow-sm hover:bg-stone-50">
                                    <i data-lucide="x" class="size-4" aria-hidden="true"></i> Hủy
                                </button>
                            </form>
                            <form action="{{ route('motion.confirm', $job) }}" method="POST" data-submit-lock>@csrf
                                <button class="inline-flex h-10 items-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-70">
                                    <i data-lucide="play" class="size-4" aria-hidden="true"></i>
                                    <span data-submit-label>Xác nhận chạy</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            @endif

            @if (in_array($job->status, ['submitted', 'processing']))
                <section class="rounded-md border border-blue-200 bg-blue-50 p-5" data-status-poller data-status-url="{{ route('motion.status', $job) }}" data-current-status="{{ $job->status }}">
                    <div class="flex items-start gap-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-md border border-blue-200 bg-white text-blue-700">
                            <i data-lucide="loader-circle" class="size-5 animate-spin" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold text-blue-950">RoboNeo đang render</h2>
                            <p class="mt-1 text-sm text-blue-800">Task <span class="font-mono text-xs">{{ $job->task_id ?? 'đang khởi tạo' }}</span></p>
                            <p class="mt-2 text-xs text-blue-700">Lần kiểm tra: <span data-poll-attempts>{{ $job->poll_attempts }}</span></p>
                        </div>
                    </div>
                </section>
            @endif

            @if ($job->status === 'completed')
                <section class="overflow-hidden rounded-md border border-emerald-200 bg-white" aria-labelledby="result-title">
                    <div class="flex items-center justify-between border-b border-emerald-100 bg-emerald-50 px-5 py-3.5">
                        <h2 id="result-title" class="flex items-center gap-2 text-sm font-semibold text-emerald-900">
                            <i data-lucide="circle-check" class="size-4" aria-hidden="true"></i> Kết quả
                        </h2>
                        <span class="text-xs text-emerald-800">{{ $job->completed_at?->format('d/m/Y H:i:s') }}</span>
                    </div>
                    @if ($job->result_url)
                        <video controls playsinline preload="metadata" class="aspect-video w-full bg-black" poster="{{ $job->result_cover_url }}">
                            <source src="{{ $job->result_url }}" type="video/mp4">
                        </video>
                        <div class="flex justify-end border-t border-stone-200 p-4">
                            <a href="{{ $job->result_url }}" download class="inline-flex h-9 items-center gap-2 rounded-md bg-emerald-700 px-3 text-sm font-semibold text-white hover:bg-emerald-800">
                                <i data-lucide="download" class="size-4" aria-hidden="true"></i> Tải video
                            </a>
                        </div>
                    @else
                        <div class="flex min-h-48 items-center justify-center p-8 text-center">
                            <div>
                                <i data-lucide="badge-check" class="mx-auto size-8 text-emerald-700" aria-hidden="true"></i>
                                <p class="mt-3 text-sm font-medium text-stone-900">Dry-run hoàn tất</p>
                                <p class="mt-1 text-xs text-stone-500">Không có request trả phí hoặc file video được tạo.</p>
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            @if ($job->status === 'failed')
                <section class="rounded-md border border-red-200 bg-red-50 p-5">
                    <div class="flex gap-3">
                        <i data-lucide="octagon-alert" class="mt-0.5 size-5 shrink-0 text-red-700" aria-hidden="true"></i>
                        <div>
                            <h2 class="text-sm font-semibold text-red-900">Job thất bại</h2>
                            <p class="mt-1 break-words text-sm text-red-800">{{ $job->error_message }}</p>
                            @if ($job->error_code)<p class="mt-2 font-mono text-xs text-red-700">{{ $job->error_code }}</p>@endif
                        </div>
                    </div>
                </section>
            @endif

            <section class="rounded-md border border-stone-200 bg-white" aria-labelledby="prompt-title">
                <div class="border-b border-stone-200 px-5 py-3.5"><h2 id="prompt-title" class="text-sm font-semibold text-stone-900">Prompt</h2></div>
                <p class="whitespace-pre-wrap p-5 text-sm leading-6 text-stone-700">{{ $job->prompt }}</p>
            </section>
        </div>

        <aside class="rounded-md border border-stone-200 bg-white" aria-labelledby="job-details-title">
            <div class="border-b border-stone-200 px-5 py-3.5"><h2 id="job-details-title" class="text-sm font-semibold text-stone-900">Chi tiết job</h2></div>
            <dl class="divide-y divide-stone-100 text-sm">
                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-stone-500">Model</dt><dd class="font-medium text-stone-900">Kling 2.6 Motion</dd></div>
                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-stone-500">Quality</dt><dd class="font-medium text-stone-900">Standard / 720p</dd></div>
                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-stone-500">Tài khoản</dt><dd class="truncate font-medium text-stone-900">{{ $job->roboneoAccount?->label ?? ($job->dry_run ? 'Dry run' : 'Tài khoản cũ') }}</dd></div>
                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-stone-500">Thời lượng</dt><dd class="font-medium text-stone-900">{{ $job->duration_seconds }} giây</dd></div>
                <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-stone-500">Chi phí</dt><dd class="font-medium text-stone-900">{{ $job->quoted_cost !== null ? number_format($job->quoted_cost).' cà rốt' : '—' }}</dd></div>
                <div class="px-5 py-3"><dt class="text-stone-500">Ảnh</dt><dd class="mt-1 truncate font-medium text-stone-900" title="{{ $job->image_original_name }}">{{ $job->image_original_name }}</dd></div>
                <div class="px-5 py-3"><dt class="text-stone-500">Video</dt><dd class="mt-1 truncate font-medium text-stone-900" title="{{ $job->video_original_name }}">{{ $job->video_original_name }}</dd></div>
                <div class="px-5 py-3"><dt class="text-stone-500">Room ID</dt><dd class="mt-1 break-all font-mono text-xs text-stone-700">{{ $job->room_id ?? '—' }}</dd></div>
                <div class="px-5 py-3"><dt class="text-stone-500">Task ID</dt><dd class="mt-1 break-all font-mono text-xs text-stone-700">{{ $job->task_id ?? '—' }}</dd></div>
            </dl>
        </aside>
    </div>
@endsection
