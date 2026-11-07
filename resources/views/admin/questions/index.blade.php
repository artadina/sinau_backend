{{-- FR-A05, AC-23, AB-12 --}}
@component('layouts.admin', ['title' => 'Kelola Bank Soal'])
    <section class="space-y-6" data-question-bank data-storage-key="sinau-question-import-{{ $module->id }}-{{ $type }}" data-clear-import="{{ $clearImportedQuestions ? 'true' : 'false' }}">
        <div>
            <a href="{{ route('admin.modules.index') }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-900">&larr; Kembali ke modul</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Bank Soal: {{ $module->title }}</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola soal Pre-test dan Post-test yang digunakan bersama oleh seluruh Admin.</p>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif
        <nav class="flex gap-2 border-b border-slate-200" aria-label="Jenis bank soal">
            @foreach (['pre' => 'Pre-test', 'post' => 'Post-test'] as $tab => $label)
                <a href="{{ route('admin.modules.questions.index', [$module, $tab]) }}"
                    @class([
                        'rounded-t-xl px-4 py-2.5 text-sm font-semibold',
                        'bg-maroon-800 text-white' => $type === $tab,
                        'bg-white text-slate-600 hover:bg-maroon-50' => $type !== $tab,
                    ])>{{ $label }}</a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.modules.questions.settings', $module) }}" class="glass-card grid gap-4 rounded-2xl p-5 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            @method('PUT')
            <div>
                <label for="pre_question_count" class="mb-1.5 block text-sm font-semibold text-slate-800">Jumlah soal Pre-test <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
                <input id="pre_question_count" name="pre_question_count" type="number" min="1" max="{{ $preCount }}" step="1" value="{{ old('pre_question_count', $module->pre_question_count) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <p class="mt-1 text-xs text-slate-500">Tersedia {{ $preCount }} soal.</p>
                @error('pre_question_count')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="post_question_count" class="mb-1.5 block text-sm font-semibold text-slate-800">Jumlah soal Post-test <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
                <input id="post_question_count" name="post_question_count" type="number" min="1" max="{{ $postCount }}" step="1" value="{{ old('post_question_count', $module->post_question_count) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <p class="mt-1 text-xs text-slate-500">Tersedia {{ $postCount }} soal.</p>
                @error('post_question_count')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-start pt-1">
                <input type="hidden" name="shuffle_questions" value="0">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $module->shuffle_questions)) class="rounded border-slate-300 text-maroon-800 focus:ring-maroon-600">
                    Acak urutan soal
                </label>
                @error('shuffle_questions')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Simpan Pengaturan</button>
            </div>
        </form>

        @error('status')
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $message }}</div>
        @enderror

        <section class="space-y-4" aria-labelledby="question-list-heading">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <h2 id="question-list-heading" class="text-lg font-bold text-slate-900">{{ $type === 'pre' ? 'Soal Pre-test' : 'Soal Post-test' }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $questions->count() }} soal dalam bank.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-open-question-modal class="rounded-xl bg-maroon-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Tambah kartu soal</button>
                    <label for="question_csv" class="cursor-pointer rounded-xl border border-maroon-200 bg-white px-4 py-2.5 text-sm font-semibold text-maroon-800 hover:bg-maroon-50">Pilih file CSV</label>
                    <input id="question_csv" data-question-csv data-import-url="{{ route('admin.modules.questions.import', [$module, $type]) }}" data-storage-key="sinau-question-import-{{ $module->id }}-{{ $type }}" type="file" accept=".csv,text/csv" class="sr-only">
                    <button type="button" data-download-csv-template class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Template CSV</button>
                </div>
            </div>
            <p data-question-import-message role="status" aria-live="polite" class="hidden rounded-lg px-3 py-2 text-sm"></p>
        </section>

        @if ($errors->has('questions') || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'questions.')))
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">Ada soal yang belum valid. Periksa kembali kartu soal sebelum menyimpan.</div>
        @endif

        <dialog data-question-modal aria-labelledby="bulk-question-heading" class="question-entry-modal m-auto w-[min(56rem,calc(100%-2rem))] max-h-[90vh] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/50">
            <section class="space-y-5 p-5 sm:p-7" data-question-editor data-mode="manual" data-storage-key="sinau-question-import-{{ $module->id }}-{{ $type }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="bulk-question-heading" class="text-xl font-bold text-slate-900">Tambah beberapa soal</h2>
                        <p class="mt-1 text-sm text-slate-600">Semua kartu di sini disimpan sekaligus ke tab {{ $type === 'pre' ? 'Pre-test' : 'Post-test' }}.</p>
                    </div>
                    <button type="button" data-close-question-modal aria-label="Tutup" class="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-600 hover:bg-slate-50">&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.modules.questions.bulk', [$module, $type]) }}" data-question-bulk-form class="space-y-4">
                    @csrf
                    <input type="hidden" name="source" value="manual">
                    <div data-question-list class="space-y-4"></div>
                    @include('admin.questions._question-card-template')
                    <script type="application/json" data-question-initial-input>{!! json_encode(old('questions', []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                    <script type="application/json" data-question-validation-errors>{!! json_encode($errors->getMessages(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                    <div class="flex flex-col-reverse justify-between gap-3 border-t border-slate-100 pt-4 sm:flex-row">
                        <button type="button" data-add-question class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tambah kartu lain</button>
                        <div class="flex justify-end gap-2">
                            <button type="button" data-close-question-modal class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                            <button type="submit" class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Simpan Semua Soal</button>
                        </div>
                    </div>
                </form>
            </section>
        </dialog>

        <div class="glass-card overflow-hidden rounded-2xl">
            @if ($questions->isEmpty())
                <div class="px-6 py-10 text-center">
                    <h2 class="font-semibold text-slate-900">Belum ada soal {{ $type === 'pre' ? 'Pre-test' : 'Post-test' }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Tambahkan soal ke bank sebelum mengatur jumlah soal tes.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($questions as $question)
                        <article class="space-y-3 p-5">
                            <div class="flex flex-col justify-between gap-3 sm:flex-row">
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-slate-900">{{ $loop->iteration }}. {{ $question->question_text }}</h3>
                                    <ul class="mt-2 grid gap-1 text-sm text-slate-600 sm:grid-cols-2">
                                        @foreach ($question->options as $key => $option)
                                            <li><span class="font-semibold">{{ $key }}.</span> {{ $option }}</li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-2 inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-900">Kunci jawaban: {{ $question->correct_key }}</p>
                                </div>
                                <div class="flex shrink-0 items-start gap-2 self-start">
                                    <a href="{{ route('admin.modules.questions.edit', [$module, $type, $question]) }}" class="inline-flex h-fit self-start whitespace-nowrap rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold leading-5 text-slate-700 hover:border-slate-400 hover:bg-slate-50">Ubah</a>
                                    <form method="POST" action="{{ route('admin.modules.questions.destroy', [$module, $type, $question]) }}" onsubmit="return confirm('Yakin ingin menghapus soal ini?')" class="self-start">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-fit whitespace-nowrap rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold leading-5 text-rose-700 hover:border-rose-400 hover:bg-rose-50">Hapus</button>
                                    </form>
                                </div>
                            </div>
                            <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600"><span class="font-semibold">Pembahasan:</span> {{ $question->explanation }}</p>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endcomponent
