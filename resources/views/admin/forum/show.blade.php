{{-- FR-A13, FR-A14, AC-33, AC-36 --}}
@component('layouts.admin', ['title' => $title])
    <section class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.forum.index', ['module_id' => $topic->module_id]) }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-950">← Kembali ke forum</a>
            <a href="{{ route('admin.forum-reports.index') }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-950">Lihat laporan</a>
        </div>

        <article class="glass-card rounded-2xl p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Topik diskusi</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $topic->author->display_name }}</p>
                    @if ($topic->unit)
                        <p class="mt-1 text-xs text-slate-500">Unit terkait: {{ $topic->unit->title }}</p>
                    @endif
                </div>
                <time class="text-xs text-slate-400" datetime="{{ $topic->created_at->toIso8601String() }}">{{ $topic->created_at->format('d/m/Y H:i') }}</time>
            </div>
            <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $topic->content }}</p>
            <form method="POST" action="{{ route('admin.forum.posts.destroy', $topic) }}" class="mt-5" onsubmit="return confirm('Hapus topik beserta seluruh balasan dan tutup laporan terkait?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Hapus topik</button>
            </form>
        </article>

        <div class="space-y-4">
            <h2 class="text-base font-bold text-slate-900">Balasan</h2>
            @forelse ($topic->replies as $reply)
                @include('admin.forum._reply', ['reply' => $reply, 'depth' => 0])
            @empty
                <p class="glass-card rounded-2xl p-5 text-sm text-slate-500">Belum ada balasan.</p>
            @endforelse
        </div>

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h2 class="font-bold text-slate-900">Balas topik sebagai Pengajar</h2>
            <form method="POST" action="{{ route('admin.forum.replies.store', $topic) }}" class="mt-4 space-y-3">
                @csrf
                <label for="topic-reply" class="block text-sm font-semibold text-slate-700">Balasan</label>
                <textarea id="topic-reply" name="content" rows="4" maxlength="1000" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('content') border-rose-500 @enderror">{{ old('content') }}</textarea>
                @error('content')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                <button type="submit" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Kirim balasan</button>
            </form>
        </div>
    </section>
@endcomponent
