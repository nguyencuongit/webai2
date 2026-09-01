<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Motion Control' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex min-h-16 max-w-screen-2xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('motion.index') }}" class="flex min-w-0 items-center gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-md bg-emerald-700 text-white shadow-sm">
                    <i data-lucide="scan-line" class="size-5" aria-hidden="true"></i>
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-stone-950">RoboNeo Motion Desk</span>
                    <span class="block truncate text-xs text-stone-500">Kling 2.6 / Standard</span>
                </span>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('roboneo-accounts.index') }}" class="inline-flex h-9 items-center gap-2 rounded-md px-2.5 text-sm font-medium {{ request()->routeIs('roboneo-accounts.*') ? 'bg-stone-100 text-stone-950' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-950' }}">
                    <i data-lucide="key-round" class="size-4" aria-hidden="true"></i>
                    <span class="hidden md:inline">Tài khoản</span>
                </a>
                <span class="hidden text-xs text-stone-500 sm:inline">Giới hạn {{ number_format(config('roboneo.motion.max_quote_cost')) }} cà rốt</span>
                <span class="inline-flex h-7 items-center gap-1.5 rounded-full border px-2.5 text-xs font-medium {{ config('roboneo.live_enabled') ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                    <span class="size-1.5 rounded-full {{ config('roboneo.live_enabled') ? 'bg-emerald-600' : 'bg-amber-500' }}"></span>
                    {{ config('roboneo.live_enabled') ? 'Live API' : 'Dry run' }}
                </span>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @yield('content')
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto flex max-w-screen-2xl flex-wrap items-center justify-between gap-2 px-4 py-4 text-xs text-stone-500 sm:px-6 lg:px-8">
            <span>Direct backend integration · Không sử dụng RoboNeo CLI</span>
            <a href="/up" class="inline-flex items-center gap-1.5 hover:text-stone-900">
                <i data-lucide="activity" class="size-3.5" aria-hidden="true"></i>
                System health
            </a>
        </div>
    </footer>
</body>
</html>
