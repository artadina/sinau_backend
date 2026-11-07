{{-- FR-A11, EC-08, AB-12 --}}
@component('layouts.admin', ['title' => 'Kelola Kuis Unit'])
    <section class="space-y-6">
        <div>
            <a href="{{ route('admin.modules.units.index', $module) }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-900">&larr; Kembali ke unit</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Kuis Unit: {{ $unit->title }}</h1>
            <p class="mt-1 text-sm text-slate-600">Unit perlu memiliki 3 sampai 5 soal kuis saat modul diterbitkan.</p>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Daftar soal kuis</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $quizzes->count() }} soal saat ini.</p>
            </div>
            <a href="{{ route('admin.units.quizzes.create', $unit) }}" class="inline-flex justify-center rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Tambah Soal</a>
        </div>

        <div class="glass-card overflow-hidden rounded-2xl">
            @if ($quizzes->isEmpty())
                <div class="px-6 py-10 text-center">
                    <h2 class="font-semibold text-slate-900">Belum ada soal kuis</h2>
                    <p class="mt-1 text-sm text-slate-600">Tambahkan 3 sampai 5 soal agar modul dapat diterbitkan.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($quizzes as $quiz)
                        <article class="flex flex-col justify-between gap-3 p-5 sm:flex-row">
                            <div class="min-w-0 space-y-2">
                                <h3 class="font-semibold text-slate-900">{{ $loop->iteration }}. {{ $quiz->question_text }}</h3>
                                <ul class="grid gap-1 text-sm text-slate-600 sm:grid-cols-2">
                                    @foreach ($quiz->options as $key => $option)
                                        <li><span class="font-semibold">{{ $key }}.</span> {{ $option }}</li>
                                    @endforeach
                                </ul>
                                <p class="text-xs text-emerald-800">Kunci: {{ $quiz->correct_key }}</p>
                                <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600"><span class="font-semibold">Pembahasan:</span> {{ $quiz->explanation }}</p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <a href="{{ route('admin.units.quizzes.edit', [$unit, $quiz]) }}" class="h-fit rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Ubah</a>
                                <form method="POST" action="{{ route('admin.units.quizzes.destroy', [$unit, $quiz]) }}" onsubmit="return confirm('Yakin ingin menghapus soal kuis ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endcomponent
