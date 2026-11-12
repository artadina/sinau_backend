{{-- FR-A14, FR-A15, AC-34, AC-36, AC-37 --}}
@component('layouts.admin', ['title' => $title])
    <section class="space-y-6">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.forum.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Forum</a>
            <a href="{{ route('admin.forum-reports.index') }}" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white">Laporan</a>
        </div>

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h2 class="font-bold text-slate-900">Laporan konten forum</h2>
            <p class="mt-1 text-sm text-slate-500">Postingan tetap tampil sampai Admin menghapusnya. Laporan yang selesai tetap tersimpan sebagai riwayat.</p>
            <nav class="mt-4 flex flex-wrap gap-2" aria-label="Filter status laporan">
                @foreach (['pending' => 'Pending', 'resolved' => 'Resolved', 'all' => 'Semua'] as $value => $label)
                    <a href="{{ route('admin.forum-reports.index', ['status' => $value]) }}" @class([
                        'rounded-lg px-3 py-2 text-sm font-semibold',
                        'bg-maroon-800 text-white' => $status === $value,
                        'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' => $status !== $value,
                    ])>{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        <div class="space-y-4">
            @forelse ($reports as $report)
                <article class="glass-card rounded-2xl p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <span @class([
                                'inline-flex rounded-full px-3 py-1 text-xs font-bold',
                                'bg-rose-100 text-rose-800' => $report->status === 'pending',
                                'bg-emerald-100 text-emerald-800' => $report->status === 'resolved',
                            ])>{{ $report->status === 'pending' ? 'Pending' : 'Resolved' }}</span>
                            <p class="mt-2 text-sm font-semibold text-slate-900">Penulis: {{ $report->post?->author?->name ?? 'Tidak tersedia' }}</p>
                            <p class="mt-1 text-sm text-slate-600">Pelapor: {{ $report->reporter->name }}</p>
                        </div>
                        <time class="text-xs text-slate-400" datetime="{{ $report->created_at->toIso8601String() }}">{{ $report->created_at->format('d/m/Y H:i') }}</time>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Postingan yang dilaporkan</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $report->post?->content ?? 'Konten sudah dihapus' }}</p>
                        @if ($report->post?->module)
                            <p class="mt-2 text-xs text-slate-500">
                                Modul: {{ $report->post->module->title }}
                                @if ($report->post->unit)
                                    · Unit: {{ $report->post->unit->title }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <div class="mt-4">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Alasan pelaporan</h3>
                        <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $report->reason }}</p>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        @if ($report->post && $report->post->parent_id === null)
                            <a href="{{ route('admin.forum.show', $report->post) }}" class="rounded-xl border border-maroon-200 bg-white px-4 py-2 text-sm font-semibold text-maroon-800 hover:bg-maroon-50">Buka topik</a>
                        @elseif ($report->post)
                            <a href="{{ route('admin.forum.show', $report->post->parent_id) }}" class="rounded-xl border border-maroon-200 bg-white px-4 py-2 text-sm font-semibold text-maroon-800 hover:bg-maroon-50">Buka diskusi</a>
                        @endif
                        @if ($report->status === 'pending')
                            <form method="POST" action="{{ route('admin.forum-reports.resolve', $report) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Tandai Resolved</button>
                            </form>
                            @if ($report->post)
                                <form method="POST" action="{{ route('admin.forum.posts.destroy', $report->post) }}" onsubmit="return confirm('Hapus posting ini dan tutup seluruh laporan terkait?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Hapus posting</button>
                                </form>
                            @endif
                        @elseif ($report->handler)
                            <p class="self-center text-xs text-slate-500">Ditangani oleh {{ $report->handler->name }} pada {{ $report->handled_at?->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </article>
            @empty
                <p class="glass-card rounded-2xl p-8 text-center text-sm text-slate-500">Tidak ada laporan untuk filter ini.</p>
            @endforelse
            @if ($reports->hasPages())
                <div class="glass-card rounded-2xl px-5 py-4">{{ $reports->links() }}</div>
            @endif
        </div>
    </section>
@endcomponent
