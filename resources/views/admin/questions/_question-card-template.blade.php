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
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (['A', 'B', 'C', 'D'] as $key)
                <div>
                    <label for="question-__INDEX__-option-{{ $key }}" class="mb-1.5 block text-sm font-semibold text-slate-800">Pilihan {{ $key }} @if ($key !== 'D')<span class="text-rose-600" aria-label="wajib diisi">*</span>@else<span class="font-normal text-slate-500">(Opsional)</span>@endif</label>
                    <input id="question-__INDEX__-option-{{ $key }}" name="questions[__INDEX__][options][{{ $key }}]" maxlength="255" @required($key !== 'D') data-question-option="{{ $key }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <p data-error-for="questions.__INDEX__.options.{{ $key }}" class="mt-1 hidden text-sm text-rose-700"></p>
                </div>
            @endforeach
        </div>
        <p data-error-for="questions.__INDEX__.options" class="hidden text-sm text-rose-700"></p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label for="question-__INDEX__-correct" class="mb-1.5 block text-sm font-semibold text-slate-800">Kunci jawaban <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
                <select id="question-__INDEX__-correct" name="questions[__INDEX__][correct_key]" required data-question-correct class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
                </select>
                <p data-error-for="questions.__INDEX__.correct_key" class="mt-1 hidden text-sm text-rose-700"></p>
            </div>
            <div>
                <label for="question-__INDEX__-explanation" class="mb-1.5 block text-sm font-semibold text-slate-800">Pembahasan <span class="text-rose-600" aria-label="wajib diisi">*</span></label>
                <textarea id="question-__INDEX__-explanation" name="questions[__INDEX__][explanation]" rows="2" required data-question-explanation class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></textarea>
                <p data-error-for="questions.__INDEX__.explanation" class="mt-1 hidden text-sm text-rose-700"></p>
            </div>
        </div>
    </article>
</template>
