<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreModuleRequest;
use App\Http\Requests\Admin\UpdateModuleRequest;
use App\Http\Requests\Admin\UpdateModuleStatusRequest;
use App\Models\Module;
use App\Services\ModulePublishValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ModuleController extends Controller
{
    // FR-A02, AB-12
    public function index(): View
    {
        return view('admin.modules.index', [
            'modules' => Module::query()->withCount('units')->orderByDesc('id')->paginate(10),
        ]);
    }

    // FR-A02
    public function create(): View
    {
        return view('admin.modules.form', ['module' => new Module()]);
    }

    // FR-A02, AC-42
    public function store(StoreModuleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['description'] = $data['description'] ?? '';
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storeCoverImage($request);
        }

        $module = Module::query()->create($data);

        return redirect()->route('admin.modules.units.index', $module)
            ->with('status', 'Modul berhasil ditambahkan. Silakan tambahkan unit materi.');
    }

    // FR-A02
    public function edit(Module $module): View
    {
        return view('admin.modules.form', compact('module'));
    }

    // FR-A02, AC-42
    public function update(UpdateModuleRequest $request, Module $module): RedirectResponse
    {
        $data = $request->validated();
        $data['description'] = $data['description'] ?? '';

        if ($request->hasFile('cover_image')) {
            $previousCover = $module->cover_image;
            $data['cover_image'] = $this->storeCoverImage($request);
            $module->update($data);

            if ($previousCover && Storage::disk('public')->exists($previousCover)
                && ! Storage::disk('public')->delete($previousCover)) {
                return redirect()->route('admin.modules.index')
                    ->with('error', 'Modul berhasil diperbarui, tetapi gambar sampul lama gagal dibersihkan. Silakan hubungi administrator.');
            }
        } else {
            unset($data['cover_image']);
            $module->update($data);
        }

        return redirect()->route('admin.modules.index')->with('status', 'Modul berhasil diperbarui.');
    }

    // FR-A02, EC-22
    public function destroy(Module $module): RedirectResponse
    {
        $coverImage = $module->cover_image;
        $deleteBlockedBy = DB::transaction(function () use ($module): ?string {
            $lockedModule = Module::query()->lockForUpdate()->findOrFail($module->id);
            $lockedModule->units()->lockForUpdate()->get();

            if (DB::table('test_attempts')->where('module_id', $lockedModule->id)->exists()) {
                return 'test';
            }

            if (DB::table('unit_quiz_attempts')
                ->join('units', 'units.id', '=', 'unit_quiz_attempts.unit_id')
                ->where('units.module_id', $lockedModule->id)
                ->exists()) {
                return 'quiz';
            }

            $lockedModule->delete();

            return null;
        });

        if ($deleteBlockedBy === 'test') {
            return redirect()->route('admin.modules.index')
                ->with('error', 'Modul tidak dapat dihapus karena sudah memiliki riwayat tes. Kembalikan modul ke draft agar tidak tampil kepada Learner.');
        }

        if ($deleteBlockedBy === 'quiz') {
            return redirect()->route('admin.modules.index')
                ->with('error', 'Modul tidak dapat dihapus karena unitnya memiliki riwayat kuis. Kembalikan modul ke draft agar tidak tampil kepada Learner.');
        }

        if ($coverImage && Storage::disk('public')->exists($coverImage)
            && ! Storage::disk('public')->delete($coverImage)) {
            return redirect()->route('admin.modules.index')
                ->with('error', 'Modul berhasil dihapus, tetapi gambar sampul gagal dibersihkan. Silakan hubungi administrator.');
        }

        return redirect()->route('admin.modules.index')->with('status', 'Modul berhasil dihapus.');
    }

    // FR-A04, AC-41
    public function updateStatus(
        UpdateModuleStatusRequest $request,
        Module $module,
        ModulePublishValidator $validator,
    ): RedirectResponse {
        $status = $request->validated('status');

        if ($status === 'published') {
            $validator->validate($module);
        }

        $module->update(['status' => $status]);

        return redirect()->route('admin.modules.index')->with(
            'status',
            $status === 'draft'
                ? 'Modul dikembalikan ke draft. Progres Learner tetap tersimpan.'
                : 'Modul berhasil diterbitkan.',
        );
    }

    // FR-A02: report storage failures on the field instead of creating a module without its requested cover.
    private function storeCoverImage(StoreModuleRequest|UpdateModuleRequest $request): string
    {
        $path = $request->file('cover_image')->store('modules/covers', 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                'cover_image' => 'Gambar sampul gagal disimpan. Silakan coba kembali.',
            ]);
        }

        return $path;
    }
}
