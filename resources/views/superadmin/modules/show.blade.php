{{-- FR-SA07, PS-23–PS-29 --}}
@component('layouts.superadmin', ['title' => 'Pratinjau Modul'])
    <section class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('superadmin.modules.index') }}" class="text-sm font-semibold text-maroon-800">&larr; Antrean Review</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $module->title }}</h1>
            <p class="mt-1 text-sm text-slate-600">Pratinjau hanya-baca · Status: {{ str_replace('_', ' ', ucfirst($module->status)) }} · Admin: {{ $module->creator?->display_name ?? 'Tidak diketahui' }}</p>
        </div>

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h2 class="text-lg font-bold text-slate-900">Metadata dan periode aktif</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold text-slate-500">Deskripsi</dt><dd class="mt-1 text-sm text-slate-800">{{ $module->description }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">Kategori/topik</dt><dd class="mt-1 text-sm text-slate-800">{{ $module->category }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">Tujuan pembelajaran</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $module->learning_objectives }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">Target peserta / kesulitan</dt><dd class="mt-1 text-sm text-slate-800">{{ $module->target_audience }} · {{ $module->difficulty }}</dd></div>
                <div><dt class="text-xs font-semibold text-slate-500">Periode aktif</dt><dd class="mt-1 text-sm text-slate-800">{{ $module->active_from?->format('d M Y') }} – {{ $module->active_until?->format('d M Y') }}</dd></div>
            </dl>
            @if ($module->cover_image)
                <img src="{{ asset('storage/'.$module->cover_image) }}" alt="Sampul {{ $module->title }}" class="mt-5 h-40 rounded-xl object-cover">
            @endif
        </div>

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h2 class="text-lg font-bold text-slate-900">Unit, video, bacaan, dan kuis</h2>
            <div class="mt-4 space-y-5">
                @foreach ($module->units as $unit)
                    <article class="rounded-xl border border-slate-200 p-4">
                        <h3 class="font-semibold text-slate-900">{{ $unit->sequence_order }}. {{ $unit->title }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $unit->learning_objective }} · {{ $unit->duration_label }}</p>
                        <p class="mt-2 break-all text-xs text-maroon-800">{{ $unit->video_url }}</p>
                        @if ($unit->youtube_embed_url)
                            <iframe src="{{ $unit->youtube_embed_url }}" title="Video {{ $unit->title }}" class="mt-3 aspect-video w-full rounded-lg" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                        @else
                            <video controls preload="metadata" src="{{ $unit->video_url }}" class="mt-3 aspect-video w-full rounded-lg bg-slate-900"></video>
                        @endif
                        <div class="prose prose-sm mt-3 max-w-none">{!! $unit->preview_reading_content !!}</div>
                        <h4 class="mt-4 text-sm font-semibold text-slate-800">Kuis unit</h4>
                        <ol class="mt-2 list-decimal space-y-2 pl-5 text-sm text-slate-700">
                            @foreach ($unit->quizzes as $quiz)
                                <li>
                                    <p>{{ $quiz->question_text }}</p>
                                    <ul class="mt-1 list-disc pl-5 text-xs">
                                        @foreach ($quiz->options as $key => $option)
                                            <li>{{ $key }}. {{ $option }}@if ($quiz->correct_key === $key) · Kunci @endif</li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-1 text-xs text-slate-500">Pembahasan: {{ $quiz->explanation }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h2 class="text-lg font-bold text-slate-900">Bank soal Pre/Post</h2>
            @foreach (['pre' => 'Pre-test', 'post' => 'Post-test'] as $type => $label)
                <h3 class="mt-4 font-semibold text-slate-800">{{ $label }}</h3>
                <ol class="mt-2 list-decimal space-y-2 pl-5 text-sm text-slate-700">
                    @foreach ($module->testQuestions->where('type', $type) as $question)
                        <li>
                            <p>{{ $question->question_text }}</p>
                            <ul class="mt-1 list-disc pl-5 text-xs">
                                @foreach ($question->options as $key => $option)
                                    <li>{{ $key }}. {{ $option }}@if ($question->correct_key === $key) · Kunci @endif</li>
                                @endforeach
                            </ul>
                            <p class="mt-1 text-xs text-slate-500">Pembahasan: {{ $question->explanation }}</p>
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </div>

        @if ($module->status === 'submitted')
            <div class="glass-card rounded-2xl p-5 sm:p-7">
                <h2 class="text-lg font-bold text-slate-900">Checklist kebijakan isi</h2>
                <form method="POST" action="{{ route('superadmin.modules.approve', $module) }}" class="mt-4 space-y-3">
                    @csrf
                    @foreach ([
                        'accurate' => 'PS-23: Materi akurat, relevan dengan konteks UMKM, dan mudah dipahami.',
                        'safe' => 'PS-24: Materi tidak mengandung SARA, ujaran kebencian, atau informasi menyesatkan.',
                        'copyright' => 'PS-25: Materi tidak melanggar hak cipta.',
                        'privacy' => 'PS-26: Materi tidak memuat data pribadi pihak ketiga tanpa izin.',
                    ] as $key => $label)
                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="policy_checks[{{ $key }}]" value="1" class="mt-0.5 rounded border-slate-300">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-rose-700">{{ $error }}</p>
                    @endforeach
                    <button type="submit" class="mt-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Setujui modul</button>
                </form>

                <form method="POST" action="{{ route('superadmin.modules.revision', $module) }}" class="mt-7 border-t border-slate-100 pt-5">
                    @csrf
                    <label for="revision-notes" class="mb-1.5 block text-sm font-semibold text-slate-800">Minta revisi (catatan wajib)</label>
                    <textarea id="revision-notes" name="notes" rows="3" required class="w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    <button type="submit" class="mt-3 rounded-xl border border-amber-300 px-4 py-2.5 text-sm font-semibold text-amber-900">Kirim catatan revisi</button>
                </form>
            </div>
        @endif

        @if ($module->status === 'published')
            <div class="glass-card rounded-2xl border border-rose-200 p-5 sm:p-7">
                <h2 class="text-lg font-bold text-rose-900">Takedown</h2>
                <p class="mt-1 text-sm text-rose-800">Modul akan langsung diarsipkan dan akses kontennya ditutup.</p>
                <form method="POST" action="{{ route('superadmin.modules.takedown', $module) }}" class="mt-4">
                    @csrf
                    <label for="takedown-reason" class="mb-1.5 block text-sm font-semibold text-slate-800">Alasan takedown</label>
                    <textarea id="takedown-reason" name="reason" rows="3" required class="w-full rounded-xl border border-slate-300 px-3 py-2"></textarea>
                    @error('reason')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    <button type="submit" class="mt-3 rounded-xl bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white">Turunkan modul</button>
                </form>
            </div>
        @endif

        <div class="glass-card rounded-2xl p-5 sm:p-7">
            <h2 class="text-lg font-bold text-slate-900">Riwayat pengajuan dan review</h2>
            <ol class="mt-4 space-y-3">
                @forelse ($module->reviews as $review)
                    <li class="border-l-2 border-maroon-200 pl-4">
                        <p class="text-sm font-semibold text-slate-900">{{ str_replace('_', ' ', ucfirst($review->action)) }}</p>
                        <p class="text-xs text-slate-500">{{ $review->actor?->name ?? 'Sistem' }} · {{ $review->created_at?->format('d M Y H:i') }}</p>
                        @if ($review->notes)<p class="mt-1 text-sm text-slate-700">{{ $review->notes }}</p>@endif
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Belum ada riwayat.</li>
                @endforelse
            </ol>
        </div>
    </section>
@endcomponent
