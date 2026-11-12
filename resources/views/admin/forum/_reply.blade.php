{{-- FR-A13, FR-A14, AC-33, AC-36: Blade escapes all user-authored forum content. --}}
<article class="glass-card rounded-2xl p-5" style="margin-left: {{ min($depth, 4) * 1.25 }}rem">
    <div class="flex flex-wrap items-center gap-2">
        <span class="font-semibold text-slate-900">{{ $reply->author->display_name }}</span>
        @if ($reply->author->role === \App\Enums\Role::Admin->value)
            <span class="rounded-full bg-maroon-100 px-2.5 py-1 text-xs font-bold text-maroon-900">Pengajar</span>
        @endif
        <time class="ml-auto text-xs text-slate-400" datetime="{{ $reply->created_at->toIso8601String() }}">{{ $reply->created_at->format('d/m/Y H:i') }}</time>
    </div>
    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $reply->content }}</p>
    <div class="mt-4 flex flex-wrap items-start gap-4">
        <form method="POST" action="{{ route('admin.forum.replies.store', $reply) }}" class="min-w-[240px] flex-1 space-y-2">
            @csrf
            <label for="reply-{{ $reply->id }}" class="block text-xs font-semibold text-slate-600">Balas sebagai Pengajar</label>
            <textarea id="reply-{{ $reply->id }}" name="content" rows="2" maxlength="1000" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-maroon-600 focus:outline-none focus:ring-2 focus:ring-maroon-100 @error('content') border-rose-500 @enderror">{{ old('content') }}</textarea>
            @error('content')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
            <button type="submit" class="rounded-lg bg-maroon-800 px-3 py-2 text-xs font-semibold text-white hover:bg-maroon-900">Kirim balasan</button>
        </form>
        <form method="POST" action="{{ route('admin.forum.posts.destroy', $reply) }}" onsubmit="return confirm('Hapus balasan ini beserta balasan turunannya dan tutup laporan terkait?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus balasan</button>
        </form>
    </div>
    @foreach ($reply->replies as $nestedReply)
        @include('admin.forum._reply', ['reply' => $nestedReply, 'depth' => $depth + 1])
    @endforeach
</article>
