{{-- FR-SA02, FR-SA03, AB-16 --}}
@component('layouts.superadmin', ['title' => $title])
    <section class="mx-auto max-w-3xl space-y-6">
        <a href="{{ $admin ? route('superadmin.admins.show', $admin) : route('superadmin.admins.index') }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-950">← Kembali</a>
        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h1 class="text-xl font-bold text-slate-900">{{ $admin ? 'Ubah akun Admin' : 'Tambah Admin baru' }}</h1>
            @unless ($admin)
                <p class="mt-1 text-sm text-slate-600">Sistem membuat password sementara secara otomatis setelah akun dibuat.</p>
            @endunless
            <form method="POST" action="{{ $admin ? route('superadmin.admins.update', $admin) : route('superadmin.admins.store') }}" class="mt-6 space-y-5">
                @csrf
                @if ($admin) @method('PUT') @endif
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nama</label>
                    <input id="name" name="name" value="{{ old('name', $admin?->name) }}" maxlength="100" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('name') border-rose-500 @enderror">
                    @error('name')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $admin?->email) }}" maxlength="255" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('email') border-rose-500 @enderror">
                    @error('email')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                @if ($admin)
                    <p class="rounded-xl bg-maroon-50 p-4 text-sm text-maroon-900">Perubahan data tidak mengubah role atau kata sandi.</p>
                @endif
                <div class="flex justify-end gap-3">
                    <a href="{{ $admin ? route('superadmin.admins.show', $admin) : route('superadmin.admins.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a>
                    <button class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">{{ $admin ? 'Simpan perubahan' : 'Buat akun Admin' }}</button>
                </div>
            </form>
        </div>
    </section>
@endcomponent
