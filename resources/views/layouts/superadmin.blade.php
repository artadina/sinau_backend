<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - Panel Superadmin Sinau</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden">
    <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-slate-950/50 md:hidden"></div>
    <aside id="admin-sidebar" class="glass-sidebar fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col text-white transition-transform duration-300 md:translate-x-0">
        <div class="flex items-center justify-between border-b border-white/10 px-6 py-5">
            <a href="{{ route('superadmin.admins.index') }}" class="flex items-center gap-3" aria-label="Sinau Panel Superadmin">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-maroon-600 to-rose-400 text-xl font-extrabold shadow-lg shadow-black/20">S</span>
                <span>
                    <span class="block text-lg font-bold tracking-wide">SINAU</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.16em] text-maroon-200">Panel Superadmin</span>
                </span>
            </a>
            <button id="sidebar-close" type="button" class="rounded-lg p-2 text-maroon-100 hover:bg-white/10 md:hidden" aria-label="Tutup menu">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5" aria-label="Navigasi Superadmin">
            <p class="mb-3 px-4 text-[10px] font-bold uppercase tracking-[0.16em] text-maroon-300">Kelola Akun</p>
            @foreach ([
                ['Kelola Akun Admin', route('superadmin.admins.index'), 'admins', 'shield-check'],
                ['Kelola Akun Learner', route('superadmin.learners.index'), 'learners', 'users'],
            ] as [$label, $url, $section, $icon])
                <a href="{{ $url }}" @class([
                    'group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition',
                    'bg-gradient-to-r from-maroon-600 to-maroon-700 text-white shadow-md shadow-black/15' => request()->routeIs("superadmin.{$section}.*"),
                    'text-maroon-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs("superadmin.{$section}.*"),
                ]) @if (request()->routeIs("superadmin.{$section}.*")) aria-current="page" @endif>
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0"></i>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </nav>
        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10 text-sm font-bold text-white">
                    {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-maroon-200">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-2 text-maroon-200 transition hover:bg-white/10 hover:text-white" aria-label="Keluar">
                        <i data-lucide="log-out" class="h-4 w-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>
    <div class="min-h-screen md:pl-72">
        <header class="glass-card sticky top-0 z-30 flex min-h-20 items-center justify-between gap-4 border-x-0 border-t-0 px-4 sm:px-7">
            <div class="flex min-w-0 items-center gap-3">
                <button id="sidebar-open" type="button" class="rounded-lg p-2 text-slate-600 hover:bg-maroon-50 hover:text-maroon-800 md:hidden" aria-label="Buka menu">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-slate-900 sm:text-xl">{{ $title }}</p>
                    <p class="hidden text-xs text-slate-500 sm:block">Pengelolaan akun Sinau</p>
                </div>
            </div>
            <span class="inline-flex shrink-0 items-center gap-2 rounded-full border border-maroon-100 bg-maroon-50 px-3 py-1.5 text-xs font-semibold text-maroon-800">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Superadmin
            </span>
        </header>
        <main class="relative mx-auto max-w-7xl p-4 sm:p-7 lg:p-9">
            @if (session('status'))
                <div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
