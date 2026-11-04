@component('layouts.auth', ['title' => 'Ganti Kata Sandi'])
    <section class="glass-card rounded-3xl p-7 sm:p-9">
        <div class="mb-7">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-maroon-50 text-maroon-800">
                <i data-lucide="shield-check" class="h-5 w-5"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Buat kata sandi baru</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Untuk melindungi akun, ganti kata sandi sementara sebelum menggunakan panel.</p>
        </div>

        <form method="POST" action="{{ route('password.temporary.update') }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Kata sandi baru</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-200 bg-white/80 px-4 py-3 text-sm outline-none transition focus:border-maroon-700 focus:ring-4 focus:ring-maroon-800/10 @error('password') border-rose-500 @enderror"
                    aria-describedby="password-help password-error">
                <p id="password-help" class="mt-2 text-xs text-slate-500">Minimal 8 karakter.</p>
                @error('password')
                    <p id="password-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Konfirmasi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-200 bg-white/80 px-4 py-3 text-sm outline-none transition focus:border-maroon-700 focus:ring-4 focus:ring-maroon-800/10">
            </div>
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-maroon-800 to-maroon-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-maroon-900/15 transition hover:from-maroon-900 hover:to-maroon-700">
                Simpan kata sandi
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-maroon-800">Keluar</button>
        </form>
    </section>
@endcomponent
