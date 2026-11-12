{{-- FR-SA05, AB-16 --}}
@component('layouts.superadmin', ['title' => $title])
    <section class="mx-auto max-w-3xl space-y-6">
        <a href="{{ route('superadmin.learners.show', $learner) }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-950">← Kembali ke detail Learner</a>
        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h1 class="text-xl font-bold text-slate-900">Ubah akun Learner</h1>
            <form method="POST" action="{{ route('superadmin.learners.update', $learner) }}" class="mt-6 space-y-5">
                @csrf @method('PUT')
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nama</label>
                    <input id="name" name="name" value="{{ old('name', $learner->name) }}" maxlength="100" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('name') border-rose-500 @enderror">
                    @error('name')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $learner->email) }}" maxlength="255" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('email') border-rose-500 @enderror">
                    @error('email')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="business_name" class="mb-2 block text-sm font-semibold text-slate-700">Nama usaha <span class="font-normal text-slate-500">(opsional)</span></label>
                    <input id="business_name" name="business_name" value="{{ old('business_name', $learner->business_name) }}" maxlength="150" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('business_name') border-rose-500 @enderror">
                    @error('business_name')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('superadmin.learners.show', $learner) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a>
                    <button class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </section>
@endcomponent
