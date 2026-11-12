{{-- FR-SA02, FR-SA03, FR-SA04, AC-11, AB-16 --}}
@component('layouts.superadmin', ['title' => $title])
    <section class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Kelola Akun Admin</h1>
                <p class="mt-1 text-sm text-slate-600">Kelola akun pengajar. Konten yang mereka buat tetap tersedia jika akun dihapus.</p>
            </div>
            <a href="{{ route('superadmin.admins.create') }}" class="inline-flex items-center justify-center rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-maroon-900">
                Tambah Admin
            </a>
        </div>

        @error('role')
            <p role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>
        @enderror
        @if ($temporaryPassword)
            <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
                <p class="font-bold">Password sementara — tampil satu kali</p>
                <p class="mt-1 text-sm">Sampaikan langsung kepada Admin. Password ini tidak dikirim lewat email dan tidak dapat dilihat lagi setelah halaman ini dibuka.</p>
                <p class="mt-3 select-all rounded-lg border border-amber-200 bg-white px-4 py-3 font-mono text-lg">{{ $temporaryPassword }}</p>
            </div>
        @endif

        <form method="GET" action="{{ route('superadmin.admins.index') }}" class="glass-card flex flex-col gap-3 rounded-2xl p-4 sm:flex-row">
            <label for="search" class="sr-only">Cari nama atau email Admin</label>
            <input id="search" name="search" value="{{ $search }}" type="search" placeholder="Cari nama atau email" class="min-w-0 flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('search') border-rose-500 @enderror">
            <button class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Cari</button>
            @error('search')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
        </form>

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-maroon-50 text-xs uppercase tracking-wide text-maroon-900">
                        <tr><th class="px-5 py-4 font-semibold">Nama</th><th class="px-5 py-4 font-semibold">Email</th><th class="px-5 py-4 font-semibold">Status</th><th class="px-5 py-4 text-right font-semibold">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($admins as $admin)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $admin->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $admin->email }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $admin->is_temp_password ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800' }}">
                                        {{ $admin->is_temp_password ? 'Password sementara' : 'Aktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('superadmin.admins.show', $admin) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detail</a>
                                        <a href="{{ route('superadmin.admins.edit', $admin) }}" class="rounded-lg border border-maroon-200 px-3 py-1.5 text-xs font-semibold text-maroon-800 hover:bg-maroon-50">Ubah</a>
                                        <form method="POST" action="{{ route('superadmin.admins.role', $admin) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="role" value="learner">
                                            <button class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50">Jadikan Learner</button>
                                        </form>
                                        <form method="POST" action="{{ route('superadmin.admins.destroy', $admin) }}" onsubmit="return confirm('Hapus akun Admin ini? Konten yang dibuat tetap ada.');">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Belum ada akun Admin.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($admins->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $admins->links() }}</div>@endif
        </div>
    </section>
@endcomponent
