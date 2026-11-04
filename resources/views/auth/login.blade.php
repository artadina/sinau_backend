@component('layouts.auth', ['title' => 'Masuk Admin'])
    <section class="glass-card rounded-3xl p-7 sm:p-9">
        <div class="mb-8 flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-maroon-800 to-maroon-600 text-xl font-bold text-white shadow-lg shadow-maroon-900/20">S</div>
            <div>
                <p class="text-xl font-bold tracking-wide text-slate-900">SINAU</p>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-maroon-800">Panel Admin</p>
            </div>
        </div>

        <div class="mb-7">
            <h1 class="text-2xl font-bold text-slate-900">Selamat datang</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Masuk untuk melanjutkan ke panel pengajar.</p>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                <div class="relative">
                    <i data-lucide="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                        class="w-full rounded-xl border border-slate-200 bg-white/80 py-3 pl-10 pr-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-maroon-700 focus:ring-4 focus:ring-maroon-800/10 @error('email') border-rose-500 @enderror"
                        placeholder="nama@contoh.id" aria-describedby="email-error">
                </div>
                @error('email')
                    <p id="email-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label for="password" class="block text-sm font-semibold text-slate-700">Kata sandi</label>
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-maroon-800 hover:text-maroon-950">Lupa kata sandi?</a>
                </div>
                <div class="relative">
                    <i data-lucide="lock-keyhole" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="w-full rounded-xl border border-slate-200 bg-white/80 py-3 pl-10 pr-12 text-sm outline-none transition focus:border-maroon-700 focus:ring-4 focus:ring-maroon-800/10 @error('password') border-rose-500 @enderror"
                        aria-describedby="password-error" placeholder="Masukkan kata sandi">
                    <button type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-maroon-800">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                    </button>
                </div>
                @error('password')
                    <p id="password-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-maroon-800 to-maroon-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-maroon-900/15 transition hover:from-maroon-900 hover:to-maroon-700 focus:outline-none focus:ring-4 focus:ring-maroon-800/20">
                Masuk ke panel
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </button>
        </form>

        <p class="mt-7 text-center text-xs leading-5 text-slate-500">Akun panel dibuat oleh pengelola sistem. Pendaftaran mandiri tidak tersedia.</p>
    </section>
@endcomponent
