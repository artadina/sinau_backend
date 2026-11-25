{{-- FR-A11, AC-42 --}}
@component('layouts.admin', ['title' => 'Kelola Kuis Unit'])
    <section class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('admin.units.quizzes.index', $unit) }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-900">&larr; Kembali ke kuis unit</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $quiz->exists ? 'Ubah Soal Kuis' : 'Tambah Soal Kuis' }}</h1>
            <p class="mt-1 text-sm text-slate-600">Modul: {{ $module->title }} · Unit: {{ $unit->title }}</p>
        </div>

        <form method="POST" data-question-format-form action="{{ $quiz->exists ? route('admin.units.quizzes.update', [$unit, $quiz]) : route('admin.units.quizzes.store', $unit) }}" class="glass-card space-y-5 rounded-2xl p-6 sm:p-8">
            @csrf
            @if ($quiz->exists)
                @method('PUT')
            @endif

            <div>
                <label for="question_text" class="mb-1.5 block text-sm font-semibold text-slate-800">Teks soal atau kasus <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
                <textarea id="question_text" name="question_text" rows="4" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('question_text', $quiz->question_text) }}</textarea>
                @error('question_text')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="question_format" class="mb-1.5 block text-sm font-semibold text-slate-800">Format soal <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
                <select id="question_format" name="question_format" data-question-format required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @foreach (['multiple_choice' => 'Pilihan ganda', 'checkbox' => 'Checkbox (lebih dari satu jawaban)', 'true_false' => 'Benar / Salah'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('question_format', $quiz->question_format ?? 'multiple_choice') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('question_format')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            @php($options = old('options', $quiz->options ?? ['A' => '', 'B' => '', 'C' => '']))
            @php($correctKeys = old('correct_keys', $quiz->answer_keys))
            @foreach (['A', 'B', 'C', 'D'] as $key)
                <div data-question-option-wrap="{{ $key }}">
                    <label for="option_{{ $key }}" class="mb-1.5 block text-sm font-semibold text-slate-800">Pilihan {{ $key }} @if ($key !== 'D')<span class="text-rose-600" aria-label="wajib diisi">*</span>@else<span class="font-normal text-slate-500">(Opsional)</span>@endif</label>
                    <input id="option_{{ $key }}" name="options[{{ $key }}]" value="{{ $options[$key] ?? '' }}" @required(in_array($key, ['A', 'B', 'C'], true)) maxlength="255" data-question-option="{{ $key }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    @error('options.'.$key)<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            @error('options')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror

            <fieldset>
                <legend class="mb-1.5 block text-sm font-semibold text-slate-800">Kunci jawaban <span class="text-rose-600" aria-label="wajib diisi">*</span></legend>
                    @foreach (['A', 'B', 'C', 'D'] as $key)
                        <label data-question-correct-wrap="{{ $key }}" class="mr-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <input type="radio" name="correct_keys[]" value="{{ $key }}" data-question-correct-choice="{{ $key }}" @checked(in_array($key, $correctKeys, true)) class="border-slate-300 text-maroon-800 focus:ring-maroon-600">
                            <span>{{ $key }}</span>
                        </label>
                    @endforeach
                @error('correct_keys')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                @error('correct_keys.*')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </fieldset>

            <div>
                <label for="explanation" class="mb-1.5 block text-sm font-semibold text-slate-800">Pembahasan <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
                <textarea id="explanation" name="explanation" rows="3" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('explanation', $quiz->explanation) }}</textarea>
                @error('explanation')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.units.quizzes.index', $unit) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Simpan Soal</button>
            </div>
        </form>
    </section>
@endcomponent
