{{-- FR-A03, FR-A16, AC-45 --}}
@component('layouts.admin', ['title' => 'Kelola Modul'])
    <section class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('admin.modules.units.index', $module) }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-900">&larr; Kembali ke unit</a>
            <p class="mt-3 text-sm font-semibold text-maroon-800">{{ $module->title }} &middot; Unit {{ $unit->sequence_order }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $unit->title }}</h1>
            <p class="mt-2 text-sm text-slate-600">{{ $unit->learning_objective }}</p>
        </div>

        <article class="glass-card overflow-hidden rounded-2xl">
            <div class="bg-slate-950">
                @if ($youtubeEmbedUrl)
                    <iframe
                        src="{{ $youtubeEmbedUrl }}"
                        title="Pratinjau video {{ $unit->title }}"
                        class="aspect-video w-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen
                    ></iframe>
                @else
                    <video controls preload="metadata" class="aspect-video w-full" aria-label="Pratinjau video {{ $unit->title }}">
                        <source src="{{ $unit->video_url }}">
                        Browser Anda tidak mendukung pemutar video.
                    </video>
                @endif
            </div>
            <div class="space-y-4 p-6 sm:p-8">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-900">Materi singkat</h2>
                    @if ($unit->duration_label)
                        <span class="text-xs text-slate-500">{{ $unit->duration_label }}</span>
                    @endif
                </div>
                @if ($readingContent !== '')
                    <div class="material-preview-content text-sm leading-7 text-slate-700">{!! $readingContent !!}</div>
                @else
                    <p class="text-sm leading-7 text-slate-500">Belum ada materi singkat untuk unit ini.</p>
                @endif
            </div>
        </article>
    </section>
@endcomponent
