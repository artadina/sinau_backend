<template data-question-template>
    <article data-question-card class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
        <div class="flex items-center justify-between gap-3">
            <h3 class="font-semibold text-slate-900">Soal <span data-question-number></span></h3>
            <button type="button" data-remove-question class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Hapus kartu</button>
        </div>
        <div>
            <label for="question-__INDEX__-text" class="mb-1.5 block text-sm font-semibold text-slate-800">Teks soal <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
            <textarea id="question-__INDEX__-text" name="questions[__INDEX__][question_text]" rows="3" required data-question-text class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></textarea>
            <p data-error-for="questions.__INDEX__.question_text" class="mt-1 hidden text-sm text-rose-700"></p>
        </div>
        <div>
            <label for="question-__INDEX__-format" class="mb-1.5 block text-sm font-semibold text-slate-800">Format soal <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
            <select id="question-__INDEX__-format" name="questions[__INDEX__][question_format]" required data-question-format class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                <option value="multiple_choice">Pilihan ganda</option>
                <option value="checkbox">Checkbox (lebih dari satu jawaban)</option>
                <option value="true_false">Benar / Salah</option>
            </select>
            <p data-error-for="questions.__INDEX__.question_format" class="mt-1 hidden text-sm text-rose-700"></p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (['A', 'B', 'C', 'D'] as $key)
                <div data-question-option-wrap="{{ $key }}">
                    <label for="question-__INDEX__-option-{{ $key }}" class="mb-1.5 block text-sm font-semibold text-slate-800">Pilihan {{ $key }} @if ($key !== 'D')<span class="text-rose-600" aria-label="wajib diisi">*</span>@else<span class="font-normal text-slate-500">(Opsional)</span>@endif</label>
                    <input id="question-__INDEX__-option-{{ $key }}" name="questions[__INDEX__][options][{{ $key }}]" maxlength="255" @required($key !== 'D') data-question-option="{{ $key }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <p data-error-for="questions.__INDEX__.options.{{ $key }}" class="mt-1 hidden text-sm text-rose-700"></p>
                </div>
            @endforeach
        </div>
        <p data-error-for="questions.__INDEX__.options" class="hidden text-sm text-rose-700"></p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <fieldset>
                    <legend class="mb-1.5 block text-sm font-semibold text-slate-800">Kunci jawaban <span class="text-rose-600" aria-label="wajib diisi">*</span></legend>
                    @foreach (['A', 'B', 'C', 'D'] as $key)
                        <label data-question-correct-wrap="{{ $key }}" class="mb-2 mr-2 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <input type="radio" name="questions[__INDEX__][correct_keys][]" value="{{ $key }}" data-question-correct-choice="{{ $key }}" class="border-slate-300 text-maroon-800 focus:ring-maroon-600">
                            <span>{{ $key }}</span>
                        </label>
                    @endforeach
                    <p data-error-for="questions.__INDEX__.correct_keys" class="mt-1 hidden text-sm text-rose-700"></p>
                </fieldset>
            </div>
            <div>
                <label for="question-__INDEX__-explanation" class="mb-1.5 block text-sm font-semibold text-slate-800">Pembahasan <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
                <textarea id="question-__INDEX__-explanation" name="questions[__INDEX__][explanation]" rows="2" required data-question-explanation class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></textarea>
                <p data-error-for="questions.__INDEX__.explanation" class="mt-1 hidden text-sm text-rose-700"></p>
            </div>
        </div>
    </article>
</template>
