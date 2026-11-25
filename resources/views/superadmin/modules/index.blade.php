{{-- FR-SA07, PS-22, PS-27–PS-29 --}}
@component('layouts.superadmin', ['title' => 'Antrean Review Modul'])
    <section class="space-y-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Antrean review</h1>
            <p class="mt-1 text-sm text-slate-600">Urutan pengajuan dari yang terlama; penanda SLA dihitung berdasarkan hari kerja Senin–Jumat.</p>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-900">Pengajuan modul</h2>
            </div>
            @forelse ($submissions as $module)
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $module->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">Admin: {{ $module->creator?->display_name ?? 'Tidak diketahui' }} · Diajukan {{ $module->submitted_at?->format('d M Y H:i') }}</p>
                        @if ($module->revision_of_id)
                            <span class="mt-2 inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-800">Pengajuan ulang</span>
                        @endif
                        @if ($module->near_sla)
                            <span class="mt-2 inline-flex rounded-full {{ $module->past_sla ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-900' }} px-2.5 py-1 text-xs font-semibold">
                                {{ $module->past_sla ? 'SLA 3 hari kerja terlewati' : 'Mendekati batas SLA' }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('superadmin.modules.show', $module) }}" class="rounded-xl border border-maroon-200 px-4 py-2 text-center text-sm font-semibold text-maroon-800">Pratinjau dan review</a>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-600">Belum ada pengajuan review.</p>
            @endforelse
            <div class="border-t border-slate-100 px-5 py-4">{{ $submissions->links() }}</div>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-900">Daftar modul (hanya-baca)</h2>
            </div>
            @forelse ($allModules as $module)
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $module->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $module->creator?->display_name ?? 'Tidak diketahui' }} · {{ $module->units_count }} unit · {{ str_replace('_', ' ', ucfirst($module->status)) }}</p>
                    </div>
                    <a href="{{ route('superadmin.modules.show', $module) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-center text-sm font-semibold text-slate-700">Lihat detail</a>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-600">Belum ada modul.</p>
            @endforelse
            <div class="border-t border-slate-100 px-5 py-4">{{ $allModules->links() }}</div>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-900">Pengajuan perpanjangan</h2>
            </div>
            @forelse ($extensions as $extension)
                <div class="border-b border-slate-100 px-5 py-5 last:border-b-0">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $extension->module->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">Admin: {{ $extension->requester?->display_name ?? 'Tidak diketahui' }} · Sampai {{ $extension->previous_active_until->format('d M Y') }} → {{ $extension->requested_active_until->format('d M Y') }}</p>
                            <p class="mt-2 text-sm text-slate-700">{{ $extension->reason }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('superadmin.extensions.decision', $extension) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                        @csrf
                        <div>
                            <label for="notes-{{ $extension->id }}" class="sr-only">Catatan keputusan</label>
                            <input id="notes-{{ $extension->id }}" name="notes" maxlength="2000" placeholder="Catatan wajib jika ditolak" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <button name="decision" value="approve" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Setujui</button>
                        <button name="decision" value="reject" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700">Tolak</button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-600">Belum ada pengajuan perpanjangan.</p>
            @endforelse
            <div class="border-t border-slate-100 px-5 py-4">{{ $extensions->links() }}</div>
        </div>
    </section>
@endcomponent
