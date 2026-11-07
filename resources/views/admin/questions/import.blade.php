{{-- FR-A05, AC-42: review CSV rows separately from manually entered questions. --}}
@component('layouts.admin', ['title' => 'Pratinjau Impor CSV'])
    <section class="mx-auto max-w-5xl space-y-6" data-question-import-page data-mode="csv" data-storage-key="sinau-question-import-{{ $module->id }}-{{ $type }}">
        <div>
            <a href="{{ route('admin.modules.questions.index', [$module, $type]) }}" data-cancel-question-import class="text-sm font-semibold text-maroon-800 hover:text-maroon-900">&larr; Kembali ke bank soal</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">Pratinjau Impor CSV</h1>
            <p class="mt-1 text-sm text-slate-600">Bank {{ $type === 'pre' ? 'Pre-test' : 'Post-test' }} · {{ $module->title }}</p>
        </div>

        <div role="note" class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            Periksa dan edit soal di bawah. Soal yang sudah ada di bank tidak ikut diubah.
        </div>
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">Ada soal yang belum valid. Periksa field yang ditandai sebelum menyimpan.</div>
        @endif
        <p data-question-import-message role="status" aria-live="polite" class="hidden rounded-lg px-3 py-2 text-sm"></p>

        <section data-question-editor data-mode="csv" class="glass-card space-y-5 rounded-2xl p-5 sm:p-7">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Soal untuk ditambahkan</h2>
                    <p data-import-file-name class="mt-1 text-sm text-slate-600"></p>
                </div>
                <button type="button" data-add-question class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tambah kartu lain</button>
            </div>

            <form method="POST" action="{{ route('admin.modules.questions.bulk', [$module, $type]) }}" data-question-bulk-form class="space-y-4">
                @csrf
                <input type="hidden" name="source" value="csv">
                <div data-question-list class="space-y-4"></div>
                @include('admin.questions._question-card-template')
                <script type="application/json" data-question-initial-input>{!! json_encode(old('questions', []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                <script type="application/json" data-question-validation-errors>{!! json_encode($errors->getMessages(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-100 pt-4 sm:flex-row">
                    <a href="{{ route('admin.modules.questions.index', [$module, $type]) }}" data-cancel-question-import class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                    <button type="submit" data-save-imported-questions class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Simpan Soal CSV</button>
                </div>
            </form>
        </section>
    </section>
@endcomponent
