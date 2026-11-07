{{-- FR-A03, FR-A04, AB-12 --}}
@component('layouts.admin', ['title' => 'Kelola Modul'])
    <section class="space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <a href="{{ route('admin.modules.index') }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-900">&larr; Kembali ke modul</a>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Unit: {{ $module->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">Atur materi dan urutan unit untuk modul ini.</p>
            </div>
            <a href="{{ route('admin.modules.units.create', $module) }}" class="inline-flex items-center justify-center rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Tambah Unit</a>
        </div>

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="glass-card overflow-hidden rounded-2xl">
            @if ($units->isEmpty())
                <div class="px-6 py-12 text-center">
                    <h2 class="text-base font-semibold text-slate-900">Belum ada unit</h2>
                    <p class="mt-1 text-sm text-slate-600">Tambahkan setidaknya satu unit sebelum menerbitkan modul.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="bg-maroon-50 text-xs uppercase tracking-wide text-maroon-900">
                            <tr>
                                <th class="px-5 py-4 font-semibold">Urutan</th>
                                <th class="px-5 py-4 font-semibold">Unit</th>
                                <th class="px-5 py-4 font-semibold">Durasi</th>
                                <th class="px-5 py-4 text-right font-semibold">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($units as $unit)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $unit->sequence_order }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $unit->title }}</p>
                                        <p class="mt-1 line-clamp-2 max-w-xl text-xs leading-5 text-slate-500">{{ $unit->learning_objective }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-700">{{ $unit->duration_label ?? 'Tidak dicantumkan' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('admin.units.quizzes.index', $unit) }}" class="rounded-lg border border-maroon-200 px-3 py-1.5 text-xs font-semibold text-maroon-800 hover:bg-maroon-50">Kuis ({{ $unit->quizzes_count }})</a>
                                            <form method="POST" action="{{ route('admin.modules.units.move', [$module, $unit, 'up']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" @disabled($loop->first) aria-label="Naikkan {{ $unit->title }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">&uarr;</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.modules.units.move', [$module, $unit, 'down']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" @disabled($loop->last) aria-label="Turunkan {{ $unit->title }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">&darr;</button>
                                            </form>
                                            <a href="{{ route('admin.units.preview', $unit) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Pratinjau</a>
                                            <a href="{{ route('admin.modules.units.edit', [$module, $unit]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Ubah</a>
                                            <form method="POST" action="{{ route('admin.modules.units.destroy', [$module, $unit]) }}" onsubmit="return confirm('Yakin ingin menghapus unit ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endcomponent
