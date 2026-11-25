{{-- FR-A02, PS-30 --}}
@component('layouts.admin', ['title' => 'Kelola Modul'])
    <section class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Kelola Modul</h1>
                <p class="mt-1 text-sm text-slate-600">Buat dan kelola modul milik Anda melalui alur review.</p>
            </div>
            <a href="{{ route('admin.modules.create') }}" class="inline-flex items-center justify-center rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-maroon-900">
                Tambah Modul
            </a>
        </div>

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="glass-card overflow-hidden rounded-2xl">
            @if ($modules->isEmpty())
                <div class="px-6 py-12 text-center">
                    <h2 class="text-base font-semibold text-slate-900">Belum ada modul</h2>
                    <p class="mt-1 text-sm text-slate-600">Tambahkan modul pertama untuk mulai menyusun materi.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-maroon-50 text-xs uppercase tracking-wide text-maroon-900">
                            <tr>
                                <th class="px-5 py-4 font-semibold">Modul</th>
                                <th class="px-5 py-4 font-semibold">Unit</th>
                                <th class="px-5 py-4 font-semibold">Status</th>
                                <th class="px-5 py-4 font-semibold">Pemilik</th>
                                <th class="px-5 py-4 text-right font-semibold">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($modules as $module)
                                <tr class="align-top">
                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-3">
                                            @if ($module->cover_image)
                                                <img src="{{ asset('storage/'.$module->cover_image) }}" alt="Sampul {{ $module->title }}" class="h-12 w-16 rounded-lg object-cover">
                                            @else
                                                <div class="flex h-12 w-16 items-center justify-center rounded-lg bg-maroon-50 text-xs font-bold text-maroon-800">SINAU</div>
                                            @endif
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ $module->title }}</p>
                                                <p class="mt-1 line-clamp-2 max-w-xl text-xs leading-5 text-slate-500">{{ $module->description }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">{{ $module->units_count }} unit</td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-emerald-50 text-emerald-800' => $module->status === 'published',
                                            'bg-amber-50 text-amber-800' => in_array($module->status, ['draft', 'revision_required'], true),
                                            'bg-sky-50 text-sky-800' => in_array($module->status, ['submitted', 'approved'], true),
                                            'bg-slate-100 text-slate-700' => $module->status === 'archived',
                                        ])>{{ [
                                            'draft' => 'Draft',
                                            'submitted' => 'Menunggu review',
                                            'revision_required' => 'Perlu revisi',
                                            'approved' => 'Disetujui (terjadwal)',
                                            'published' => 'Terbit',
                                            'archived' => 'Diarsipkan',
                                        ][$module->status] ?? $module->status }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">{{ $module->creator?->display_name ?? 'Tidak diketahui' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            @if ($module->created_by === auth()->id())
                                                <a href="{{ route('admin.modules.show', $module) }}" class="rounded-lg border border-maroon-200 px-3 py-1.5 text-xs font-semibold text-maroon-800 hover:bg-maroon-50">Detail / Quality Gate</a>
                                                @if (in_array($module->status, ['draft', 'revision_required'], true))
                                                    <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" onsubmit="return confirm('Yakin ingin menghapus modul ini? Tindakan ini tidak dapat dibatalkan.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus draft</button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="text-xs text-slate-500">Hanya-baca</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($modules->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $modules->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endcomponent
