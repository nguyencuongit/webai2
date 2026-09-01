@extends('layouts.app', ['title' => 'Motion Control'])

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 border-b border-stone-200 pb-5 sm:flex-row sm:items-end">
        <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-emerald-700">RoboNeo workflow</p>
            <h1 class="text-2xl font-semibold text-stone-950">Motion Control</h1>
            <p class="mt-1 text-sm text-stone-600">Ảnh nhân vật + video chuyển động · Kling 2.6 Standard 720p</p>
        </div>
        <div class="flex divide-x divide-stone-200 overflow-hidden rounded-md border border-stone-200 bg-white">
            <div class="min-w-20 px-4 py-2 text-center">
                <span class="block text-base font-semibold text-stone-950">{{ $stats['total'] }}</span>
                <span class="block text-xs text-stone-500">Tổng job</span>
            </div>
            <div class="min-w-20 px-4 py-2 text-center">
                <span class="block text-base font-semibold text-amber-700">{{ $stats['active'] }}</span>
                <span class="block text-xs text-stone-500">Đang chạy</span>
            </div>
            <div class="min-w-20 px-4 py-2 text-center">
                <span class="block text-base font-semibold text-emerald-700">{{ $stats['completed'] }}</span>
                <span class="block text-xs text-stone-500">Hoàn tất</span>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-5 flex gap-3 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <i data-lucide="circle-alert" class="mt-0.5 size-5 shrink-0" aria-hidden="true"></i>
            <div>
                <p class="font-semibold">Dữ liệu chưa hợp lệ</p>
                <ul class="mt-1 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(360px,0.8fr)_minmax(600px,1.2fr)]">
        <section class="rounded-md border border-stone-200 bg-white" aria-labelledby="create-job-title">
            <div class="border-b border-stone-200 px-5 py-4">
                <h2 id="create-job-title" class="text-sm font-semibold text-stone-950">Tạo báo giá mới</h2>
            </div>

            <form action="{{ route('motion.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-5" data-submit-lock>
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="file-picker group relative flex min-h-32 cursor-pointer flex-col justify-between rounded-md border border-dashed border-stone-300 bg-stone-50 p-4 transition hover:border-emerald-600 hover:bg-emerald-50/30">
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only" required data-file-input>
                        <span class="grid size-9 place-items-center rounded-md border border-stone-200 bg-white text-emerald-700 shadow-sm">
                            <i data-lucide="image-plus" class="size-5" aria-hidden="true"></i>
                        </span>
                        <span class="mt-4 min-w-0">
                            <span class="block text-sm font-medium text-stone-900">Ảnh nhân vật</span>
                            <span class="mt-0.5 block truncate text-xs text-stone-500" data-file-label>JPG, PNG, WEBP · 10 MB</span>
                        </span>
                    </label>

                    <label class="file-picker group relative flex min-h-32 cursor-pointer flex-col justify-between rounded-md border border-dashed border-stone-300 bg-stone-50 p-4 transition hover:border-orange-600 hover:bg-orange-50/40">
                        <input type="file" name="video" accept="video/mp4,video/quicktime,video/webm" class="sr-only" required data-file-input>
                        <span class="grid size-9 place-items-center rounded-md border border-stone-200 bg-white text-orange-700 shadow-sm">
                            <i data-lucide="video" class="size-5" aria-hidden="true"></i>
                        </span>
                        <span class="mt-4 min-w-0">
                            <span class="block text-sm font-medium text-stone-900">Video tham chiếu</span>
                            <span class="mt-0.5 block truncate text-xs text-stone-500" data-file-label>MP4, MOV, WEBM · 100 MB</span>
                        </span>
                    </label>
                </div>

                <div>
                    <label for="prompt" class="mb-1.5 block text-sm font-medium text-stone-800">Prompt</label>
                    <textarea id="prompt" name="prompt" rows="5" maxlength="2000" required class="block w-full resize-y rounded-md border border-stone-300 bg-white px-3 py-2.5 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" placeholder="Smooth natural motion, preserve facial identity and outfit...">{{ old('prompt', 'Smooth natural motion, preserve facial identity, consistent outfit and lighting, stable framing, high temporal coherence.') }}</textarea>
                </div>

                @if (config('roboneo.live_enabled'))
                    @if ($accounts->isNotEmpty())
                        @php
                            $selectedAccountId = old('roboneo_account_id', $accounts->firstWhere('is_default', true)?->id ?? $accounts->first()?->id);
                        @endphp
                        <div>
                            <label for="roboneo_account_id" class="mb-1.5 block text-sm font-medium text-stone-800">Tài khoản RoboNeo</label>
                            <select id="roboneo_account_id" name="roboneo_account_id" required class="block h-10 w-full rounded-md border border-stone-300 bg-white px-3 text-sm text-stone-900 outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected($selectedAccountId === $account->id)>{{ $account->label }}{{ $account->is_default ? ' (mặc định)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="flex items-start gap-3 rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-800">
                            <i data-lucide="key-round" class="mt-0.5 size-4 shrink-0" aria-hidden="true"></i>
                            <span>Chưa có tài khoản đang hoạt động. <a href="{{ route('roboneo-accounts.index') }}" class="font-semibold underline">Thêm Personal Access Token</a>.</span>
                        </div>
                    @endif
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <fieldset>
                        <legend class="mb-1.5 text-sm font-medium text-stone-800">Chất lượng</legend>
                        <div class="grid h-10 grid-cols-2 rounded-md border border-stone-300 bg-stone-100 p-0.5">
                            <label class="flex cursor-pointer items-center justify-center gap-1.5 rounded-sm bg-white text-xs font-semibold text-emerald-800 shadow-sm">
                                <input type="radio" name="quality" value="std" checked class="sr-only">
                                <i data-lucide="gauge" class="size-3.5" aria-hidden="true"></i>
                                Standard
                            </label>
                            <span class="flex items-center justify-center gap-1 text-xs text-stone-400" aria-disabled="true">
                                Pro
                                <i data-lucide="lock-keyhole" class="size-3" aria-hidden="true"></i>
                            </span>
                        </div>
                    </fieldset>

                    <div>
                        <label for="duration_seconds" class="mb-1.5 block text-sm font-medium text-stone-800">Thời lượng</label>
                        <div class="relative">
                            <input id="duration_seconds" name="duration_seconds" type="number" min="3" max="30" step="1" value="{{ old('duration_seconds', 10) }}" required class="block h-10 w-full rounded-md border border-stone-300 bg-white px-3 pr-12 text-sm font-medium outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-stone-500">giây</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-900">
                    <i data-lucide="shield-check" class="mt-0.5 size-4 shrink-0" aria-hidden="true"></i>
                    <span>Chỉ tạo task sau khi bạn xem và xác nhận báo giá.</span>
                </div>

                <button type="submit" @disabled(config('roboneo.live_enabled') && $accounts->isEmpty()) class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <i data-lucide="calculator" class="size-4" aria-hidden="true"></i>
                    <span data-submit-label>Tải lên và báo giá</span>
                </button>
            </form>
        </section>

        <section class="overflow-hidden rounded-md border border-stone-200 bg-white" aria-labelledby="recent-jobs-title">
            <div class="flex items-center justify-between border-b border-stone-200 px-5 py-4">
                <h2 id="recent-jobs-title" class="text-sm font-semibold text-stone-950">Job gần đây</h2>
                <span class="text-xs text-stone-500">{{ $jobs->count() }} hiển thị</span>
            </div>

            @if ($jobs->isEmpty())
                <div class="grid min-h-64 place-items-center px-5 py-12 text-center">
                    <div>
                        <span class="mx-auto grid size-11 place-items-center rounded-md border border-stone-200 bg-stone-50 text-stone-500">
                            <i data-lucide="list-video" class="size-5" aria-hidden="true"></i>
                        </span>
                        <p class="mt-3 text-sm font-medium text-stone-800">Chưa có job</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-50 text-xs font-medium text-stone-500">
                            <tr>
                                <th class="px-5 py-3">Job</th>
                                <th class="px-4 py-3">Trạng thái</th>
                                <th class="px-4 py-3">Thời lượng</th>
                                <th class="px-4 py-3 text-right">Chi phí</th>
                                <th class="w-12 px-3 py-3"><span class="sr-only">Mở</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($jobs as $job)
                                @php
                                    $statusClass = match ($job->status) {
                                        'completed' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                        'failed' => 'bg-red-50 text-red-800 border-red-200',
                                        'cancelled' => 'bg-stone-100 text-stone-600 border-stone-200',
                                        'awaiting_confirmation' => 'bg-amber-50 text-amber-800 border-amber-200',
                                        default => 'bg-blue-50 text-blue-800 border-blue-200',
                                    };
                                    $statusLabel = match ($job->status) {
                                        'uploading' => 'Đang tải',
                                        'awaiting_confirmation' => 'Chờ xác nhận',
                                        'submitted' => 'Đã gửi',
                                        'processing' => 'Đang xử lý',
                                        'completed' => 'Hoàn tất',
                                        'failed' => 'Thất bại',
                                        'cancelled' => 'Đã hủy',
                                        default => $job->status,
                                    };
                                @endphp
                                <tr class="group hover:bg-stone-50">
                                    <td class="max-w-64 px-5 py-3.5">
                                        <a href="{{ route('motion.show', $job) }}" class="block truncate font-medium text-stone-900 group-hover:text-emerald-800">{{ $job->image_original_name }}</a>
                                        <span class="mt-0.5 block truncate text-xs text-stone-500">{{ $job->created_at->format('d/m/Y H:i') }} · {{ $job->dry_run ? 'Dry run' : ($job->roboneoAccount?->label ?? 'Live') }}</span>
                                    </td>
                                    <td class="px-4 py-3.5"><span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-stone-600">{{ $job->duration_seconds }} giây</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-right font-medium text-stone-800">{{ $job->quoted_cost !== null ? number_format($job->quoted_cost) : '—' }}</td>
                                    <td class="px-3 py-3.5">
                                        <a href="{{ route('motion.show', $job) }}" title="Mở job" class="grid size-8 place-items-center rounded-md text-stone-500 transition hover:bg-white hover:text-stone-900 hover:shadow-sm">
                                            <i data-lucide="chevron-right" class="size-4" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
