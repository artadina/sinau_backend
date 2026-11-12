{{-- FR-A08, FR-A09, AC-39 --}}
@component('layouts.admin', ['title' => $title])
    <section class="space-y-6">
        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <form method="GET" action="{{ route('admin.results.index') }}" class="grid gap-4 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label for="module_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Modul</label>
                    <select id="module_id" name="module_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100">
                        <option value="">Semua modul</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module->id }}" @selected((string) ($filters['module_id'] ?? '') === (string) $module->id)>{{ $module->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="mb-1.5 block text-sm font-semibold text-slate-700">Tes selesai mulai</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100">
                </div>
                <div>
                    <label for="date_to" class="mb-1.5 block text-sm font-semibold text-slate-700">Tes selesai sampai</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100">
                </div>
                @foreach ($errors->all() as $error)
                    <p class="text-sm text-rose-700 md:col-span-4">{{ $error }}</p>
                @endforeach
                <div class="flex flex-wrap gap-2 md:col-span-4">
                    <button type="submit" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Terapkan filter</button>
                    <a href="{{ route('admin.results.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Atur ulang</a>
                    <a href="{{ route('admin.results.export', request()->query()) }}" class="rounded-xl border border-maroon-200 bg-white px-4 py-2.5 text-sm font-semibold text-maroon-800 hover:bg-maroon-50">Ekspor CSV</a>
                </div>
            </form>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-maroon-50 text-xs uppercase tracking-wide text-maroon-900">
                        <tr>
                            <th scope="col" class="px-5 py-4">Pengguna</th>
                            <th scope="col" class="px-5 py-4">Modul</th>
                            <th scope="col" class="px-5 py-4">Skor pre</th>
                            <th scope="col" class="px-5 py-4">Skor post</th>
                            <th scope="col" class="px-5 py-4">Selisih</th>
                            <th scope="col" class="px-5 py-4">Unit selesai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr class="bg-white">
                                @foreach ($row as $value)
                                    <td class="px-5 py-4 text-slate-700">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada tes selesai yang sesuai filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $rows->links() }}
            </div>
        </div>
    </section>
@endcomponent
