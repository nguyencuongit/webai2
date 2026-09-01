@extends('layouts.app', ['title' => 'Tài khoản RoboNeo'])

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 border-b border-stone-200 pb-5 sm:flex-row sm:items-end">
        <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-emerald-700">RoboNeo credentials</p>
            <h1 class="text-2xl font-semibold text-stone-950">Tài khoản RoboNeo</h1>
            <p class="mt-1 text-sm text-stone-600">Personal Access Token được mã hóa trước khi lưu.</p>
        </div>
        <span class="text-sm text-stone-500">{{ $accounts->where('is_active', true)->count() }} đang hoạt động</span>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">
            <i data-lucide="circle-check" class="size-5 shrink-0" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 flex gap-3 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <i data-lucide="circle-alert" class="mt-0.5 size-5 shrink-0" aria-hidden="true"></i>
            <ul class="space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(320px,0.65fr)_minmax(640px,1.35fr)]">
        <section class="rounded-md border border-stone-200 bg-white" aria-labelledby="add-account-title">
            <div class="border-b border-stone-200 px-5 py-4">
                <h2 id="add-account-title" class="text-sm font-semibold text-stone-950">Thêm tài khoản</h2>
            </div>
            <form action="{{ route('roboneo-accounts.store') }}" method="POST" class="space-y-4 p-5" data-submit-lock>
                @csrf
                <div>
                    <label for="label" class="mb-1.5 block text-sm font-medium text-stone-800">Tên hiển thị</label>
                    <input id="label" name="label" type="text" maxlength="100" value="{{ old('label') }}" required class="block h-10 w-full rounded-md border border-stone-300 bg-white px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" placeholder="Tài khoản chính">
                </div>
                <div>
                    <label for="access_token" class="mb-1.5 block text-sm font-medium text-stone-800">Personal Access Token</label>
                    <input id="access_token" name="access_token" type="password" minlength="20" maxlength="4096" required autocomplete="off" spellcheck="false" class="block h-10 w-full rounded-md border border-stone-300 bg-white px-3 font-mono text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" placeholder="Nhập token từ CLI Settings">
                </div>
                <label class="flex items-center gap-2 text-sm text-stone-700">
                    <input name="is_default" type="checkbox" value="1" @checked(old('is_default')) class="size-4 rounded border-stone-300 text-emerald-700 focus:ring-emerald-600">
                    Dùng làm mặc định
                </label>
                <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-70">
                    <i data-lucide="plus" class="size-4" aria-hidden="true"></i>
                    <span data-submit-label>Xác minh và lưu</span>
                </button>
            </form>
        </section>

        <section class="overflow-hidden rounded-md border border-stone-200 bg-white" aria-labelledby="account-list-title">
            <div class="flex items-center justify-between border-b border-stone-200 px-5 py-4">
                <h2 id="account-list-title" class="text-sm font-semibold text-stone-950">Danh sách tài khoản</h2>
                <span class="text-xs text-stone-500">{{ $accounts->count() }} tài khoản</span>
            </div>

            @if ($accounts->isEmpty())
                <div class="grid min-h-56 place-items-center p-8 text-center">
                    <div>
                        <i data-lucide="key-round" class="mx-auto size-7 text-stone-400" aria-hidden="true"></i>
                        <p class="mt-3 text-sm font-medium text-stone-800">Chưa có tài khoản</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-left text-sm">
                        <thead class="bg-stone-50 text-xs font-medium text-stone-500">
                            <tr>
                                <th class="px-5 py-3">Tài khoản</th>
                                <th class="px-4 py-3">Token</th>
                                <th class="px-4 py-3">Job</th>
                                <th class="px-4 py-3">Xác minh</th>
                                <th class="px-4 py-3 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($accounts as $account)
                                <tr class="hover:bg-stone-50">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-stone-900">{{ $account->label }}</span>
                                            @if ($account->is_default)<span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-800">Mặc định</span>@endif
                                        </div>
                                        <span class="mt-0.5 block font-mono text-xs {{ $account->is_active ? 'text-emerald-700' : 'text-stone-400' }}">{{ $account->is_active ? ($account->uid ?? 'UID chưa có') : 'Đã tắt' }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-xs text-stone-500">••••••••••••</td>
                                    <td class="px-4 py-3.5 text-stone-600">{{ $account->motion_jobs_count }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-xs text-stone-500">{{ $account->last_verified_at?->format('d/m/Y H:i') ?? 'Chưa xác minh' }}</td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex justify-end gap-1">
                                            @unless ($account->is_default)
                                                <form action="{{ route('roboneo-accounts.default', $account) }}" method="POST">@csrf
                                                    <button title="Đặt mặc định" class="grid size-8 place-items-center rounded-md text-stone-500 hover:bg-white hover:text-amber-700 hover:shadow-sm"><i data-lucide="star" class="size-4" aria-hidden="true"></i></button>
                                                </form>
                                            @endunless
                                            <form action="{{ route('roboneo-accounts.verify', $account) }}" method="POST" data-submit-lock>@csrf
                                                <button title="Xác minh token" class="grid size-8 place-items-center rounded-md text-stone-500 hover:bg-white hover:text-emerald-700 hover:shadow-sm"><i data-lucide="refresh-cw" class="size-4" aria-hidden="true"></i></button>
                                            </form>
                                            <a href="{{ route('roboneo-accounts.edit', $account) }}" title="Chỉnh sửa" class="grid size-8 place-items-center rounded-md text-stone-500 hover:bg-white hover:text-stone-900 hover:shadow-sm"><i data-lucide="pencil" class="size-4" aria-hidden="true"></i></a>
                                            <form action="{{ route('roboneo-accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Xóa tài khoản này?')">@csrf @method('DELETE')
                                                <button title="Xóa" @disabled($account->motion_jobs_count > 0) class="grid size-8 place-items-center rounded-md text-stone-500 hover:bg-white hover:text-red-700 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-30"><i data-lucide="trash-2" class="size-4" aria-hidden="true"></i></button>
                                            </form>
                                        </div>
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
