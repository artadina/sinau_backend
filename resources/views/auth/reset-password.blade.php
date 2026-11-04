@component('layouts.auth', ['title' => 'Atur Kata Sandi Baru'])
    <section class="glass-card rounded-3xl p-7 sm:p-9">
        <div class="mb-7">
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-maroon-50 text-maroon-800">
                <i data-lucide="key-round" class="h-5 w-5"></i>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Atur kata sandi baru</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Gunakan minimal 8 karakter dan konfirmasi kata sandi yang sama.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required
                    class="w-full rounded-xl border border-slate-200 bg-white/80 px-4 py-3 text-sm outline-none transition focus:border-maroon-700 focus:ring-4 focus:ring-maroon-800/10 @error('email') border-rose-500 @enderror"
                    aria-describedby="email-error">
                @error('email')
                    <p id="email-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            @foreach (['password' => 'Kata sandi baru', 'password_confirmation' => 'Konfirmasi kata sandi'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="mb-2 block text-sm font-semibold text-slate-700">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="password" required autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-200 bg-white/80 px-4 py-3 text-sm outline-none transition focus:border-maroon-700 focus:ring-4 focus:ring-maroon-800/10 @error('password') border-rose-500 @enderror"
                        aria-describedby="password-error">
                    @if ($field === 'password')
                        @error('password')
                            <p id="password-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            @endforeach
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-maroon-800 to-maroon-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-maroon-900/15 transition hover:from-maroon-900 hover:to-maroon-700">
                Simpan kata sandi
            </button>
        </form>
    </section>
@endcomponent
