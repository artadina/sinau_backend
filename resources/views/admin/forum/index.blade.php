{{-- FR-A13, FR-A15, AC-33, AC-34 --}}
@component('layouts.admin', ['title' => $title])
    <section class="space-y-6">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.forum.index', ['module_id' => $selectedModuleId]) }}" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white">Forum</a>
            <a href="{{ route('admin.forum-reports.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Laporan</a>
        </div>

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <form method="GET" action="{{ route('admin.forum.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[220px] flex-1">
                    <label for="module_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Pilih modul</label>
                    <select id="module_id" name="module_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100">
                        @foreach ($modules as $module)
                            <option value="{{ $module->id }}" @selected((string) $selectedModuleId === (string) $module->id)>{{ $module->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Tampilkan</button>
            </form>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-bold text-slate-900">Topik diskusi</h2>
                <p class="mt-1 text-sm text-slate-500">Admin dapat membaca dan membalas diskusi, tetapi tidak membuat topik baru.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($topics as $topic)
                    <a href="{{ route('admin.forum.show', $topic) }}" class="block px-5 py-5 transition hover:bg-maroon-50/40">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-slate-900">{{ \Illuminate\Support\Str::limit($topic->content, 120) }}</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ $topic->author->display_name }}</p>
                                @if ($topic->unit)
                                    <p class="mt-1 text-xs text-slate-500">Unit terkait: {{ $topic->unit->title }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-full bg-maroon-50 px-3 py-1 text-xs font-semibold text-maroon-800">{{ $topic->replies_count }} balasan</span>
                        </div>
                        <time class="mt-3 block text-xs text-slate-400" datetime="{{ $topic->created_at->toIso8601String() }}">{{ $topic->created_at->format('d/m/Y H:i') }}</time>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">
                        @if ($modules->isEmpty())
                            Belum ada modul untuk ditampilkan.
                        @else
                            Belum ada topik pada modul ini.
                        @endif
                    </p>
                @endforelse
            </div>
            @if ($topics->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $topics->links() }}</div>
            @endif
        </div>
    </section>
@endcomponent
