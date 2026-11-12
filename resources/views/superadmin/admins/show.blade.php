{{-- FR-SA03, FR-SA04 --}}
@component('layouts.superadmin', ['title' => $title])
    <section class="mx-auto max-w-3xl space-y-6">
        <a href="{{ route('superadmin.admins.index') }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-950">← Kembali ke daftar Admin</a>
        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">{{ $admin->name }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ $admin->email }}</p>
                </div>
                <span class="rounded-full bg-maroon-50 px-3 py-1.5 text-xs font-semibold text-maroon-800">Admin</span>
            </div>
            <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Status kata sandi</dt><dd class="mt-1 text-sm text-slate-800">{{ $admin->is_temp_password ? 'Wajib diganti saat login pertama' : 'Sudah aktif' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Tanggal dibuat</dt><dd class="mt-1 text-sm text-slate-800">{{ $admin->created_at->format('d/m/Y') }}</dd></div>
            </dl>
            @error('role')
                <p role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>
            @enderror
            <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('superadmin.admins.edit', $admin) }}" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Ubah data</a>
                <form method="POST" action="{{ route('superadmin.admins.role', $admin) }}">
                    @csrf @method('PUT')<input type="hidden" name="role" value="learner">
                    <button class="rounded-xl border border-amber-200 px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-50">Ubah role ke Learner</button>
                </form>
            </div>
        </div>
    </section>
@endcomponent
