@extends('layouts.app', ['title' => 'Sửa tài khoản RoboNeo'])

@section('content')
    <nav class="mb-5 flex items-center gap-2 text-sm text-stone-500" aria-label="Breadcrumb">
        <a href="{{ route('roboneo-accounts.index') }}" class="hover:text-stone-900">Tài khoản RoboNeo</a>
        <i data-lucide="chevron-right" class="size-4" aria-hidden="true"></i>
        <span class="text-stone-700">{{ $account->label }}</span>
    </nav>

    <div class="mx-auto max-w-2xl">
        <div class="mb-6 border-b border-stone-200 pb-5">
            <h1 class="text-2xl font-semibold text-stone-950">Chỉnh sửa tài khoản</h1>
            <p class="mt-1 font-mono text-xs text-stone-500">{{ $account->uid ?? 'UID chưa xác định' }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-5 flex gap-3 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <i data-lucide="circle-alert" class="mt-0.5 size-5 shrink-0" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('roboneo-accounts.update', $account) }}" method="POST" class="space-y-5 rounded-md border border-stone-200 bg-white p-5" data-submit-lock>
            @csrf
            @method('PUT')
            <div>
                <label for="label" class="mb-1.5 block text-sm font-medium text-stone-800">Tên hiển thị</label>
                <input id="label" name="label" type="text" maxlength="100" value="{{ old('label', $account->label) }}" required class="block h-10 w-full rounded-md border border-stone-300 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
            </div>
            <div>
                <label for="access_token" class="mb-1.5 block text-sm font-medium text-stone-800">Personal Access Token mới</label>
                <input id="access_token" name="access_token" type="password" minlength="20" maxlength="4096" autocomplete="off" spellcheck="false" class="block h-10 w-full rounded-md border border-stone-300 px-3 font-mono text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" placeholder="Để trống để giữ token hiện tại">
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex items-center gap-2 text-sm text-stone-700">
                    <input type="hidden" name="is_active" value="0">
                    <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $account->is_active)) class="size-4 rounded border-stone-300 text-emerald-700 focus:ring-emerald-600">
                    Đang hoạt động
                </label>
                <label class="flex items-center gap-2 text-sm text-stone-700">
                    <input name="is_default" type="checkbox" value="1" @checked(old('is_default', $account->is_default)) class="size-4 rounded border-stone-300 text-emerald-700 focus:ring-emerald-600">
                    Dùng làm mặc định
                </label>
            </div>
            <div class="flex justify-end gap-2 border-t border-stone-200 pt-5">
                <a href="{{ route('roboneo-accounts.index') }}" class="inline-flex h-10 items-center rounded-md border border-stone-300 bg-white px-4 text-sm font-medium text-stone-700 hover:bg-stone-50">Hủy</a>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-70">
                    <i data-lucide="check" class="size-4" aria-hidden="true"></i><span data-submit-label>Lưu thay đổi</span>
                </button>
            </div>
        </form>
    </div>
@endsection
