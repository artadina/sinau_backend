{{-- FR-A02, PS-01–PS-30 --}}
@component('layouts.admin', ['title' => 'Detail Modul'])
    @php
        $allAutomaticChecksPassed = collect($checks)->every(fn ($check) => $check['passed']);
        $editable = in_array($module->status, ['draft', 'revision_required'], true);
        $statusLabels = [
            'draft' => 'Draft',
            'submitted' => 'Menunggu review',
            'revision_required' => 'Perlu revisi',
            'approved' => 'Disetujui (terjadwal)',
            'published' => 'Terbit',
            'archived' => 'Diarsipkan',
        ];
    @endphp

    <section class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div>
                <a href="{{ route('admin.modules.index') }}" class="text-sm font-semibold text-maroon-800">&larr; Kelola Modul</a>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $module->title }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $statusLabels[$module->status] ?? $module->status }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($editable)
                    <a href="{{ route('admin.modules.edit', $module) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Ubah metadata</a>
                    <a href="{{ route('admin.modules.units.index', $module) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Kelola unit</a>
                    <a href="{{ route('admin.modules.questions.index', [$module, 'pre']) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Kelola bank soal</a>
                @elseif ($module->status === 'published')
                    <a href="{{ route('admin.modules.edit', $module) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Edit minor</a>
                    <form method="POST" action="{{ route('admin.modules.revision', $module) }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-maroon-200 px-4 py-2 text-sm font-semibold text-maroon-800">Buat versi untuk perubahan signifikan</button>
                    </form>
                @elseif ($module->status === 'archived')
                    <form method="POST" action="{{ route('admin.modules.revision', $module) }}">
                        @csrf
                        <button type="submit" class="rounded-xl border border-maroon-200 px-4 py-2 text-sm font-semibold text-maroon-800">Buat pengajuan baru dari salinan</button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif
        @error('quality_gate')
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</div>
        @enderror

        @if ($module->status === 'revision_required')
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="font-semibold text-amber-900">Catatan revisi Superadmin</h2>
                @foreach ($module->reviews->where('action', 'revision_requested') as $review)
                    <div class="mt-3 border-t border-amber-200 pt-3 text-sm text-amber-900">
                        <p>{{ $review->notes }}</p>
                        <p class="mt-1 text-xs text-amber-700">{{ $review->actor?->name ?? 'Superadmin' }} · {{ $review->created_at?->format('d M Y H:i') }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Quality Gate</h2>
                    <p class="mt-1 text-sm text-slate-600">Periksa daftar ini setelah menyimpan perubahan modul, unit, atau soal.</p>
                </div>
                @if ($editable)
                    <form method="POST" action="{{ route('admin.modules.submit', $module) }}" class="flex flex-wrap items-center gap-3">
                        @csrf
                        <label class="flex max-w-lg items-start gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="copyright_confirmation" value="1" required class="mt-0.5 rounded border-slate-300">
                            <span>Saya menyatakan memiliki atau memperoleh izin penggunaan seluruh konten.</span>
                        </label>
                        <button type="submit" @disabled(! $allAutomaticChecksPassed) class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                            {{ $module->status === 'revision_required' ? 'Ajukan ulang' : 'Ajukan review' }}
                        </button>
                    </form>
                @endif
            </div>
            @error('copyright_confirmation')<p class="mt-3 text-sm text-rose-700">{{ $message }}</p>@enderror
            <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($checks as $code => $check)
                    <li class="rounded-xl border {{ $check['passed'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-3">
                        <p class="text-xs font-bold {{ $check['passed'] ? 'text-emerald-800' : 'text-rose-800' }}">
                            {{ $check['passed'] ? '✓' : '×' }} {{ $code }}
                        </p>
                        <p class="mt-1 text-sm {{ $check['passed'] ? 'text-emerald-900' : 'text-rose-900' }}">{{ $check['message'] }}</p>
                        @if (! $check['passed'] && $editable)
                            <a href="{{ $check['url'] }}" class="mt-1 inline-block text-xs font-semibold underline {{ $check['passed'] ? 'text-emerald-800' : 'text-rose-800' }}">Perbaiki bagian ini</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($module->status === 'published')
            <div class="glass-card rounded-2xl p-5 sm:p-7">
                <h2 class="text-lg font-bold text-slate-900">Ajukan perpanjangan periode aktif</h2>
                <p class="mt-1 text-sm text-slate-600">Modul tetap tayang sampai tanggal aktif saat ini saat pengajuan menunggu keputusan.</p>
                @if ($pendingExtension)
                    <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Pengajuan perpanjangan sedang menunggu Superadmin.</p>
                @else
                    <form method="POST" action="{{ route('admin.modules.extensions.store', $module) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label for="active_until" class="mb-1 block text-sm font-semibold text-slate-800">Tanggal akhir baru</label>
                            <input id="active_until" name="active_until" type="date" required value="{{ old('active_until') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                            @error('active_until')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="reason" class="mb-1 block text-sm font-semibold text-slate-800">Alasan perpanjangan</label>
                            <textarea id="reason" name="reason" rows="3" required class="w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('reason') }}</textarea>
                            @error('reason')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white">Ajukan perpanjangan</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h2 class="text-lg font-bold text-slate-900">Riwayat audit</h2>
            <ol class="mt-4 space-y-4">
                @forelse ($module->reviews as $review)
                    <li class="border-l-2 border-maroon-200 pl-4">
                        <p class="text-sm font-semibold text-slate-900">{{ str_replace('_', ' ', ucfirst($review->action)) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $review->actor?->name ?? 'Sistem' }} · {{ $review->created_at?->format('d M Y H:i') }}</p>
                        @if ($review->notes)<p class="mt-1 text-sm text-slate-700">{{ $review->notes }}</p>@endif
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Belum ada riwayat review.</li>
                @endforelse
            </ol>
        </div>
    </section>
@endcomponent
