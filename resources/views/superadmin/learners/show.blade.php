{{-- FR-SA05, FR-SA06, AB-09: account details only; no test or score data is queried. --}}
@component('layouts.superadmin', ['title' => $title])
    <section class="mx-auto max-w-3xl space-y-6">
        <a href="{{ route('superadmin.learners.index') }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-950">← Kembali ke daftar Learner</a>
        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">{{ $learner->name }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ $learner->email }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">Learner</span>
            </div>
            <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Nama usaha</dt><dd class="mt-1 text-sm text-slate-800">{{ $learner->business_name ?: '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Tanggal dibuat</dt><dd class="mt-1 text-sm text-slate-800">{{ $learner->created_at->format('d/m/Y') }}</dd></div>
            </dl>
            @error('role')
                <p role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>
            @enderror
            <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('superadmin.learners.edit', $learner) }}" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Ubah data</a>
                <form method="POST" action="{{ route('superadmin.learners.role', $learner) }}">
                    @csrf @method('PUT')<input type="hidden" name="role" value="admin">
                    <button class="rounded-xl border border-amber-200 px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-50">Ubah role ke Admin</button>
                </form>
            </div>
        </div>
    </section>
@endcomponent
