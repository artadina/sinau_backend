{{-- FR-A10, AC-40 --}}
@component('layouts.admin', ['title' => $title])
    <div class="space-y-7">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['Jumlah Learner', $learnerCount],
                ['Jumlah Modul', $moduleCount],
                ['Tes Selesai', $completedTestCount],
            ] as [$label, $value])
                <article class="glass-card rounded-2xl p-6">
                    <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-extrabold text-maroon-800">{{ number_format($value) }}</p>
                </article>
            @endforeach
        </section>

        <section class="glass-card rounded-2xl p-6 sm:p-8">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Rata-rata skor per modul</h1>
                    <p class="mt-1 text-sm text-slate-500">Learner dengan skor pre dan post selesai.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.modules.index') }}" class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Kelola Modul</a>
                    <a href="/admin/questions" class="rounded-xl border border-maroon-200 bg-white px-4 py-2.5 text-sm font-semibold text-maroon-800 hover:bg-maroon-50">Kelola Soal</a>
                </div>
            </div>

            @if ($moduleAverages->isEmpty())
                <p class="mt-6 rounded-xl bg-slate-50 px-4 py-5 text-sm text-slate-500">Belum ada modul dengan skor pre dan post yang selesai.</p>
            @else
                <div class="mt-6 space-y-5">
                    @foreach ($moduleAverages as $module)
                        @php
                            $pre = $module->average_pre === null ? null : (float) $module->average_pre;
                            $post = $module->average_post === null ? null : (float) $module->average_post;
                        @endphp
                        <article class="rounded-xl border border-slate-100 p-4">
                            <h2 class="font-semibold text-slate-900">{{ $module->title }}</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @foreach ([['Pre', $pre], ['Post', $post]] as [$label, $score])
                                    <div>
                                        <div class="mb-1 flex justify-between text-xs font-semibold text-slate-600">
                                            <span>{{ $label }}</span>
                                            <span>{{ $score === null ? '-' : number_format($score, 2, '.', '') }}</span>
                                        </div>
                                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-maroon-800" style="width: {{ $score === null ? 0 : min(100, max(0, $score)) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endcomponent
