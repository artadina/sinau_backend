<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreModuleRequest;
use App\Http\Requests\Admin\UpdateModuleRequest;
use App\Models\Module;
use App\Models\ModuleExtensionRequest;
use App\Models\Unit;
use App\Services\ModuleGovernanceService;
use App\Services\ModuleQualityGate;
use App\Services\ModuleVersionFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ModuleController extends Controller
{
    // FR-A02, PS-30: list modules but expose editing actions only for the owning Admin.
    public function index(): View
    {
        return view('admin.modules.index', [
            'modules' => Module::query()->with(['creator'])->withCount('units')->orderByDesc('id')->paginate(10),
        ]);
    }

    // PS-01–PS-22, PS-30: show the current Quality Gate and workflow history to the owner.
    public function show(Module $module, ModuleQualityGate $qualityGate): View
    {
        return view('admin.modules.show', [
            'module' => $module->load(['units.quizzes', 'testQuestions', 'reviews.actor', 'extensionRequests']),
            'checks' => $qualityGate->inspect($module),
            'pendingExtension' => $module->extensionRequests()->where('status', 'pending')->exists(),
        ]);
    }

    // FR-A02
    public function create(): View
    {
        return view('admin.modules.form', ['module' => new Module()]);
    }

    // FR-A02, PS-30: every new module starts as an Admin-owned draft.
    public function store(StoreModuleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['description'] = $data['description'] ?? '';
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'draft';

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storeCoverImage($request);
        }

        $module = DB::transaction(function () use ($data, $request): Module {
            $module = Module::query()->create($data);
            app(ModuleGovernanceService::class)->record(
                $module,
                'created',
                $request->user(),
                null,
                'draft',
            );

            return $module;
        });

        return redirect()->route('admin.modules.show', $module)
            ->with('status', 'Modul draft berhasil dibuat. Lengkapi unit dan bank soal melalui halaman modul.');
    }

    // PS-30: drafts and revision copies can be edited in place.
    public function edit(Module $module): View
    {
        abort_unless(in_array($module->status, ['draft', 'revision_required', 'published'], true), 403);

        return view('admin.modules.form', compact('module'));
    }

    // PS-30: a published module can only receive the three explicitly minor edits.
    public function update(
        UpdateModuleRequest $request,
        Module $module,
        ModuleGovernanceService $governance,
    ): RedirectResponse {
        $data = $request->validated();
        $data['description'] = $data['description'] ?? '';
        $published = $module->status === 'published';

        if ($published && $this->titleUsedByAnotherModule($module, $data['title'])) {
            throw ValidationException::withMessages([
                'title' => 'Judul sudah digunakan modul lain.',
            ]);
        }

        $previousCover = $module->cover_image;
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->storeCoverImage($request);
        } else {
            unset($data['cover_image']);
        }

        DB::transaction(function () use ($module, $data, $published, $request, $governance): void {
            $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
            abort_unless($locked->created_by === $request->user()->id, 404);
            abort_unless(
                $published
                    ? $locked->status === 'published'
                    : in_array($locked->status, ['draft', 'revision_required'], true),
                403,
            );
            $locked->update($data);

            if ($published) {
                $governance->record(
                    $locked,
                    'minor_update',
                    $request->user(),
                    'published',
                    'published',
                    null,
                    ['fields' => array_keys($data)],
                );
            }
        });

        if ($request->hasFile('cover_image') && $previousCover
            && ! Module::query()->where('cover_image', $previousCover)->exists()
            && Storage::disk('public')->exists($previousCover)
            && ! Storage::disk('public')->delete($previousCover)) {
            return redirect()->route('admin.modules.index')
                ->with('error', 'Modul berhasil diperbarui, tetapi gambar sampul lama gagal dibersihkan. Silakan hubungi administrator.');
        }

        return redirect()->route('admin.modules.show', $module)->with('status', 'Modul berhasil diperbarui.');
    }

    // FR-A02, EC-22: protected learner history prevents physical deletion.
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

        if ($deleteBlockedBy !== null) {
            $type = $deleteBlockedBy === 'test' ? 'tes' : 'kuis unit';

            return redirect()->route('admin.modules.index')
                ->with('error', "Modul tidak dapat dihapus karena sudah memiliki riwayat {$type}. Modul dapat dikembalikan ke draft agar tidak tampil kepada Learner.");
        }

        if ($coverImage && ! Module::query()->where('cover_image', $coverImage)->exists()
            && Storage::disk('public')->exists($coverImage)
            && ! Storage::disk('public')->delete($coverImage)) {
            return redirect()->route('admin.modules.index')
                ->with('error', 'Modul berhasil dihapus, tetapi gambar sampul gagal dibersihkan. Silakan hubungi administrator.');
        }

        return redirect()->route('admin.modules.index')->with('status', 'Modul berhasil dihapus.');
    }

    // PS-01–PS-22: only a complete module with the ownership declaration can enter review.
    public function submit(
        Request $request,
        Module $module,
        ModuleQualityGate $qualityGate,
        ModuleGovernanceService $governance,
    ): RedirectResponse {
        $request->validate([
            'copyright_confirmation' => ['accepted'],
        ], [
            'copyright_confirmation.accepted' => 'Pernyataan hak cipta dan kepemilikan konten wajib disetujui.',
        ]);

        DB::transaction(function () use ($module, $request, $qualityGate, $governance): void {
            $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
            abort_unless(in_array($locked->status, ['draft', 'revision_required'], true), 403);
            $failed = $qualityGate->failed($qualityGate->inspect($locked));

            if ($failed !== []) {
                throw ValidationException::withMessages(['quality_gate' => implode(' ', $failed)]);
            }

            $from = $locked->status;
            $locked->update(['status' => 'submitted', 'submitted_at' => now()]);
            $governance->record(
                $locked,
                $from === 'revision_required' ? 'resubmitted' : 'submitted',
                $request->user(),
                $from,
                'submitted',
                null,
                ['copyright_confirmation' => true],
            );
        });

        $governance->notifySuperadmins(
            $module,
            'module_submitted',
            "Modul '{$module->title}' menunggu review.",
        );

        return redirect()->route('admin.modules.show', $module)
            ->with('status', 'Modul berhasil diajukan untuk review.');
    }

    // PS-22: extension requests leave a published module live until the old end date.
    public function requestExtension(
        Request $request,
        Module $module,
        ModuleGovernanceService $governance,
    ): RedirectResponse {
        $validated = $request->validate([
            'active_until' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($module): void {
                    if ($module->active_from === null || $module->active_until === null) {
                        $fail('Periode aktif modul belum lengkap.');

                        return;
                    }

                    $requestedEnd = \Illuminate\Support\Carbon::parse($value);
                    if ($requestedEnd->lessThanOrEqualTo($module->active_until)) {
                        $fail('Tanggal akhir baru harus melewati tanggal aktif saat ini.');
                    } elseif ($requestedEnd->greaterThan($module->active_from->copy()->addMonthsNoOverflow(12))) {
                        $fail('Total periode aktif tidak boleh melebihi 12 bulan.');
                    }
                },
            ],
            'reason' => ['required', 'string', 'max:2000'],
        ], [
            'required' => ':attribute wajib diisi.',
        ], [
            'active_until' => 'Tanggal akhir baru',
            'reason' => 'Alasan perpanjangan',
        ]);

        abort_unless($module->status === 'published' && $module->active_until?->greaterThanOrEqualTo(today()), 403);

        DB::transaction(function () use ($module, $request, $validated, $governance): void {
            $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
            abort_unless($locked->status === 'published', 403);
            abort_if($locked->extensionRequests()->where('status', 'pending')->exists(), 409);

            $extension = $locked->extensionRequests()->create([
                'requested_by' => $request->user()->id,
                'previous_active_until' => $locked->active_until,
                'requested_active_until' => $validated['active_until'],
                'reason' => $validated['reason'],
            ]);
            $governance->record(
                $locked,
                'extension_requested',
                $request->user(),
                'published',
                'published',
                $validated['reason'],
                ['extension_request_id' => $extension->id, 'active_until' => $validated['active_until']],
            );
        });

        $governance->notifySuperadmins(
            $module,
            'extension_requested',
            "Perpanjangan periode aktif modul '{$module->title}' menunggu keputusan.",
        );

        return redirect()->route('admin.modules.show', $module)
            ->with('status', 'Pengajuan perpanjangan berhasil dikirim.');
    }

    // PS-30: significant changes are edited in a copy; published content stays isolated.
    public function startRevision(Module $module): RedirectResponse
    {
        abort_unless(in_array($module->status, ['published', 'archived'], true), 403);

        $revision = null;
        $copiedUnitIds = [];
        try {
            $revision = DB::transaction(function () use ($module, &$copiedUnitIds): Module {
                $locked = Module::query()->lockForUpdate()->findOrFail($module->id);
                abort_unless(in_array($locked->status, ['published', 'archived'], true), 403);
                abort_if($locked->status === 'archived' && $locked->revisions()->exists(), 409);
                abort_if(
                    $locked->revisions()->whereIn('status', ['draft', 'submitted', 'revision_required', 'approved'])->exists(),
                    409,
                );

                $revision = $locked->replicate([
                    'status', 'submitted_at', 'approved_by', 'approved_at', 'active_from', 'active_until',
                ]);
                $revision->status = 'draft';
                $revision->revision_of_id = $locked->id;
                $revision->save();

                foreach ($locked->units()->with('quizzes')->get() as $sourceUnit) {
                    $unit = $sourceUnit->replicate();
                    $unit->module_id = $revision->id;
                    $unit->save();
                    $copiedUnitIds[] = $unit->id;
                    $this->copyUnitImages($sourceUnit, $unit);
                    $unit->update([
                        'reading_content' => str_replace(
                            '/units/'.$sourceUnit->id.'/material/images/',
                            '/units/'.$unit->id.'/material/images/',
                            $sourceUnit->reading_content,
                        ),
                    ]);

                    foreach ($sourceUnit->quizzes as $quiz) {
                        $quizCopy = $quiz->replicate();
                        $quizCopy->unit_id = $unit->id;
                        $quizCopy->save();
                    }
                }

                foreach ($locked->testQuestions as $question) {
                    $questionCopy = $question->replicate();
                    $questionCopy->module_id = $revision->id;
                    $questionCopy->save();
                }

                return $revision;
            });
        } catch (\RuntimeException $exception) {
            foreach ($copiedUnitIds as $unitId) {
                Storage::disk('local')->deleteDirectory("unit-materials/{$unitId}");
            }

            throw $exception;
        }

        app(ModuleGovernanceService::class)->record(
            $module,
            'revision_created',
            request()->user(),
            $module->status,
            $module->status,
            null,
            ['revision_module_id' => $revision->id],
        );

        return redirect()->route('admin.modules.edit', $revision)
            ->with('status', 'Salinan draft berhasil dibuat. Perubahan signifikan tidak mengubah modul yang sedang terbit.');
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

    private function titleUsedByAnotherModule(Module $module, string $title): bool
    {
        return Module::query()->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
            ->whereNotIn('id', app(ModuleVersionFamily::class)->ids($module))
            ->exists();
    }

    private function copyUnitImages(Unit $source, Unit $destination): void
    {
        $disk = Storage::disk('local');
        $sourceDirectory = "unit-materials/{$source->id}";
        $destinationDirectory = "unit-materials/{$destination->id}";

        foreach ($disk->files($sourceDirectory) as $path) {
            if (! $disk->copy($path, $destinationDirectory.'/'.basename($path))) {
                throw new \RuntimeException('Gambar materi gagal disalin untuk versi modul baru.');
            }
        }
    }
}
