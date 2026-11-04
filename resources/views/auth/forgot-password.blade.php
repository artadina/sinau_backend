@component('layouts.auth', ['title' => 'Lupa Kata Sandi'])
    <section class="glass-card rounded-3xl p-7 sm:p-9">
        <a href="{{ route('login') }}" class="mb-7 inline-flex items-center gap-2 text-sm font-semibold text-maroon-800 hover:text-maroon-950">
            <i data-lucide="arrow-right" class="h-4 w-4 rotate-180"></i>
            Kembali ke masuk
        </a>
        <div class="mb-7">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-maroon-50 text-maroon-800">
                <i data-lucide="key-round" class="h-5 w-5"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Lupa kata sandi?</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Masukkan email akun Admin. Kami akan mengirim tautan untuk membuat kata sandi baru.</p>
        </div>

        @if (session('status'))
            <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-5 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                <div class="relative">
                    <i data-lucide="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus
                        class="w-full rounded-xl border border-slate-200 bg-white/80 py-3 pl-10 pr-4 text-sm outline-none transition focus:border-maroon-700 focus:ring-4 focus:ring-maroon-800/10 @error('email') border-rose-500 @enderror"
                        aria-describedby="email-error">
                </div>
                @error('email')
                    <p id="email-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-maroon-800 to-maroon-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-maroon-900/15 transition hover:from-maroon-900 hover:to-maroon-700">
                Kirim tautan reset
            </button>
        </form>
    </section>
@endcomponent
