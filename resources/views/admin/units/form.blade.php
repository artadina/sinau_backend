{{-- FR-A03, FR-A04, FR-A16, AC-42, AC-45, AC-46 --}}
@component('layouts.admin', ['title' => 'Kelola Modul'])
    <section class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('admin.modules.units.index', $module) }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-900">&larr; Kembali ke unit</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $unit->exists ? 'Ubah Unit' : 'Tambah Unit' }}</h1>
            <p class="mt-1 text-sm text-slate-600">Modul: {{ $module->title }}</p>
        </div>

        <form method="POST" action="{{ $unit->exists ? route('admin.modules.units.update', [$module, $unit]) : route('admin.modules.units.store', $module) }}" class="glass-card space-y-5 rounded-2xl p-6 sm:p-8">
            @csrf
            @if ($unit->exists)
                @method('PUT')
            @endif

            <div>
                <label for="title" class="mb-1.5 block text-sm font-semibold text-slate-800">
                    Judul unit <span class="text-rose-600" aria-label="wajib diisi">*</span>
                </label>
                <input id="title" name="title" value="{{ old('title', $unit->title) }}" required maxlength="150" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-maroon-600 focus:ring-2 focus:ring-maroon-100">
                @error('title')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="learning_objective" class="mb-1.5 block text-sm font-semibold text-slate-800">
                    Tujuan belajar (satu kalimat) <span class="text-rose-600" aria-label="wajib diisi">*</span>
                </label>
                <input id="learning_objective" name="learning_objective" value="{{ old('learning_objective', $unit->learning_objective) }}" required maxlength="255" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-maroon-600 focus:ring-2 focus:ring-maroon-100">
                @error('learning_objective')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="video_url" class="mb-1.5 block text-sm font-semibold text-slate-800">
                    Tautan video <span class="text-rose-600" aria-label="wajib diisi">*</span>
                </label>
                <input id="video_url" name="video_url" type="url" value="{{ old('video_url', $unit->video_url) }}" required maxlength="255" placeholder="https://www.youtube.com/watch?v=... atau https://contoh.com/video.mp4" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-maroon-600 focus:ring-2 focus:ring-maroon-100">
                @error('video_url')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                <p class="mt-1.5 text-xs text-slate-500">Bisa memakai tautan YouTube atau tautan langsung ke file video. Video YouTube harus mengizinkan embed.</p>
            </div>

            <div>
                <label for="duration_minutes" class="mb-1.5 block text-sm font-semibold text-slate-800">
                    Durasi (menit) <span class="font-normal text-slate-500">(Opsional)</span>
                </label>
                <input id="duration_minutes" name="duration_minutes" type="number" min="0.01" max="35791394.12" step="0.01" value="{{ old('duration_minutes', $unit->duration_minutes) }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-maroon-600 focus:ring-2 focus:ring-maroon-100">
                @error('duration_minutes')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div
                data-material-editor
                data-image-upload-url="{{ $unit->exists ? route('admin.units.material-images.store', $unit) : '' }}"
                data-pending-key="sinau-unit-material-{{ $module->id }}"
                class="space-y-3"
            >
                <label class="block text-sm font-semibold text-slate-800">Materi singkat <span class="font-normal text-slate-500">(Opsional)</span></label>
                <div class="overflow-hidden rounded-xl border border-slate-300 bg-white focus-within:border-maroon-600 focus-within:ring-2 focus-within:ring-maroon-100">
                    {{-- FR-A16, AC-45, AC-46 --}}
                    <div data-material-toolbar class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50 p-3" role="toolbar" aria-label="Format materi singkat">
                        <div class="material-toolbar-group" role="group" aria-label="Gaya teks">
                            <select data-material-block-style aria-label="Gaya teks" class="material-toolbar-select">
                                <option value="paragraph">Paragraf</option>
                                <option value="heading-2">Judul</option>
                                <option value="heading-3">Subjudul</option>
                            </select>
                        </div>
                        <div class="material-toolbar-group" role="group" aria-label="Format teks">
                            <button type="button" data-material-command="bold" aria-label="Tebal" title="Tebal" aria-pressed="false" class="material-toolbar-button">
                                <i data-lucide="bold" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                            <button type="button" data-material-command="italic" aria-label="Miring" title="Miring" aria-pressed="false" class="material-toolbar-button">
                                <i data-lucide="italic" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="material-toolbar-group" role="group" aria-label="Daftar">
                            <button type="button" data-material-command="bulletList" aria-label="Daftar poin" title="Daftar poin" aria-pressed="false" class="material-toolbar-button">
                                <i data-lucide="list" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                            <button type="button" data-material-command="orderedList" aria-label="Daftar angka" title="Daftar angka" aria-pressed="false" class="material-toolbar-button">
                                <i data-lucide="list-ordered" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="material-toolbar-group" role="group" aria-label="Sisipkan materi">
                            <button type="button" data-material-add-image aria-label="Tambah gambar" title="Tambah gambar" class="material-toolbar-button gap-2 px-3">
                                <i data-lucide="image-plus" class="h-4 w-4" aria-hidden="true"></i>
                                <span class="text-xs font-semibold">Gambar</span>
                            </button>
                        </div>
                        <div class="material-toolbar-group" role="group" aria-label="Impor materi">
                            <button type="button" data-material-import-trigger aria-label="Impor DOCX atau PDF" title="Impor DOCX atau PDF" class="material-toolbar-button gap-2 px-3">
                                <i data-lucide="file-up" class="h-4 w-4" aria-hidden="true"></i>
                                <span class="text-xs font-semibold">Impor DOCX/PDF</span>
                            </button>
                        </div>
                    </div>
                    <div data-material-content class="material-editor-content min-h-48 px-4 py-3 text-sm leading-7 outline-none" aria-label="Isi materi singkat" role="textbox" aria-multiline="true"></div>
                </div>
                <input data-material-image-input type="file" accept="image/jpeg,image/png" class="hidden">
                <input data-material-import-input type="file" accept=".docx,.pdf" class="hidden">
                <textarea id="reading_content" name="reading_content" class="hidden">{{ old('reading_content', $unit->reading_content) }}</textarea>
                <input type="hidden" name="continue_editing" value="0" data-material-continue>
                <p class="text-xs leading-5 text-slate-500">Gambar JPG/PNG maksimal 5 MB per file dan 10 gambar per materi. Impor DOCX atau PDF berteks maksimal 10 MB; PDF maksimal 50 halaman. DOC lama dan PDF hasil scan belum didukung.</p>
                <p data-material-message role="status" aria-live="polite" class="hidden text-sm"></p>
                @error('reading_content')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="sequence_order" class="mb-1.5 block text-sm font-semibold text-slate-800">
                    Urutan unit <span class="text-rose-600" aria-label="wajib diisi">*</span>
                </label>
                <input id="sequence_order" name="sequence_order" type="number" step="1" value="{{ old('sequence_order', $unit->exists ? $unit->sequence_order : ($module->units()->max('sequence_order') ?? 0) + 1) }}" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-maroon-600 focus:ring-2 focus:ring-maroon-100">
                @error('sequence_order')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.modules.units.index', $module) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Simpan Unit</button>
            </div>
        </form>
    </section>
@endcomponent
