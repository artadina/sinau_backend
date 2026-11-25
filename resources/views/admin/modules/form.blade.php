{{-- FR-A02, AC-42 --}}
@component('layouts.admin', ['title' => 'Kelola Modul'])
    @php($minorEdit = $module->status === 'published')
    <section class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ $module->exists ? route('admin.modules.show', $module) : route('admin.modules.index') }}" class="text-sm font-semibold text-maroon-800 hover:text-maroon-900">&larr; Kembali</a>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $minorEdit ? 'Edit Minor Modul Terbit' : ($module->exists ? 'Ubah Modul' : 'Tambah Modul') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $minorEdit ? 'Hanya judul, deskripsi, dan gambar sampul dapat diubah langsung. Perubahan lain memerlukan versi baru dan review.' : 'Lengkapi metadata modul. Quality Gate ditampilkan pada halaman detail modul.' }}</p>
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ $module->exists ? route('admin.modules.update', $module) : route('admin.modules.store') }}" class="glass-card space-y-5 rounded-2xl p-6 sm:p-8">
            @csrf
            @if ($module->exists)
                @method('PUT')
            @endif

            <div>
                <label for="title" class="mb-1.5 block text-sm font-semibold text-slate-800">
                    Judul modul <span class="text-rose-600" aria-label="wajib diisi">*</span>
                </label>
                <input id="title" name="title" value="{{ old('title', $module->title) }}" required maxlength="150" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-2 focus:ring-maroon-100">
                @error('title')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="mb-1.5 block text-sm font-semibold text-slate-800">Deskripsi <span class="font-normal text-slate-500">(Opsional)</span></label>
                <textarea id="description" name="description" rows="3" maxlength="255" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-2 focus:ring-maroon-100">{{ old('description', $module->description) }}</textarea>
                @error('description')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            @unless ($minorEdit)
                <div>
                    <label for="learning_objectives" class="mb-1.5 block text-sm font-semibold text-slate-800">Tujuan belajar <span class="font-normal text-slate-500">(2–5 poin, satu poin per baris)</span></label>
                    <textarea id="learning_objectives" name="learning_objectives" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-maroon-600 focus:ring-2 focus:ring-maroon-100">{{ old('learning_objectives', $module->learning_objectives) }}</textarea>
                    @error('learning_objectives')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="category" class="mb-1.5 block text-sm font-semibold text-slate-800">Kategori/topik</label>
                        <input id="category" name="category" maxlength="100" value="{{ old('category', $module->category) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('category')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="difficulty" class="mb-1.5 block text-sm font-semibold text-slate-800">Tingkat kesulitan</label>
                        <select id="difficulty" name="difficulty" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">
                            <option value="">Pilih tingkat</option>
                            @foreach (['Dasar', 'Menengah'] as $difficulty)
                                <option value="{{ $difficulty }}" @selected(old('difficulty', $module->difficulty) === $difficulty)>{{ $difficulty }}</option>
                            @endforeach
                        </select>
                        @error('difficulty')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="target_audience" class="mb-1.5 block text-sm font-semibold text-slate-800">Target peserta</label>
                    <textarea id="target_audience" name="target_audience" rows="2" maxlength="1000" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">{{ old('target_audience', $module->target_audience) }}</textarea>
                    @error('target_audience')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
            @endunless

            @if (! $minorEdit)
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="active_from" class="mb-1.5 block text-sm font-semibold text-slate-800">Mulai periode aktif</label>
                        <input id="active_from" name="active_from" type="date" value="{{ old('active_from', $module->active_from?->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('active_from')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="active_until" class="mb-1.5 block text-sm font-semibold text-slate-800">Akhir periode aktif</label>
                        <input id="active_until" name="active_until" type="date" value="{{ old('active_until', $module->active_until?->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        @error('active_until')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endif

            @if (! $minorEdit)
            <div>
                <label for="cover_image" class="mb-1.5 block text-sm font-semibold text-slate-800">Gambar sampul <span class="font-normal text-slate-500">(JPG/PNG, maksimal 2 MB; wajib sebelum diajukan)</span></label>
            @else
            <div>
                <label for="cover_image" class="mb-1.5 block text-sm font-semibold text-slate-800">Gambar sampul <span class="font-normal text-slate-500">(Opsional, JPG/PNG, maksimal 2 MB)</span></label>
            @endif
                <input id="cover_image" name="cover_image" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-maroon-50 file:px-4 file:py-3 file:font-semibold file:text-maroon-800">
                @if ($module->cover_image)
                    <div class="mt-3 flex items-center gap-3">
                        <img src="{{ asset('storage/'.$module->cover_image) }}" alt="Sampul {{ $module->title }}" class="h-16 w-24 rounded-lg border border-slate-200 object-cover">
                        <p class="text-xs text-slate-500">Sampul saat ini. Pilih file baru jika ingin menggantinya.</p>
                    </div>
                @endif
                @error('cover_image')<p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.modules.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                <button type="submit" class="rounded-xl bg-maroon-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-maroon-900">Simpan Modul</button>
            </div>
        </form>
    </section>
@endcomponent
