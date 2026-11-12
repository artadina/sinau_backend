{{-- FR-SA05, FR-SA06, AB-09, AB-16 --}}
@component('layouts.superadmin', ['title' => $title])
    <section class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Kelola Akun Learner</h1>
            <p class="mt-1 text-sm text-slate-600">Daftar ini hanya memuat data akun, bukan skor atau riwayat tes.</p>
        </div>
        @error('role')
            <p role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</p>
        @enderror
        <form method="GET" action="{{ route('superadmin.learners.index') }}" class="glass-card flex flex-col gap-3 rounded-2xl p-4 sm:flex-row">
            <label for="search" class="sr-only">Cari nama atau email Learner</label>
            <input id="search" name="search" value="{{ $search }}" type="search" placeholder="Cari nama atau email" class="min-w-0 flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('search') border-rose-500 @enderror">
            <button class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Cari</button>
            @error('search')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
        </form>
        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="bg-maroon-50 text-xs uppercase tracking-wide text-maroon-900">
                        <tr><th class="px-5 py-4 font-semibold">Nama</th><th class="px-5 py-4 font-semibold">Email</th><th class="px-5 py-4 font-semibold">Nama usaha</th><th class="px-5 py-4 text-right font-semibold">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($learners as $learner)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $learner->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $learner->email }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $learner->business_name ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('superadmin.learners.show', $learner) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detail</a>
                                        <a href="{{ route('superadmin.learners.edit', $learner) }}" class="rounded-lg border border-maroon-200 px-3 py-1.5 text-xs font-semibold text-maroon-800 hover:bg-maroon-50">Ubah</a>
                                        <form method="POST" action="{{ route('superadmin.learners.role', $learner) }}">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="role" value="admin">
                                            <button class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50">Jadikan Admin</button>
                                        </form>
                                        <form method="POST" action="{{ route('superadmin.learners.destroy', $learner) }}" onsubmit="return confirm('Hapus akun Learner? Data identitas akun dan isi forum akan dianonimkan.');">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Belum ada akun Learner.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($learners->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $learners->links() }}</div>@endif
        </div>
    </section>
@endcomponent
